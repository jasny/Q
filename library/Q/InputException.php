<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/RestException.php';
require_once 'Q/ExpectedException.php';

/**
 * Exception for invalid user data.
 */
class InputException extends Exception implements RestException, ExpectedException
{
    /**
     * Class constructor
     * 
     * @param string $message
     * @param int    $code     HTTP status code
     */
    public function __construct($message, $code=400)
    {
        parent::__construct($message, $code);
    }
}
