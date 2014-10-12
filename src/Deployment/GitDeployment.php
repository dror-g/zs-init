<?php
namespace Zend\Deployment;

class GitDeployment extends AbstractDeployment
{
    public function __construct($repo,$defaultDocRoot)
    {
        parent::__construct($repo,$defaultDocRoot);
    }

    public function deploy()
    {
        exec("rm -rf {$this->defaultDocRoot}/*");
        exec("git clone {$this->repo} {$this->defaultDocRoot}");
        symlink('/usr/local/zend/share/dist/dummy.php',"{$this->defaultDocRoot}/dummy.php");
    }
}
