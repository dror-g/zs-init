<?php
namespace Zend;

use Exception;

class Log
{
    const ERROR='Error';
    const WARNING='Warning';
    const INFO='Info';

    public function __construct($filename)
    {
        $this->fd = fopen($filename,'a');
        if(!$this->fd) {
            throw new Exception("Failed openning log file {$filename}");
        }
    }

    public function __destruct()
    {
        fclose($this->fd);
    }

    public function log($severity,$message)
    {
        if(flock($this->fd,LOCK_EX)) {
            fwrite($this->fd,"{$severity}: {$message}\n");
            fflush($this->fd);
            flock($this->fd,LOCK_UN);
        } else {
            throw new Exception("Failed acquiring exclusive lock on log file");
        }
    }
}
