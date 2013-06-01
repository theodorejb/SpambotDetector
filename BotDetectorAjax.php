<?php

require 'BotDetector.php';

if (isset($_POST['instance']))
    $instance = $_POST['instance'];
else
    $instance = NULL;

$botDetector = new BotDetector($instance);
echo $botDetector->getValidKey();
?>