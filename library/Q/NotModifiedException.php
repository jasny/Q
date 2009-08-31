<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/RestException.php';

/**
 * Method should exist, but is not yet implemented.
 * 
 * @package Exception
 */
class NotModifiedException extends Exception implements RestException
{
    /**
     * Class constructor
     * 
     * @param string $message
     * @param int    $code     HTTP status code
     */
    public function __construct($message="Not modified", $code=304)
    {
        parent::__construct($message, $code);
    }
}