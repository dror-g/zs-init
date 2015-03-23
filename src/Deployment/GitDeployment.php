<?php
namespace Zend\Deployment;

use Zend\Log;

class GitDeployment extends AbstractDeployment
{
    private $url;

    public function __construct($path, Log $log, $url, $relativeRoot)
    {
        parent::__construct($path, $log, false, $relativeRoot);
        $this->url = $url;
    }

    public function deploy()
    {
        $this->cleanDeploymentDir();
        exec("git clone {$this->url} {$this->deploymentDir} 2>&1", $output, $exitCode);
        $this->fixDummyPhp();
        $this->runComposer();
        $this->updateApacheConfig();
        return true;
    }
}
