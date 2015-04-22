<?php
namespace Zend\Init;

use Zend\Log;
use Zend\State;
use Zend\Init\Step;
use Exception;

class Process
{
    const LOG_FILENAME="/var/log/zs-init.log";

    private $state;
    private $steps;

    public function __construct(array $steps)
    {
        foreach ($steps as $step) {
            if (!($step instanceof Step)) {
                throw new Exception("At least one of initialization steps is invalid");
            }
        }
        $log = new Log(self::LOG_FILENAME);
        $this->state = new State($log);
        $this->steps = $steps;
    }

    public function execute()
    {
        $success = true;
        foreach ($this->steps as $step) {
            $result = $step->execute($this->state);
            if ($result->getStatus() != Result::STATUS_SUCCESS) {
                $this->state->log->log(Log::ERROR, $result->getMessage());
                break;
            } elseif ($result->hasMessage()) {
                $this->state->log->log(Log::WARNING, $result->getMessage());
            }
        }
        unset($this->state->log);
        if (!$success) {
            return $result->getMessage();
        }
        return true;
    }

    public function getState()
    {
        return $this->state;
    }
}
