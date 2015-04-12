#!/usr/local/zend/bin/php
<?php
require(__DIR__ . '/vendor/autoload.php');

use Zend\ZSWebApiClient;
use Zend\State;
use Zend\Log;

if($argc != 1) {
    die("Usage: {$argv[0]}\n");
}

$log = new Log('/var/log/zs-shutdown.log');
$state = new State($log);
if(isset($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH'],$state['NODE_ID'])) {
    $log->log(Log::INFO, "Removing server from cluster");
    $client = new ZSWebApiClient($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH']);
    $result = $client->clusterRemoveServer(['serverId' => $state['NODE_ID']]);
    $log->log(Log::INFO, "Zend Server remove from cluster WebAPI response:\nHTTP Response code: {$result['code']}\nReason: {$result['reason']}\n");
    unset($state['NODE_ID']);
}

exit(0);
