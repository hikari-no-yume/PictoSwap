<?php
declare(strict_types=1);

namespace ajf\PictoSwap;

use PDO;

// Initialises session etc.
function user_init() {
    global $SID_CONSTANT;

    if (isset($_GET['PHPSESSID'])) {
        \session_id($_GET['PHPSESSID']);
    }
    \session_start();
    $SID_CONSTANT = session_name() . '=' . session_id();
}

// Checks if user is logged in
function user_logged_in(): bool {
    return (isset($_SESSION['logged_in']) && $_SESSION['logged_in']);
}

// Gets user ID of logged in user
function user_id(): int {
    return (int)$_SESSION['user_id'];
}

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
// Return value of TRUE indicates success, otherwise string error returned
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

    return TRUE;
}

// Registers a user
// Return value of TRUE indicates success, otherwise string error returned
function user_register(string $username, string $password) {
    if (user_exists($username)) {
        return "There is already a user with that username";
    }

    if (!\preg_match(VALID_USERNAME_REGEX, $username)) {
        return "Username can only be 3-18 characters in length, composed only of lowercase letters, numbers and underscores";
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

    return TRUE;
}

// Logs in a user
// Return value of TRUE indicates success, otherwise string error returned
function user_login(string $username, string $password) {
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
        return "No such user.";
    }
    $password_hash = $rows[0]['password_hash'];
    if (!\password_verify($password, $password_hash)) {
        return "Incorrect password.";
    }
    $_SESSION['logged_in'] = TRUE;
    $_SESSION['user_id'] = (int)$rows[0]['user_id'];
    $_SESSION['username'] = $username;
    return TRUE;
}

// Logs out the user
function user_logout() {
    \session_destroy();
}

// Sends a user's letter to the specified recipients
// Return value of TRUE indicates success, otherwise string error returned
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
        return "There is no such letter with that ID owned by you.";
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

    return TRUE;
}

// Adds a new letter for a user
// Return value of TRUE indicated success, otherwise string error returned
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
    return TRUE;
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
            'own'           => (bool)((int)$letter['from_id'] === user_id())
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
        return null;
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
            'own'               => (bool)((int)$letter['from_id'] === user_id()),
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
        return "No such user.";
    }
    
    if ($friend_user_id == $user_id) {
        return "You cannot send a friend request to yourself.";
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
                return "You already sent a friend request to that user.";
            } else {
                return "That user already sent you a friend request.";
            }
        } else {
            return "You are already friends with that user.";
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

    return TRUE;
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
// Return value of TRUE indicates success, otherwise string error returned
function user_friend_request_respond(int $user_id, int $friend_user_id, string $mode) {
    $db = connectDB();
    
    // Check user exists
    if (!user_id_exists($friend_user_id)) {
        return "No such user.";
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
        return "No such friend request.";
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
    
        return TRUE;
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

        return TRUE;
    } else {
        return "The \"$mode\" mode is not supported.";
    }
}
