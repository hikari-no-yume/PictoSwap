<?php

require_once '../include/user.php';

user_init();

function respond($obj) {
    header('Content-Type: application/json');
    echo json_encode($obj);
}

// POST requests send a JSON body with request details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = file_get_contents('php://input');
    if ($data === FALSE) {
        die("file_get_contents failed");
    }
    $data = json_decode($data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("json_decode failed");
    }
    switch ($data->action) {
        case 'new_letter':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            $user_id = user_id();
            $error = user_new_letter($user_id, $data->letter);
            if ($error === TRUE) {
                respond([
                    'error' => null
                ]);
            } else {
                respond([
                    'error' => $error
                ]);
            }
        break;
        case 'register':
            $error = user_register($data->username, $data->password);
            if ($error === TRUE) {
                respond([
                    'error' => null
                ]);
            } else {
                respond([
                    'error' => $error
                ]);
            }
        break;
        case 'login':
            $error = user_login($data->username, $data->password);
            if ($error === TRUE) {
                respond([
                    'error' => null,
                    'SID' => $SID_CONSTANT
                ]);
            } else {
                respond([
                    'error' => $error
                ]);
            }
        break;
        default:
            respond([
                'error' => "Unknown POST action: '" . $data->action . "'"
            ]);
        break;
    }
// GET requests send request details as parameters
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($_GET['action']) {
        case 'letters':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            respond([
                'letters' => [],
                'error' => null
            ]);
        break;
        default:
            respond([
                'error' => "Unknown GET action: '" . $data->action . "'"
            ]);
        break;
    }
} else {
    die("Unsupported request method: " . $_SERVER['REQUEST_METHOD']);
}
