<?php
namespace Zend;

use Exception;

define("ZEND_CONFIG_SYSTEM_CONFIG_FILE",__DIR__ . '/../data/system.json');

class Config
{
    const SYSTEM_CONFIG_FILE=ZEND_CONFIG_SYSTEM_CONFIG_FILE;
    private $config;

    public function __construct()
    {
        $this->config = [];
        if(is_file(self::SYSTEM_CONFIG_FILE)) {
            $this->config = json_decode(file_get_contents(self::SYSTEM_CONFIG_FILE),true);
        }
        $userData = file_get_contents("http://169.254.169.254/latest/user-data");
        if($userData !== false) {
            $tmp = json_decode($userData,true);
            if(is_array($tmp)) {
                $this->config = array_merge($tmp,$this->config);
            }
        }
    }

    public function __get($name)
    {
        if(isset($this->config[$name])) {
            return $this->config[$name];
        }
        return null;
    }

    public function getConfig()
    {
        return $this->config;
    }
}
