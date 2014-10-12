<?php
namespace Zend\Deployment;

use Exception;

abstract class AbstractDeployment implements Deployment
{
    protected $repo;
    protected $defaultDocRoot;

    public function __construct($repo,$defaultDocRoot)
    {
        $this->repo = $repo;
        $this->defaultDocRoot = $defaultDocRoot;
        if($this->defaultDocRoot == "") {
            throw new Exception("Default document root must not be empty!");
        }
    }

    abstract public function deploy();
}
