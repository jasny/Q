<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/ExpectedException.php';
require_once 'Q/RestException.php';

/**
 * Base class for Auth exceptions
 * 
 * @package Auth
 */
abstract class Auth_Exception extends Exception implements RestException, ExpectedException
{
    /**
     * Class constructor
     * 
     * @param string|int $status  Auth status message or code
     * @param int        $code    HTTP status code
     */
    public function __construct($status, $code=403)
    {
        if (is_int($status)) $status = Auth::getMessage($status);
        parent::__construct($status, $code);
    }
}