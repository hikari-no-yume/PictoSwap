<?php
declare(strict_types=1);

namespace ajf\PictoSwap;

use PDO;

// Finds out if a user with the given username exists
function user_exists(string $username): bool {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT
            COUNT(*)
        FROM
            users
        WHERE
            username = :username
        ;
    ');
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // If 0 rows, no such user exists -> false
    // If 1 row, user exists -> true
    return (bool)($row['COUNT(*)']);
}

// Finds out if a user with the given ID exists
function user_id_exists(int $user_id): bool {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT
            COUNT(*)
        FROM
            users
        WHERE
            user_id = :user_id
        ;
    ');
    $stmt->execute([':user_id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    // If 0 rows, no such user exists -> false
    // If 1 row, user exists -> true
    return (bool)($row['COUNT(*)']);
}

// Returns the ID of the user with the given username, or NULL if none
function user_find_id(string $username) {
    $db = connectDB();
    
    // Look up user
    $stmt = $db->prepare('
        SELECT
            user_id
        FROM
            users
        WHERE
            username = :username
        LIMIT
            1
        ;
    ');
    $stmt->execute([':username' => $username]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        return NULL;
    } else {
        return $rows[0]['user_id'];
    }
}

// Changes a user's password
function user_change_password(int $user_id, string $new_password) {
    $db = connectDB();
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

    $db->beginTransaction();
    $stmt = $db->prepare('
        UPDATE
            users
        SET
            password_hash = :password_hash
        WHERE
            user_id = :user_id
        ;
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':password_hash' => $password_hash
    ]);
    $db->commit();
}

// Registers a user
function user_register(string $username, string $password) {
    if (user_exists($username)) {
        throw new PictoSwapException("There is already a user with that username");
    }

    if (!\preg_match(VALID_USERNAME_REGEX, $username)) {
        throw new PictoSwapException("Username can only be 3-18 characters in length, composed only of lowercase letters, numbers and underscores");
    }

    $db = connectDB();
    $password_hash = \password_hash($password, PASSWORD_BCRYPT);

    $db->beginTransaction();
    $stmt = $db->prepare('
        INSERT INTO
            users(username, password_hash, timestamp)
        VALUES
            (:username, :password_hash, datetime(\'now\'))
        ;
    ');
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => $password_hash
    ]);
    $user_id = $db->lastInsertId();
    $db->commit();
}

// Logs in a user
// Returns an array containing new session data (give to startUserSession())
function user_login(string $username, string $password): array {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT
            user_id,
            password_hash
        FROM
            users
        WHERE
            username = :username
        LIMIT
            1
        ;
    ');
    $stmt->execute([':username' => $username]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        throw new PictoSwapException("No such user.", 404);
    }
    $password_hash = $rows[0]['password_hash'];
    if (!\password_verify($password, $password_hash)) {
        throw new PictoSwapException("Incorrect password.");
    }
    return [
        'logged_in' => TRUE,
        'user_id' => (int)$rows[0]['user_id'],
        'username' => $username
    ];
}

// Sends a user's letter to the specified recipients
function user_send_letter(int $user_id, int $letter_id, array $friend_ids) {
    $db = connectDB();

    // Check there is such a letter
    $stmt = $db->prepare('
        SELECT
            COUNT(*)
        FROM
            letters
        WHERE
            letter_id = :letter_id
            AND user_id = :user_id
        ;
    ');
    $stmt->execute([
        ':letter_id' => $letter_id,
        ':user_id' => $user_id
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row['COUNT(*)']) {
        throw new PictoSwapException("There is no such letter with that ID owned by you.", 404);
    }

    // Actually send
    $db->beginTransaction();
    foreach ($friend_ids as $friend_id) {
        $stmt = $db->prepare('
            INSERT INTO
                letter_recipients(user_id, letter_id, read, timestamp)
            VALUES
                (:user_id, :letter_id, 0, datetime(\'now\'))
            ;
        ');
        $stmt->execute([
            ':user_id' => $friend_id,
            ':letter_id' => $letter_id
        ]);
    }
    $db->commit();
}

// Adds a new letter for a user
function user_new_letter(int $user_id, \StdClass $letter) {
    $images = renderLetterPreviews($letter);

    $db = connectDB();
    $db->beginTransaction();
    $stmt = $db->prepare('
        INSERT INTO
            letters(user_id, timestamp, content)
        VALUES
            (:user_id, datetime(\'now\'), :content)
        ;
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':content' => json_encode($letter)
    ]);
    $letter_id = $db->lastInsertId();
    $stmt = $db->prepare('
        INSERT INTO
            letter_recipients(user_id, letter_id, read, timestamp)
        VALUES
            (:user_id, :letter_id, 1, datetime(\'now\'))
        ;
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':letter_id' => $letter_id
    ]);
    $db->commit();

    for ($i = 0; $i < count($images); $i++) {
        \ImagePNG($images[$i], 'previews/' . $letter_id . '-' . $i . '.png');
        \ImageDestroy($images[$i]);
    }
}

// Gets array of user's letters, most recent first
function user_get_received_letters(int $user_id): array {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT
            letters.user_id AS from_id,
            users.username AS from_username,
            letters.letter_id AS letter_id,
            letter_recipients.timestamp AS timestamp,
            letter_recipients.read AS read
        FROM
            letter_recipients
        LEFT JOIN
            letters
        ON
            letter_recipients.letter_id = letters.letter_id
        LEFT JOIN
            users
        ON
            letters.user_id = users.user_id
        WHERE
            letter_recipients.user_id = :user_id
        ORDER BY
            timestamp DESC
        ;
    ');
    $stmt->execute([
        ':user_id' => $user_id
    ]);
    $letters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return \array_map(function (array $letter): array {
        return [
            'from_id'       => (int)$letter['from_id'],
            'from_username' => (string)$letter['from_username'],
            'letter_id'     => (int)$letter['letter_id'],
            'timestamp'     => $letter['timestamp'],
            'read'          => (bool)$letter['read'],
            'own'           => (bool)((int)$letter['from_id'] === getSessionUserId())
        ];
    }, $letters);
    return $letters;
}

// Gets possible recipients for a letter (friends it hasn't yet been sent to)
function user_get_possible_recipients(int $user_id, int $letter_id): array {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT
            my_friends.user_id AS id,
            users.username AS username
        FROM
            (
                SELECT
                    (CASE WHEN
                        user_id_1 = :user_id
                    THEN
                        user_id_2
                    ELSE
                        user_id_1
                    END) AS user_id
                FROM
                    friendships
                WHERE
                    user_id_1 = :user_id
                    OR user_id_2 = :user_id
            ) AS my_friends
        LEFT JOIN
            users
        ON
            my_friends.user_id = users.user_id 
        WHERE
            my_friends.user_id NOT IN (
                SELECT
                    user_id
                FROM
                    letter_recipients
                WHERE
                    letter_recipients.letter_id = :letter_id
            )
        ;
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':letter_id' => $letter_id
    ]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return \array_map(function (array $friend): array {
        return [
            'id'            => (int)$friend['id'],
            'username'      => (string)$friend['username']
        ];
    }, $friends);
}

// Gets a letter received by a user
function user_get_received_letter(int $user_id, int $letter_id): array {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT
            letters.user_id AS from_id,
            users.username AS from_username,
            letters.letter_id AS letter_id,
            letter_recipients.timestamp AS timestamp,
            letter_recipients.read AS read,
            letters.content AS content
        FROM
            letter_recipients
        LEFT JOIN
            letters
        ON
            letter_recipients.letter_id = letters.letter_id
        LEFT JOIN
            users
        ON
            letters.user_id = users.user_id
        WHERE
            letter_recipients.user_id = :user_id
            AND letters.letter_id = :letter_id
        ORDER BY
            timestamp DESC
        ;
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':letter_id' => $letter_id
    ]);
    $letter = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($letter === null) {
        throw new PictoSwapException("No such letter.", 404);
    } else {
        $stmt = $db->prepare('
            UPDATE
                letter_recipients
            SET
                read = 1
            WHERE
                user_id = :user_id
                AND letter_id = :letter_id
            ;
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':letter_id' => $letter_id
        ]);

        return [
            'from_id'           => (int)$letter['from_id'],
            'from_username'     => (string)$letter['from_username'],
            'letter_id'         => (int)$letter['letter_id'],
            'timestamp'         => $letter['timestamp'],
            'read'              => (bool)$letter['read'],
            'own'               => (bool)((int)$letter['from_id'] === getSessionUserId()),
            'content'           => \json_decode($letter['content'])
        ];
    }
}

// Sends a friend request
// Return value of TRUE indicates success, otherwise string error returned
function user_add_friend(int $user_id, string $friend_username) {
    $db = connectDB();
    
    // Look up user
    $friend_user_id = user_find_id($friend_username);
    
    if ($friend_user_id === NULL) {
        throw new PictoSwapException("No such user.", 404);
    }
    
    if ($friend_user_id == $user_id) {
        throw new PictoSwapException("You cannot send a friend request to yourself.", 400);
    }
    
    // Check they're not already friends
    $stmt = $db->prepare('
        SELECT
            user_id_1, user_id_2, provisional
        FROM
            friendships
        WHERE
            (user_id_1 = :user_id_1 AND user_id_2 = :user_id_2)
            OR (user_id_1 = :user_id_2 AND user_id_2 = :user_id_1)
        LIMIT
            1
        ;
    ');
    $stmt->execute([
        ':user_id_1' => $user_id,
        ':user_id_2' => $friend_user_id
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        $row = $rows[0];
        if ($row['provisional']) {
            if ($row['user_id_1'] == $user_id) {
                throw new PictoSwapException("You already sent a friend request to that user.", 200);
            } else {
                throw new PictoSwapException("That user already sent you a friend request.", 200);
            }
        } else {
            throw new PictoSwapException("You are already friends with that user.", 200);
        }
    }
    
    // Actually send the request
    $db->beginTransaction();
    $stmt = $db->prepare('
        INSERT INTO
            friendships(user_id_1, user_id_2, timestamp, provisional)
        VALUES
            (:user_id_1, :user_id_2, datetime(\'now\'), 1)
        ;
    ');
    $stmt->execute([
        ':user_id_1' => $user_id,
        ':user_id_2' => $friend_user_id
    ]);
    $db->commit();
}

// Gets friend requests as an array
function user_get_friend_requests(int $user_id): array {
    $db = connectDB();
    $stmt = $db->prepare('
        SELECT
            friendships.user_id_1 AS user_id,
            users.username AS username
        FROM
            friendships
        LEFT JOIN
            users
        ON
            friendships.user_id_1 = users.user_id
        WHERE
            friendships.user_id_2 = :user_id_2
            AND friendships.provisional = 1
        ORDER BY
            friendships.timestamp DESC
        ;
    ');
    $stmt->execute([
        ':user_id_2' => $user_id
    ]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array_map(function (array $request): array {
        return [
            'user_id'       => (int)$request['user_id'],
            'username'      => (string)$request['username']
        ];
    }, $requests);
}

// Responds to a friend request ($mode is 'accept' to accept, 'deny' to deny)
function user_friend_request_respond(int $user_id, int $friend_user_id, string $mode) {
    $db = connectDB();
    
    // Check user exists
    if (!user_id_exists($friend_user_id)) {
        throw new PictoSwapException("No such user.", 404);
    }
    
    // Check there is such a friend request
    $stmt = $db->prepare('
        SELECT
            COUNT(*)
        FROM
            friendships
        WHERE
            user_id_1 = :user_id_1
            AND user_id_2 = :user_id_2
            AND provisional = 1
        LIMIT
            1
        ;
    ');
    $stmt->execute([
        ':user_id_1' => $friend_user_id,
        ':user_id_2' => $user_id
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$rows[0]['COUNT(*)']) {
        throw new PictoSwapException("No such friend request.", 404);
    }
    
    // Actually accept/deny the request
    if ($mode === 'accept') {
        $db->beginTransaction();
        $stmt = $db->prepare('
            UPDATE
                friendships
            SET
                provisional = 0
            WHERE
                user_id_1 = :user_id_1
                AND user_id_2 = :user_id_2
            ;
        ');
        $stmt->execute([
            ':user_id_1' => $friend_user_id,
            ':user_id_2' => $user_id
        ]);
        $db->commit();
    } else if ($mode === 'deny') {
        $db->beginTransaction();
        $stmt = $db->prepare('
            DELETE FROM
                friendships
            WHERE
                user_id_1 = :user_id_1
                AND user_id_2 = :user_id_2
            ;
        ');
        $stmt->execute([
            ':user_id_1' => $friend_user_id,
            ':user_id_2' => $user_id
        ]);
        $db->commit();
    } else {
        throw new PictoSwapException("The \"$mode\" mode is not supported.", 400);
    }
}
