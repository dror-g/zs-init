<?php
namespace Zend\Init;

use Zend\State;

interface Step
{
    public function execute(State $state);
}
