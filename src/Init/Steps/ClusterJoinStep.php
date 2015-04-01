<?php
namespace Zend\Init\Steps;

use Zend\Log;
use Zend\State;
use Zend\ZSWebApiClient;
use Zend\Init\Result;
use Exception;

class ClusterJoinStep extends AbstractStep
{
    const DB_NAME='ZendServer';

    public function __construct()
    {
        parent::__construct("cluster join step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO,"Starting {$this->name}");
        if(isset($state['ZEND_ADMIN_PASSWORD'])) {
            $state->log->log(Log::INFO,"Deleting AWS HTTP authentication module");
            $this->deleteAmazonAuth();

            $state->log->log(Log::INFO,"Restarting Zend Server lighttpd");
            self::zendServerControl("restart-lighttpd",$state->log);

            $this->waitForZendServer();

            $state->log->log(Log::INFO,"Bootstrapping Zend Server");
            $result = $this->bootstrapSingleServer($state);
            if($result instanceof Result) {
                return $result;
            }

            $state->log->log(Log::INFO,"Restarting PHP");
            $result = $this->restartPhp($state);
            if($result instanceof Result) {
                return $result;
            }
        }

        if(isset($state['ZEND_ADMIN_PASSWORD'],$state['ZEND_CLUSTER_DB_HOST'],$state['ZEND_CLUSTER_DB_USER'],$state['ZEND_CLUSTER_DB_PASSWORD'])) {
            $state->log->log(Log::INFO,"Setting Session Clustering settings");
            $result = $this->storeDirective($state,['zend_sc.ha.use_broadcast' => 0]);
            if($result instanceof Result) {
                return $result;
            }

            $state->log->log(Log::INFO,"Joining Zend Cluster");
            $result = $this->serverAddToCluster($state);
            if($result instanceof Result) {
                return $result;
            }

            if($state['NODE_ID'] == 1) {
                $state->log->log(Log::INFO,"Setting PHP session save handler to Session Clustering");
                $result = $this->storeDirective($state,['session.save_handler' => 'cluster']);
                if($result instanceof Result) {
                    return $result;
                }

                $state->log->log(Log::INFO,"Restarting PHP");
                $result = $this->restartPhp($state);
                if($result instanceof Result) {
                    return $result;
                }
            }
        }

        $state->log->log(Log::INFO,"Finished {$this->name}");

        return new Result(Result::STATUS_SUCCESS);
    }

    protected function deleteAmazonAuth()
    {
        if (is_dir('/usr/local/zend/gui/3rdparty/AmazonHttpAuth')) {
            self::rmDir('/usr/local/zend/gui/3rdparty/AmazonHttpAuth');
            unlink('/usr/local/zend/gui/3rdparty/modules.config.php');
            rename('/usr/local/zend/gui/3rdparty/modules.config.php.old', '/usr/local/zend/gui/3rdparty/modules.config.php');
        }
    }

    protected function bootstrapSingleServer(State $state)
    {
        $client = new ZSWebApiClient($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH']);
        $production = true;
        if ($state['EDITION'] == "developer" || (isset($state['ZEND_BOOTSTRAP_PRODUCTION']) && $state['ZEND_BOOTSTRAP_PRODUCTION'] === false)) {
            $production = false;
        }
        $response = $client->bootstrapSingleServer([
            'adminPassword' => $state['ZEND_ADMIN_PASSWORD'],
            'production' => $production,
            'orderNumber' => $state['ZEND_LICENSE_ORDER'],
            'licenseKey' => $state['ZEND_LICENSE_KEY'],
            'acceptEula' => true,
        ]);

        if(isset($response['error'])) {
            return new Result(Result::STATUS_ERROR,$response['error']['errorCode'] . ": " . $response['error']['errorMessage']);
        }

        $state['WEB_API_KEY_NAME'] = $response['data']['key']['name'];
        $state['WEB_API_KEY_HASH'] = $response['data']['key']['hash'];
        if(!$response['data']['success']) {
            while(!($result = $this->tasksComplete($state))) {
                $strResult = $result ? "true" : "false";
                $state->log->log(Log::INFO, "Waiting for ZS tasks to complete (last result: {$strResult})");
                sleep(3);
            }
        }
        return true;
    }

    protected function tasksComplete(State $state)
    {
        $client = new ZSWebApiClient($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH']);
        $response = $client->tasksComplete([], [], true);
        return $response['data']['tasksComplete'];
    }

    protected function storeDirective(State $state,$directives)
    {
        $client = new ZSWebApiClient($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH']);
        $response = $client->configurationStoreDirectives([
            'directives' => $directives,
        ]);

        if(isset($response['error'])) {
            return new Result(Result::STATUS_ERROR,$response['error']['errorCode'] . ": " . $response['error']['errorMessage']);
        }

        return true;
    }

    protected function serverAddToCluster(State $state)
    {
        $client = new ZSWebApiClient($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH'],420);
        $response = $client->serverAddToCluster([
            'serverName' => gethostname(),
            'nodeIp' => gethostbyname(gethostname()),
            'dbHost' => $state['ZEND_CLUSTER_DB_HOST'],
            'dbUsername' => $state['ZEND_CLUSTER_DB_USER'],
            'dbPassword' => $state['ZEND_CLUSTER_DB_PASSWORD'],
            'dbName' => self::DB_NAME,
        ]);

        if(isset($response['error'])) {
            return new Result(Result::STATUS_ERROR,$response['error']['errorCode'] . ": " . $response['error']['errorMessage']);
        }

        if($response['data']['serversInfo']) {
            $state['NODE_ID'] = $response['data']['serversInfo']['id'];
        }
        if($response['data']['clusterAdminKey']) {
            $state['WEB_API_KEY_NAME'] = $response['data']['clusterAdminKey']['name'];
            $state['WEB_API_KEY_HASH'] = $response['data']['clusterAdminKey']['hash'];
        }

        return true;
    }

    protected function restartPhp(State $state)
    {
        $client = new ZSWebApiClient($state['WEB_API_KEY_NAME'],$state['WEB_API_KEY_HASH'],420);
        $response = $client->restartPhp([
            'force' => true,
        ]);

        if(isset($response['error'])) {
            return new Result(Result::STATUS_ERROR,$response['error']['errorCode'] . ": " . $response['error']['errorMessage']);
        }

        return true;
    }

    protected function waitForZendServer()
    {
        $headers = false;
        while (!is_array($headers)) {
            sleep(1);
            $headers = @get_headers("http://127.0.0.1:10081/ZendServer");
        }
        return;
    }
}
