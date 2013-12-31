<?php

require_once 'db.php';
require_once 'password_compat.php';

// Initialises session etc.
function user_init() {
    global $SID_CONSTANT;

    if (isset($_GET['PHPSESSID'])) {
        session_id($_GET['PHPSESSID']);
    }
    session_start();
    $SID_CONSTANT = session_name() . '=' . session_id();
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
        return "Username can only be 3-18 characters in length, composed only of letters, numbers and underscores";
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

// Checks if user is logged in
function user_logged_in() {
    return (isset($_SESSION['logged_in']) && $_SESSION['logged_in']);
}
