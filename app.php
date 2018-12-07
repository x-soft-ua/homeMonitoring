#!/usr/bin/php
<?php

require(__DIR__ . '/vendor/autoload.php');

if (count($_SERVER['argv']) < 3) {
    throw new \Exception('invalid args count');
}
$cl = $_SERVER['argv'][1];
$cl = '\app\\' . ucfirst($cl);

$coreApp = new $cl();
$coreApp->init();

