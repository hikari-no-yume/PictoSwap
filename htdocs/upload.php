<?php

$data = file_get_contents('php://input');
if ($data === FALSE) {
    die("file_get_contents failed");
}
$data = json_decode($data);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("json_decode failed");
}

switch ($data->action) {
    case 'new_message':
        echo "Save message here";
    break;
    default:
        die("Unknown action: '" . $data->action . "'");
    break;
}
