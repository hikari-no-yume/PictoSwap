<?php

function connectDB() {
    static $PDO = null;
    if ($PDO === null) {
        $PDO = new PDO('sqlite:../pictoswap.sqlite');
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $PDO;
}
