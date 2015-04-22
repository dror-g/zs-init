<?php
namespace Zend\Init\Steps;

use Zend\Log;
use Zend\State;
use Zend\Init\Result;

class LicenseStep extends AbstractStep
{
    const LICENSE_FILE="/etc/zend.lic";

    public function __construct()
    {
        parent::__construct("license setup step");
    }

    public function execute(State $state)
    {
        $state->log->log(Log::INFO, "Starting {$this->name}");
        self::zendServerControl('stop', $state->log);
        if (is_file(self::LICENSE_FILE)) {
            $state->log->log(Log::INFO, "Analyzing license file");
            $license = json_decode(file_get_contents(self::LICENSE_FILE), true);
            if ($license == null) {
                return new StepResult(Result::STATUS_SUCCESS, "Could not parse license file");
            }

            $state->log->log(Log::INFO, "Setting up license");
            $state['ZEND_LICENSE_KEY'] = $license['ZEND_LICENSE_KEY'];
            $state['ZEND_LICENSE_ORDER'] = $license['ZEND_LICENSE_ORDER'];
            self::pregReplaceFile("/zend\\.serial_number=.*\$/", "zend.serial_number={$license['ZEND_LICENSE_KEY']}", "/usr/local/zend/etc/conf.d/ZendGlobalDirectives.ini");
            self::pregReplaceFile("/zend\\.user_name=.*\$/", "zend.user_name={$license['ZEND_LICENSE_ORDER']}", "/usr/local/zend/etc/conf.d/ZendGlobalDirectives.ini");
            exec("sqlite3 /usr/local/zend/var/db/zsd.db \"UPDATE ZSD_DIRECTIVES set DISK_VALUE='{$license['ZEND_LICENSE_KEY']}' WHERE NAME='zend.serial_number'\"");
            exec("sqlite3 /usr/local/zend/var/db/zsd.db \"UPDATE ZSD_DIRECTIVES set DISK_VALUE='{$license['ZEND_LICENSE_ORDER']}' WHERE NAME='zend.user_name'\"");

            $state->log->log(Log::INFO, "Deleting license file");
            unlink(self::LICENSE_FILE);
        }
        $state->log->log(Log::INFO, "Finished {$this->name}");
        return new Result(Result::STATUS_SUCCESS);
    }
}
