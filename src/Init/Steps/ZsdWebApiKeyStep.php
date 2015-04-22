<?php
namespace Zend\Init\Steps;

use Zend\Log;
use Zend\State;
use Zend\Init\Result;

class ZsdWebApiKeyStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct("ZSD WebAPI key setup step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO, "Starting {$this->name}");
        exec('sqlite3 /usr/local/zend/var/db/gui.db "UPDATE GUI_WEBAPI_KEYS SET HASH = LOWER(HEX(RANDOM())) WHERE NAME=\'zend-zsd\'"');
        $state->log->log(Log::INFO, "Finished {$this->name}");
        return new Result(Result::STATUS_SUCCESS);
    }
}
