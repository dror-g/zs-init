<?php
namespace Zend;

use Exception;

define("ZEND_CONFIG_SYSTEM_CONFIG_FILE",__DIR__ . '/../data/system.json');

class Config
{
    const SYSTEM_CONFIG_FILE=ZEND_CONFIG_SYSTEM_CONFIG_FILE;

    const DELAY_BETWEEN_RETRIES=2;
    const MAX_RETRIES=120;

    private $config;

    public function __construct(Log $log)
    {
        $this->config = [];
        if(is_file(self::SYSTEM_CONFIG_FILE)) {
            $this->config = json_decode(file_get_contents(self::SYSTEM_CONFIG_FILE),true);
        }
        $userData = self::getUserData();
        if($userData !== false) {
            $tmp = json_decode($userData,true);
            if(is_array($tmp)) {
                $this->config = array_merge($tmp,$this->config);
            } else {
                $tmp = var_export($tmp, true);
                $log->log(Log::WARNING, "Failed parsing user data ({$tmp})");
            }
        } else {
            $log->log(Log::WARNING, "Failed retrieving user data");
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

    public static function isEc2()
    {
        return gethostbyname('instance-data.ec2.internal.') != 'instance-data.ec2.internal.';
    }

    public static function isAzure()
    {
        return is_dir('/var/lib/waagent');
    }

    public static function getUserData()
    {
        if (self::isEc2()) {
            return @file_get_contents("http://169.254.169.254/latest/user-data");
        } else if (self::isAzure()) {
            for ($i = 0; $i < self::MAX_RETRIES; $i++) {
                $string = @file_get_contents('/var/lib/waagent/ovf-env.xml');
                if ($string !== false) {
                    break;
                }
                sleep(self::DELAY_BETWEEN_RETRIES);
            }
            if ($string === false) {
                return false;
            }
            $parser = xml_parser_create();
            xml_parse_into_struct($parser, file_get_contents('/var/lib/waagent/ovf-env.xml'), $values, $index);
            xml_parser_free($parser);
            return base64_decode($values[$index['CUSTOMDATA'][0]]['value']);
        }
        return false;
    }
}
