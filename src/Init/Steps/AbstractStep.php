<?php
namespace Zend\Init\Steps;

use Zend\Log;
use Zend\State;
use Zend\Init\Step;

abstract class AbstractStep implements Step
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    abstract public function execute(State $state);

    protected static function pregReplaceFile($pattern, $replacement, $filename)
    {
        $text = file_get_contents($filename);
        $text = preg_replace($pattern, $replacement, $text);
        file_put_contents($filename, $text);
    }

    protected static function rmDir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object)) {
                        self::rmDir($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    protected static function zendServerControl($action, Log $log = null)
    {
        if ($log) {
            $log->log(Log::INFO, "Executing /etc/init.d/zend-server {$action}");
        }
        exec("/etc/init.d/zend-server {$action}");
    }
}
