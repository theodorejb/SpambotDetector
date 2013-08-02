<?php

require 'SpambotDetect.php';

session_start();

if (isset($_SESSION[SpambotDetect::secretKeySessionName])) {
    $secret = $_SESSION[SpambotDetect::secretKeySessionName];
    $botDetect = new SpambotDetect($secret);
    echo $botDetect->getValidKey();
}
?>