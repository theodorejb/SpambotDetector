<?php

require 'SpambotDetect.php';

session_start();

/*
 * The secret key session value will only be set if the form page has already been loaded.
 * This prevents bots from getting a value from this page first and directly posting it to the form page.
 */

if (isset($_SESSION[SpambotDetect::secretKeySessionName])) {
    $secret = $_SESSION[SpambotDetect::secretKeySessionName];
    $botDetect = new SpambotDetect($secret);
    echo $botDetect->getValidKey();
}
?>