<?php
namespace Zend\Init\Steps;

use Zend\Log;
use Zend\State;
use Zend\Init\Result;

class FinishStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct("finish step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO,"Starting {$this->name}");
        unset($state['ZEND_LICENSE_KEY']);
        unset($state['ZEND_LICENSE_ORDER']);
        $state->log->log(Log::INFO,"Finished {$this->name}");
        return new Result(Result::STATUS_SUCCESS);
    }
}
