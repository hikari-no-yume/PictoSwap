<?php

require_once 'db.php';
require_once 'password_compat.php';
require_once 'graphics.php';

// Initialises session etc.
function user_init() {
    global $SID_CONSTANT;

    if (isset($_GET['PHPSESSID'])) {
        session_id($_GET['PHPSESSID']);
    }
    session_start();
    $SID_CONSTANT = session_name() . '=' . session_id();
}

// Checks if user is logged in
function user_logged_in() {
    return (isset($_SESSION['logged_in']) && $_SESSION['logged_in']);
}

// Gets user ID of logged in user
function user_id() {
    return $_SESSION['user_id'];
}

// Finds out if a user with the given username exists
function user_exists($username) {
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
    return !!($row['COUNT(*)']);
}

// Registers a user
// Return value of TRUE indicates success, otherwise string error returned
function user_register($username, $password) {
    if (user_exists($username)) {
        return "There is already a user with that username";
    }

    if (!preg_match(VALID_USERNAME_REGEX, $username)) {
        return "Username can only be 3-18 characters in length, composed only of lowercase letters, numbers and underscores";
    }

    $db = connectDB();
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

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
function user_login($username, $password) {
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
    if (!password_verify($password, $password_hash)) {
        return "Incorrect password.";
    }
    $_SESSION['logged_in'] = TRUE;
    $_SESSION['user_id'] = $rows[0]['user_id'];
    $_SESSION['username'] = $username;
    return TRUE;
}

// Adds a new letter for a user
// Return value of TRUE indicated success, otherwise string error returned
function user_new_letter($user_id, $letter) {
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
        ImagePNG($images[$i], 'previews/' . $letter_id . '-' . $i . '.png');
        ImageDestroy($images[$i]);
    }
    return TRUE;
}

// Gets array of user's letters, most recent first
function user_get_received_letters($user_id) {
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
    foreach ($letters as &$letter) {
        $letter['own'] = ((int)$letter['from_id'] === (int)user_id());
    }
    return $letters;
}

// Gets a letter received by a user
function user_get_received_letter($user_id, $letter_id) {
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
        $letter['own'] = ((int)$letter['from_id'] === (int)user_id());
        $letter['content'] = json_decode($letter['content']);
        return $letter;
    }
}
