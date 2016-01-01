<?php
declare(strict_types=1);

namespace ajf\PictoSwap;

require_once __DIR__ . '/../vendor/autoload.php';

user_init();

function respond(array $obj, int $statusCode = NULL) {
    header('Content-Type: application/json');
    if ($statusCode === NULL && isset($obj["error"])) {
        $statusCode = 400;
    }
    if ($statusCode !== NULL) {
        header('HTTP/1.1 ' . $statusCode);
    }
    echo json_encode($obj);
}

function ensureLoggedIn() {
    if (!user_logged_in()) {
        respond([
            'error' => "User is not logged in!"
        ], 403);
        exit;
    }
}

try {
    // POST requests send a JSON body with request details
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = \file_get_contents('php://input');
        if ($data === FALSE) {
            die("file_get_contents failed");
        }
        $data = \json_decode($data);
        if (\json_last_error() !== \JSON_ERROR_NONE) {
            die("json_decode failed");
        }
        switch ($data->action) {
            case 'new_letter':
                $user_id = user_id();
                user_new_letter($user_id, $data->letter);
                respond([
                    'error' => null
                ]);
            break;
            case 'send_letter':
                ensureLoggedIn();
                $user_id = user_id();
                $letter_id = $data->letter_id;
                $friend_ids = $data->friend_ids;
                user_send_letter($user_id, $letter_id, $friend_ids);
                respond([
                    'error' => null
                ]);
            break; 
            case 'add_friend':
                ensureLoggedIn();
                $user_id = user_id();
                user_add_friend($user_id, $data->username);
                respond([
                    'error' => null
                ]);
            break;
            case 'friend_request_respond':
                ensureLoggedIn();
                $user_id = user_id();
                user_friend_request_respond($user_id, $data->friend_user_id, $data->mode);
                respond([
                    'error' => null
                ]);
            break;
            case 'register':
                user_register($data->username, $data->password);
                respond([
                    'error' => null
                ]);
            break;
            case 'change_password':
                ensureLoggedIn();
                $user_id = user_id(); 
                user_change_password($user_id, $data->new_password);
                respond([
                    'error' => null
                ]);
            break; 
            case 'login':
                user_login($data->username, $data->password);
                respond([
                    'error' => null,
                    'SID' => $SID_CONSTANT
                ]);
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
                ensureLoggedIn();
                $letters = user_get_received_letters(user_id());
                respond([
                    'letters' => $letters,
                    'error' => null
                ]);
            break;
            case 'letter':
                ensureLoggedIn();
                $letterID = (int)$_GET['id'];
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
                ensureLoggedIn();
                $requests = user_get_friend_requests(user_id());
                respond([
                    'requests' => $requests,
                    'error' => null
                ]);
            break;
            case 'get_possible_recipients':
                ensureLoggedIn();
                $letter_id = (int)$_GET['letter_id'];
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
} catch (\Throwable $e) {
    if ($e instanceof PictoSwapException) {
        respond([
            'error' => $e->getMessage()
        ], $e->getStatusCode());
    } else {
        $error = "Caught error type " . $e->getCode() . ": " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n" . $e->getTraceAsString();
        respond([
            'error' => "Internal PictoSwap error.\n\n" . $error
        ], 500);
        error_log($error);
    }
}
