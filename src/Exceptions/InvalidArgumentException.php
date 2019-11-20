<?php
namespace sobernt\JqObject\Exceptions;
use Exception;
use Throwable;

class InvalidArgumentException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        if($code==0) $code=404;
        parent::__construct($message, $code, $previous);
    }
}