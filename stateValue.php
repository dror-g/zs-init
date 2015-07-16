#!/usr/local/zend/bin/php
<?php
require(__DIR__ . '/vendor/autoload.php');

use Zend\Log;
use Zend\State;

if ($argc != 2) {
    die("Usage: {$argv[0]} <name>\n");
}

$log = new Log("php://stderr");
$state = new State($log);

$names = explode('.', $argv[1]);
$arr = $state;
for ($i = 0; $i < count($names); $i++) {
    if (is_array($arr) || $arr instanceof ArrayAccess) {
        $arr = $arr[$names[$i]];
    }
}
echo $arr . PHP_EOL;
