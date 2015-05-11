<?php
namespace Zend;

use Exception;

define("ZEND_CONFIG_SYSTEM_CONFIG_FILE", __DIR__ . '/../data/system.json');

class Config
{
    const SYSTEM_CONFIG_FILE=ZEND_CONFIG_SYSTEM_CONFIG_FILE;

    const DELAY_BETWEEN_RETRIES=2;
    const MAX_RETRIES=120;

    private $config;

    public function __construct(Log $log)
    {
        $this->config = [];
        if (is_file(self::SYSTEM_CONFIG_FILE)) {
            $this->config = json_decode(file_get_contents(self::SYSTEM_CONFIG_FILE), true);
        }
        $userData = self::getUserData();
        if ($userData !== false) {
            $tmp = json_decode($userData, true);
            if (is_array($tmp)) {
                $this->config = array_merge($tmp, $this->config);
            } else {
                $tmp = var_export($tmp, true);
                $error = json_last_error_msg();
                $log->log(Log::WARNING, "Failed parsing user data (retval: {$tmp}, error: {$error})");
            }
        } else {
            $log->log(Log::WARNING, "Failed retrieving user data");
        }
    }

    public function __get($name)
    {
        if (isset($this->config[$name])) {
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
        $c = stream_context_create([
            'http'=> [
                'timeout' => 3,
            ]
        ]);
        return file_get_contents('http://169.254.169.254/', false, $c) !== false;
    }

    public static function isGoogleComputeEngine()
    {
        return gethostbyname('metadata.google.internal.') != 'metadata.google.internal.';
    }

    public static function isAzure()
    {
        return is_dir('/var/lib/waagent');
    }

    public static function isDocker()
    {
        return file_exists('/.dockerenv');
    }

    public static function getUserData()
    {
        if (self::isEc2()) {
            return @file_get_contents("http://169.254.169.254/latest/user-data");
        } elseif (self::isAzure()) {
            for ($i = 0; $i < self::MAX_RETRIES; $i++) {
                $string = @file_get_contents('/var/lib/waagent/ovf-env.xml');
                if ($string !== false) {
                    $parser = xml_parser_create();
                    xml_parse_into_struct($parser, $string, $values, $index);
                    xml_parser_free($parser);
                    if (isset($index['CUSTOMDATA']) && isset($index['CUSTOMDATA'][0])) {
                        return base64_decode($values[$index['CUSTOMDATA'][0]]['value']);
                    }
                }
                sleep(self::DELAY_BETWEEN_RETRIES);
            }
            return false;
        } elseif (self::isGoogleComputeEngine()) {
            $opts = [
                'http' => [
                    'method' => "GET",
                    'header' => "Metadata-Flavor: Google\r\n"
                ]
            ];
            $context = stream_context_create($opts);
            return @file_get_contents("http://metadata.google.internal/computeMetadata/v1/instance/attributes/zend", false, $context);
        } elseif (self::isDocker()) {
	    $arr = [];
	    foreach($_SERVER as $key => $value) {
        	if(preg_match('/^ZEND/',$key)){
        	    $arr[$key] = $value;
        	}
	    }
	    return json_encode($arr);
	}
        return false;
    }
}
