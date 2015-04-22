<?php
namespace Zend\Init\Steps;

use Zend\Deployment\Deployment;
use Zend\Log;
use Zend\State;
use Zend\Init\Result;
use Zend\Deployment\GitDeployment;
use Zend\Deployment\S3Deployment;
use Zend\Deployment\ZpkDeployment;
use Zend\ZSWebApiClient;

class DeploymentStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct("deployment step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO, "Starting {$this->name}");

        $state->log->log(Log::INFO, "Downloading composer");
        $composer = file_get_contents("https://getcomposer.org/composer.phar");
        file_put_contents("/usr/local/zend/bin/composer.phar", $composer);
        chmod("/usr/local/zend/bin/composer.phar", 0755);
        putenv("COMPOSER_HOME=/root");

        $restartZendServer = false;
        $restartApache = false;
        foreach ($state['ZEND_DEPLOYMENTS'] as $deployment) {
            if ($deployment['type'] == 'git') {
                $state->log->log(Log::INFO, "Deploying git application to {$deployment['path']}");
                $deploymentObj = new GitDeployment($deployment['path'], $state->log, $deployment['url'], @$deployment['relativeRoot']);
                $restartApache = true;
            } elseif ($deployment['type'] == 's3') {
                $state->log->log(Log::INFO, "Deploying S3 application from s3://{$deployment['bucket']}/{$deployment['prefix']} to {$deployment['path']}");
                $deploymentObj = new S3Deployment($deployment['path'], $state->log, @$deployment['relativeRoot'], $deployment['bucket'], $deployment['prefix'], $state['AWS_ACCESS_KEY'], $state['AWS_SECRET_KEY']);
                $restartApache = true;
            } elseif ($deployment['type'] === 'zpk') {
                $state->log->log(Log::INFO, "Deploying ZPK application to {$deployment['path']}");
                $deploymentObj = new ZpkDeployment($deployment['path'], $state->log, $deployment['url'], $deployment['name'], $deployment['params'], $state['WEB_API_KEY_NAME'], $state['WEB_API_KEY_HASH']);
                $restartZendServer = true;
            } else {
                $state->log->log(Log::ERROR, "Unknown deployment type '{$deployment['type']}'");
                continue;
            }

            if (!$deploymentObj->deploy()) {
                $state->log->log(Log::ERROR, "Failed deploying application to " . $deploymentObj->getPath());
                $state->log->log(Log::ERROR, $deploymentObj->getError());
            } else {
                $state->log->log(Log::INFO, "Successfully deployed application to " . $deploymentObj->getPath());
            }
        }

        if ($restartApache) {
            $state->log->log(Log::INFO, "Restarting apache");
            self::zendServerControl("restart-apache", $state->log);
        }

        if ($restartZendServer) {
            $state->log->log(Log::INFO, "Restarting PHP");
            $result = $this->restartPhp($state);
            if ($result instanceof Result) {
                return $result;
            }
        }

        if (count($state['ZEND_DEPLOYMENTS']) == 0) {
            $state->log->log(Log::INFO, "No applications to deploy");
        }

        if (isset($state["ZEND_DOCUMENT_ROOT"])) {
            $defaultDocumentRoot = Deployment::DEFAULT_DOCUMENT_ROOT;
            $state->log->log(Log::INFO, "Setting document root to {$defaultDocumentRoot}/{$state['ZEND_DOCUMENT_ROOT']}");
            if (is_dir("/etc/apache2")) {
                self::pregReplaceFile("|DocumentRoot {$defaultDocumentRoot}|", "DocumentRoot {$defaultDocumentRoot}/{$state['ZEND_DOCUMENT_ROOT']}", "/etc/apache2/sites-available/000-default.conf");
                self::pregReplaceFile("|DocumentRoot {$defaultDocumentRoot}|", "DocumentRoot {$defaultDocumentRoot}/{$state['ZEND_DOCUMENT_ROOT']}", "/etc/apache2/sites-available/default-ssl.conf");
            } elseif (is_dir("/etc/httpd")) {
                self::pregReplaceFile("|DocumentRoot \"{$defaultDocumentRoot}\"|", "DocumentRoot {$defaultDocumentRoot}/{$state['ZEND_DOCUMENT_ROOT']}", "/etc/httpd/conf/httpd.conf");
            }
            $state->log->log(Log::INFO, "Restarting apache");
            self::zendServerControl("restart-apache", $state->log);
        }

        $state->log->log(Log::INFO, "Finished {$this->name}");
        return new Result(Result::STATUS_SUCCESS);
    }

    protected function restartPhp(State $state)
    {
        $client = new ZSWebApiClient($state['WEB_API_KEY_NAME'], $state['WEB_API_KEY_HASH'], 420);
        $response = $client->restartPhp([
            'force' => true,
        ]);

        if (isset($response['error'])) {
            return new Result(Result::STATUS_ERROR, $response['error']['errorCode'] . ": " . $response['error']['errorMessage']);
        }

        return true;
    }
}
