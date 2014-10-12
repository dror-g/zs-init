<?php
namespace Zend;

use ArrayObject;
use ArrayAccess;
use Exception;

define("ZEND_STATE_STATE_FILE",__DIR__ . '/../data/state');

class State implements ArrayAccess
{
    const STATE_FILE=ZEND_STATE_STATE_FILE;
    protected $state;

    public function __construct()
    {
        $state = "";
        if(is_file(self::STATE_FILE)) {
            $state = json_decode(file_get_contents(self::STATE_FILE),true);
        }
        if(!is_array($state)) {
            $config = new Config();
            $state = $config->getConfig();
        }

        $this->state = new ArrayObject($state,ArrayObject::ARRAY_AS_PROPS);
    }

    public function __destruct()
    {
        file_put_contents(self::STATE_FILE,json_encode($this->state));
    }

    public function merge(array $parameters)
    {
        $this->state->exchangeArray($parameters + $this->state->getArrayCopy());
    }

    public function __get($name)
    {
        if(isset($this->state[$name])) {
            return $this->state[$name];
        }
        return null;
    }

    public function __set($name,$value)
    {
        $this->state[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->state[$name]);
    }

    public function __unset($name)
    {
        unset($this->state[$name]);
    }

    public function offsetExists($offset)
    {
        return isset($this->state[$offset]);
    }

    public function offsetGet($offset)
    {
        if(isset($this->state[$offset])) {
            return $this->state[$offset];
        }
        return null;
    }

    public function offsetSet($offset, $value)
    {
        $this->state[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->state[$offset]);
    }
}
