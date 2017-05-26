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
    $_SESSION['image_auth_secret'] = \bin2hex(\random_bytes(32));
}

// Logs out the user
function endUserSession() {
    \session_destroy();
}

// Generates an HMAC code proving the logged-in user can see a letter
function generateAuthCode(int $letterId): string {
    if (!(isset($_SESSION['image_auth_secret'], $_SESSION['user_id']))) {
        error_log("Attempt to generate auth code for non-logged-in user");
        return "";
    }

    return \hash_hmac('sha256', "The user $_SESSION[user_id] can see letter $letterId", $_SESSION['image_auth_secret']);
}

// Verifies an HMAC code
function isValidAuthCode(string $authCode, int $letterId): bool {
    return \hash_equals(generateAuthCode($letterId), $authCode);
}
