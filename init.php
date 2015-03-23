#!/usr/local/zend/bin/php
<?php
require(__DIR__ . '/vendor/autoload.php');

use Zend\Log;
use Zend\Init\Steps\PrepareStep;
use Zend\Init\Steps\CustomPreScriptStep;
use Zend\Init\Steps\LicenseStep;
use Zend\Init\Steps\ZsdWebApiKeyStep;
use Zend\Init\Steps\DebugSettingsStep;
use Zend\Init\Steps\ClusterJoinStep;
use Zend\Init\Steps\DeploymentStep;
use Zend\Init\Steps\CustomScriptStep;
use Zend\Init\Steps\FinishStep;
use Zend\Init\Process;

if($argc != 1) {
    die("Usage: {$argv[0]}\n");
}

ini_set("memory_limit", "1G");

$result = null;
try {
    $steps = [
        new PrepareStep(),
        new CustomPreScriptStep(),
        new LicenseStep(),
        new ZsdWebApiKeyStep(),
        new DebugSettingsStep(),
        new ClusterJoinStep(),
        new DeploymentStep(),
        new CustomScriptStep(),
        new FinishStep(),
    ];

    $process = new Process($steps);
    $result = $process->execute();
} catch(Exception $e) {
    $process->getState()->log->log(Log::ERROR,"Exception caught - {$e->getMessage()}");
    echo "Exception caught: {$e->getMessage()}\n";
}

if($result !== true) {
    echo "Error: {$result}\n";
    echo "Zend Server initialization result: failed.\n";
    exit(1);
}

echo "Zend Server initialization result: success.\n";

exit(0);
