#!/usr/local/zend/bin/php
<?php
require(__DIR__ . '/vendor/autoload.php');

use Zend\ZSWebApiClient;
use Zend\State;

if($argc != 1) {
    die("Usage: {$argv[0]}\n");
}

$state = new State();
if(isset($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH'],$state['NODE_ID'])) {
    $client = new ZSWebApiClient($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH']);
    $result = $client->clusterForceRemoveServer(['serverId' => $state['NODE_ID']]);

    echo "Zend Server remove from cluster\n";
    echo "HTTP Response code: {$result['code']}\n";
    echo "Reason: {$result['reason']}\n";
}

exit(0);
