<?php

require_once '../include/user.php';

user_init();

function respond($obj) {
    header('Content-Type: application/json');
    echo json_encode($obj);
}

// POST requests send a JSON body with request details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($HTTP_RAW_POST_DATA)) {
        $data = $HTTP_RAW_POST_DATA;
    } else {
        $data = file_get_contents('php://input');
    }
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
        case 'send_letter':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            $user_id = user_id();
            $letter_id = $data->letter_id;
            $friend_ids = $data->friend_ids;
            $error = user_send_letter($user_id, $letter_id, $friend_ids);
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
        case 'add_friend':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            $user_id = user_id();
            $error = user_add_friend($user_id, $data->username);
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
        case 'friend_request_respond':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            $user_id = user_id();
            $error = user_friend_request_respond($user_id, $data->friend_user_id, $data->mode);
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
        case 'change_password':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            $user_id = user_id(); 
            $error = user_change_password($user_id, $data->new_password);
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
        case 'logout':
            user_logout();
            respond([
                'error' => null
            ]);
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
            $letters = user_get_received_letters(user_id());
            respond([
                'letters' => $letters,
                'error' => null
            ]);
        break;
        case 'letter':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            $letterID = $_GET['id'];
            $letter = user_get_received_letter(user_id(), $letterID);
            if ($letter === null) {
                respond([
                    'error' => 'No such letter'
                ]);
            } else {
                respond([
                    'letter' => $letter,
                    'error' => null
                ]);
            }
        break;
        case 'get_friend_requests':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            $requests = user_get_friend_requests(user_id());
            respond([
                'requests' => $requests,
                'error' => null
            ]);
        break;
        case 'get_possible_recipients':
            if (!user_logged_in()) {
                respond([
                    'error' => "User is not logged in!"
                ]);
                exit;
            }
            $letter_id = $_GET['letter_id'];
            $friends = user_get_possible_recipients(user_id(), $letter_id);
            respond([
                'friends' => $friends,
                'error' => null
            ]);
        break;
        default:
            respond([
                'error' => "Unknown GET action: '" . $_GET['action'] . "'"
            ]);
        break;
    }
} else {
    die("Unsupported request method: " . $_SERVER['REQUEST_METHOD']);
}
