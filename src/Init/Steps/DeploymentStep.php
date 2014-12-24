<?php
namespace Zend\Init\Steps;

use Zend\Log;
use Zend\State;
use Zend\Init\Result;
use Zend\Deployment\GitDeployment;
use Zend\Deployment\S3Deployment;
use Zend\Deployment\ZpkDeployment;

class DeploymentStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct("deployment step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO,"Starting {$this->name}");

        $state->log->log(Log::INFO,"Downloading composer");
        $composer = file_get_contents("https://getcomposer.org/composer.phar");
        file_put_contents("/usr/local/zend/bin/composer.phar",$composer);
        chmod("/usr/local/zend/bin/composer.phar",0755);

        if (isset($state["ZEND_GIT_REPO"])) {
            $state->log->log(Log::INFO, "Initializing git deployment");
            $deployment = new GitDeployment($state["ZEND_GIT_REPO"], $state['DEFAULT_DOCUMENT_ROOT']);
        } else if (isset($state["ZEND_S3_BUCKET"])) {
            $state->log->log(Log::INFO, "Initializing AWS S3 deployment");
            if(!isset($state["ZEND_S3_PREFIX"])) {
                $state["ZEND_S3_PREFIX"] = "";
            }
            $deployment = new S3Deployment($state["ZEND_S3_BUCKET"], $state["ZEND_S3_PREFIX"], $state['DEFAULT_DOCUMENT_ROOT'], $state['AWS_ACCESS_KEY'], $state['AWS_SECRET_KEY']);
        } else if (isset($state['ZEND_ZPK'])) {
            $state->log->log(Log::INFO, "Initializing ZPK deployment");
            $deployment = new ZpkDeployment($state['ZEND_ZPK']['url'], $state['ZEND_ZPK']['name'], $state['ZEND_ZPK']['params'], $state['DEFAULT_DOCUMENT_ROOT'], $state['WEB_API_KEY_NAME'], $state['WEB_API_KEY_HASH']);
        }

        if(isset($deployment)) {
            $state->log->log(Log::INFO,"Deploying application");
            $deployment->deploy();
        }

        if(isset($state["ZEND_DOCUMENT_ROOT"])) {
            $state->log->log(Log::INFO,"Setting document root to {$state['DEFAULT_DOCUMENT_ROOT']}/{$state['ZEND_DOCUMENT_ROOT']}");
            self::pregReplaceFile("|DocumentRoot {$state['DEFAULT_DOCUMENT_ROOT']}|", "DocumentRoot {$state['DEFAULT_DOCUMENT_ROOT']}/{$state['ZEND_DOCUMENT_ROOT']}", "/etc/apache2/sites-available/000-default.conf");
            self::pregReplaceFile("|DocumentRoot {$state['DEFAULT_DOCUMENT_ROOT']}|", "DocumentRoot {$state['DEFAULT_DOCUMENT_ROOT']}/{$state['ZEND_DOCUMENT_ROOT']}", "/etc/apache2/sites-available/default-ssl.conf");
            $state->log->log(Log::INFO,"Restarting apache");
            exec("/etc/init.d/apache2 reload");
        }

        $state->log->log(Log::INFO,"Finished {$this->name}");
        return new Result(Result::STATUS_SUCCESS);
    }
}
