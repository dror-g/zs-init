#!/usr/local/zend/bin/php
<?php
require(__DIR__ . '/vendor/autoload.php');

use Zend\Log;
use Zend\State;

if ($argc != 2) {
    die("Usage: {$argv[0]} <name>\n");
}

$name = $argv[1];
$log = new Log("php://stderr");
$state = new State($log);

echo $state[$name];
