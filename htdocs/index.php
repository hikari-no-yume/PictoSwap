<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
user_init();

?><!doctype html>
<meta charset=utf-8>
<title>PictoSwap</title>
<link rel="shortcut icon" href=favicon.ico>

<link rel=stylesheet href=style.css>

<script src="jInsert.js"></script>
<script src="lib3DS.js"></script>
<script src="script/InkMeter.js"></script>
<script src="script/Canvas.js"></script>
<script src="script/ColourPicker.js"></script>
<script src=script.js></script>
<script>
window.PictoSwap = window.PictoSwap || {};
window.PictoSwap.userData = <?=json_encode([
    'logged_in' => user_logged_in(),
    'SID' => $SID_CONSTANT
])?>;
</script>
<noscript>With PictoSwap, you can draw messages and send them to your friends on other 3DS systems! This website requires JavaScript, and is best viewed in the Nintendo 3DS browser. You are seeing this message because either your browser does not support JavaScript, or it has been disabled.</noscript>
