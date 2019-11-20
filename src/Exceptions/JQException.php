<?php


namespace sobernt\JqObject\Exceptions;


use Exception;
use Throwable;

class JQException extends Exception
{
public function __construct($message = "", $code = 0, Throwable $previous = null)
{
    if($code==0) $code=500;
    parent::__construct($message, $code, $previous);
}
}