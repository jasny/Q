<?php
namespace Q;

require_once 'Q/Auth.php';
require_once 'Q/Auth/LoginException.php';

/**
 * Auth exception for when password is expired.
 *  
 * @package Auth
 */
class Auth_ExpiredException extends Auth_LoginException
{
    /**
     * Class constructor
     * 
     * @param string|int $status  Auth status message or code
     * @param int        $code    HTTP status code
     */
    public function __construct($status=Auth::PASSWORD_EXPIRED, $code=403)
    {
        parent::__construct($status, $code);
    }
}
