<?php
namespace Q;

require_once 'Q/Auth/Exception.php';

/**
 * Authorization exception
 * @package Auth
 */
class Authz_Exception extends Auth_Exception
{
    /**
     * Class constructor
     * 
     * @param string $message
     * @param int    $code     HTTP status code
     */
    public function __construct($message, $code=401)
    {
        parent::__construct($message, $code);
    }
}
