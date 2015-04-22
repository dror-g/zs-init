<?php
namespace Zend\Init;

class Result
{
    const STATUS_SUCCESS="Success";
    const STATUS_ERROR="Error";

    private $status;
    private $message;

    public function __construct($status, $message = null)
    {
        $this->status = $status;
        $this->message = $message;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function hasMessage()
    {
        return isset($this->message);
    }

    public function getMessage()
    {
        return $this->message;
    }
}
