<?php
namespace Q;

require_once "Q/Auth";

/**
 * Auth checking against manually set info.
 *  
 * @package Auth
 */
class Auth_Manual extends Auth 
{
    /**
     * Available users.
     * @var object[]
     */
    public $users = array();
    

	/**
	 * Fetch user based on username.
	 * Returns authentication result
	 *
	 * @param string $username
     * @param string $password
     * @param int    $code      Output: return code
	 * @return Auth_User
	 */
	public function authUser($username, $password, &$code)
	{
	    if (!($this->passwordCrypt instanceof Crypt)) $this->passwordCrypt = Crypt::with(!empty($this->passwordCrypt) ? $this->passwordCrypt : 'none');
	    
	    foreach ($this->users as $curusr) {
	        if ($curusr->username == $username) {
	            $user = new Auth_User($curusr);
	            break;
            }
	    }

	    if (!$user) $user = new Auth_User(array('username'=>$username, 'password'=>$this->passwordCrypt->encrypt($password)));
	    $user->host = HTTP::clientIp();
	    
        if ($user->id === null) $code = self::UNKNOWN_USER;
	      elseif ($user->password != $this->passwordCrypt->encrypt($password, $user->password)) $code = self::INCORRECT_PASSWORD;
          elseif (!$user->active) $code = self::INACTIVE_USER;
          elseif ($user->expire && $user->expire < time()) $code = self::PASSWORD_EXPIRED;
        
	    return $user; 
    }
	
	/**
	 * Fetch user based on id.
	 *
	 * @param int|string $uid
	 * @return Auth_User
	 */
	public function fetchUser($uid)
	{
	    foreach ($this->users as $curusr) {
	        if ($curusr->id == $uid) {
	            $user = new Auth_User($curusr);
	            break;
            }
	    }

	    if (!isset($user)) return null;
	    
	    $user->host = HTTP::clientIp();
	    return $user;
	}
}

