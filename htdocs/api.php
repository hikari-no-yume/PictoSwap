<?php
declare(strict_types=1);

namespace ajf\PictoSwap;

require_once __DIR__ . '/../vendor/autoload.php';

initSession();

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
    if (!isUserLoggedIn()) {
        respond([
            'error' => "User is not logged in!"
        ], 403);
        exit;
    }
}

function respondOk(int $statusCode = 200) {
    respond([
        'error' => null
    ], $statusCode);
}

try {
    $db = connectDb();
    $manager = new UserManager($db);
    if (isUserLoggedIn()) {
        $user = new User($db, $manager, getSessionUserId());
    }

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
                $user->newLetter($data->letter);
                respondOk(201);
            break;
            case 'send_letter':
                ensureLoggedIn();
                $letter_id = $data->letter_id;
                $friend_ids = $data->friend_ids;
                $user->sendLetter($letter_id, $friend_ids);
                respondOk();
            break; 
            case 'add_friend':
                ensureLoggedIn();
                $user->addFriend($data->username);
                respondOk(201);
            break;
            case 'friend_request_respond':
                ensureLoggedIn();
                $user->respondToFriendRequest($data->friend_user_id, $data->mode);
                respondOk();
            break;
            case 'register':
                $manager->register($data->username, $data->password);
                respondOk(201);
            break;
            case 'change_password':
                ensureLoggedIn();
                $user->changePassword($data->new_password);
                respondOk();
            break; 
            case 'login':
                $sessionData = $manager->login($data->username, $data->password);
                startUserSession($sessionData);
                respond([
                    'error' => null,
                    'SID' => $SID_CONSTANT
                ]);
            break;
            case 'logout':
                endUserSession();
                respondOk();
            break;
            default:
                respond([
                    'error' => "Unknown POST action: '" . $data->action . "'"
                ], 404);
            break;
        }
    // GET requests send request details as parameters
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        switch ($_GET['action']) {
            case 'letters':
                ensureLoggedIn();
                $letters = $user->getReceivedLetters();
                respond([
                    'letters' => $letters,
                    'error' => null
                ]);
            break;
            case 'letter':
                ensureLoggedIn();
                $letterID = (int)$_GET['id'];
                $letter = $user->getReceivedLetter($letterID);
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
                $requests = $user->getFriendRequests();
                respond([
                    'requests' => $requests,
                    'error' => null
                ]);
            break;
            case 'get_possible_recipients':
                ensureLoggedIn();
                $letter_id = (int)$_GET['letter_id'];
                $friends = $user->getPossibleRecipients($letter_id);
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
