<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/RestException.php';
require_once 'Q/ExpectedException.php';

/**
 * Exception when a method can't be called
 * 
 * @package Exception
 */
class InvalidMethodException extends Exception implements RestException, ExpectedException
{
    /**
     * Class constructor
     * 
     * @param string $message
     * @param int    $code     HTTP status code
     */
    public function __construct($message="Method does not exist", $code=405)
    {
        parent::__construct($message, $code);
    }
}
