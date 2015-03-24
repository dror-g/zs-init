<?php
namespace Zend\Init\Steps;

use Zend\Config;
use Zend\Log;
use Zend\State;
use Zend\Init\Result;

class PrepareStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct("prepare step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO,"Starting {$this->name}");

        if (!isset($state['ZEND_DEPLOYMENTS'])) {
            $state['ZEND_DEPLOYMENTS'] = array();
        }

        if (isset($state['ZEND_GIT_REPO'])) {
            $deployments = $state['ZEND_DEPLOYMENTS'];
            $deployments[] = [
                'type' => 'git',
                'url' => $state['ZEND_GIT_REPO'],
                'path' => '/',
                'relativeRoot' => isset($state['ZEND_DOCUMENT_ROOT']) ? $state['ZEND_DOCUMENT_ROOT'] : '',
            ];
            $state['ZEND_DEPLOYMENTS'] = $deployments;
            unset($state['ZEND_GIT_REPO']);
        }

        if (isset($state['ZEND_S3_BUCKET'])) {
            $deployments = $state['ZEND_DEPLOYMENTS'];
            $deployments[] = [
                'type' => 's3',
                'bucket' => $state['ZEND_S3_BUCKET'],
                'prefix' => isset($state['ZEND_S3_PREFIX']) ? $state['ZEND_S3_PREFIX'] : "",
                'path' => '/',
                'relativeRoot' => isset($state['ZEND_DOCUMENT_ROOT']) ? $state['ZEND_DOCUMENT_ROOT'] : '',
            ];
            $state['ZEND_DEPLOYMENTS'] = $deployments;
            unset($state['ZEND_S3_BUCKET'], $state['ZEND_S3_PREFIX']);
        }

        if (isset($state['ZEND_ZPK'])) {
            $deployments = $state['ZEND_DEPLOYMENTS'];
            $deployments[] = [
                'type' => 'zpk',
                'url' => $state['ZEND_ZPK']['url'],
                'name' => $state['ZEND_ZPK']['name'],
                'params' => $state['ZEND_ZPK']['params'],
                'path' => '/',
            ];
            $state['ZEND_DEPLOYMENTS'] = $deployments;
            unset($state['ZEND_ZPK']);
        }

        $ip = self::getIp();
        $deployments = array();
        for ($i = 0; $i < count($state['ZEND_DEPLOYMENTS']); ++$i) {
            $deployment = $state['ZEND_DEPLOYMENTS'][$i];
            if ($deployment['type'] === "zpk" && is_array($deployment['params'])) {
                foreach ($deployment['params'] as &$value) {
                    $value = str_replace('$IP', $ip, $value);
                }
                unset($value);
            }
            $deployments[] = $deployment;
        }
        $state['ZEND_DEPLOYMENTS'] = $deployments;

        $state->log->log(Log::INFO,"Finished {$this->name}");
        return new Result(Result::STATUS_SUCCESS);
    }

    public static function getIp()
    {
        if (Config::isEc2()) {
            return file_get_contents('http://169.254.169.254/latest/meta-data/public-ipv4');
        }
        if (Config::isAzure()) {
            return exec("hostname -I");
        }
    }
}
