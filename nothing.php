#!/usr/local/zend/bin/php
<?php
declare(ticks = 1);
require(__DIR__ . '/vendor/autoload.php');

use Zend\Log;
use Zend\State;
use Zend\Init\Steps\ClusterJoinStep;

function sigChldHandler()
{
    while (pctnl_wait($status, WNOHANG) > 0) {
        usleep(10000);
    }
}

if ($argc != 2) {
    die("Usage: {$argv[0]} <path-to-nothing>\n");
}

pcntl_signal(SIGCHLD, 'sigChldHandler');

$nothing = $argv[1];
$log = new Log("php://stdout");

exec(__DIR__ . "/init.php", $output, $exitCode);

if ($exitCode !== 0) {
    $log->log(Log::WARNING, "Warning: failed running init.php");
}

$state = new State($log);

$nothingArgs = [];
if (isset($state['ZEND_CLUSTER_DB_HOST'], $state['ZEND_CLUSTER_DB_USER'], $state['ZEND_CLUSTER_DB_PASSWORD'], $state['NODE_ID'], $state['WEB_API_KEY_NAME'], $state['WEB_API_KEY_HASH'])) {
    $nothingArgs[] = $state['ZEND_CLUSTER_DB_HOST'];
    $nothingArgs[] = 3306;
    $nothingArgs[] = $state['ZEND_CLUSTER_DB_USER'];
    $nothingArgs[] = $state['ZEND_CLUSTER_DB_PASSWORD'];
    $nothingArgs[] = ClusterJoinStep::DB_NAME;
    $nothingArgs[] = $state['NODE_ID'];
    $nothingArgs[] = $state['WEB_API_KEY_NAME'];
    $nothingArgs[] = $state['WEB_API_KEY_HASH'];
}

$log->log(Log::INFO, "Executing nothing");
pcntl_exec($nothing, $nothingArgs);
