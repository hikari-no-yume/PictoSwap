<?php
declare(strict_types=1);

namespace ajf\PictoSwap;

function initSession() {
    global $SID_CONSTANT;

    if (isset($_GET['PHPSESSID'])) {
        \session_id($_GET['PHPSESSID']);
    }
    \session_start();
    $SID_CONSTANT = session_name() . '=' . session_id();
}

// Checks if user is logged in
function isUserLoggedIn(): bool {
    return (isset($_SESSION['logged_in']) && $_SESSION['logged_in']);
}

// Gets user ID of logged in user
function getSessionUserId(): int {
    return (int)$_SESSION['user_id'];
}

// "Logs in" the user by setting their data in the session
// Takes an array of session data (from UserManager::login())
function startUserSession(array $sessionData) {
    foreach ($sessionData as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

// Logs out the user
function endUserSession() {
    \session_destroy();
}
