<?php
namespace Zend\Init\Steps;

use Zend\Log;
use Zend\State;
use Zend\Init\Result;

class DebugSettingsStep extends AbstractStep
{
    public function __construct()
    {
        parent::__construct("debug settings step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO,"Starting {$this->name}");
        if(isset($state["ZEND_DEBUG"]) && $state["ZEND_DEBUG"] === true) {
            $state->log->log(Log::INFO,"Setting logging level to debug in ini files");
            self::pregReplaceFile('/zend_gui.logVerbosity\\s*=.*$/m', "zend_gui.logVerbosity = DEBUG", "/usr/local/zend/gui/config/zs_ui.ini");
            self::pregReplaceFile('/zend_gui.debugModeEnabled\\s*=.*$/m', 'zend_gui.debugModeEnabled = true', "/usr/local/zend/gui/config/zs_ui.ini");
            self::pregReplaceFile('/zend_deployment.daemon.log_verbosity_level\\s*=.*$/m', 'zend_deployment.daemon.log_verbosity_level=5', "/usr/local/zend/etc/zdd.ini");
            self::pregReplaceFile('/zend_server_daemon.log_verbosity_level\\s*=.*$/m', 'zend_server_daemon.log_verbosity_level=5', "/usr/local/zend/etc/zsd.ini");
        }

        $state->log->log(Log::INFO,"Cleaning semaphores");
        exec("rm -rf /usr/local/zend/tmp/zsemfile_*");
        exec("rm -rf /usr/local/zend/tmp/zshm_*");
        exec("/usr/local/zend/bin/clean_semaphores.sh");

        self::zendServerControl('start',$state->log);
        $state->log->log(Log::INFO,"Finished {$this->name}");
        return new Result(Result::STATUS_SUCCESS);
    }
}
