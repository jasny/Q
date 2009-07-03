<?php
namespace Q;

require_once("Q/Crypt.php");
require_once("Q/HTTP.php");

/**
 * Authenticate checking against manually set info.
 *  
 * @package Authenticate
 */
class Authenticate_Manual extends Authenticate 
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
	 * @return Authenticate_User
	 */
	public function authUser($username, $password, &$code)
	{
	    if (!($this->passwordCrypt instanceof Crypt)) $this->passwordCrypt = Crypt::with(!empty($this->passwordCrypt) ? $this->passwordCrypt : 'none');
	    
	    foreach ($this->users as $curusr) {
	        if ($curusr->username == $username) {
	            $user = new Authenticate_User($curusr);
	            break;
            }
	    }

	    if (!$user) $user = new Authenticate_User(array('username'=>$username, 'password'=>$this->passwordCrypt->encrypt($password)));
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
	 * @return Authenticate_User
	 */
	public function fetchUser($uid)
	{
	    foreach ($this->users as $curusr) {
	        if ($curusr->id == $uid) {
	            $user = new Authenticate_User($curusr);
	            break;
            }
	    }

	    if (!isset($user)) return null;
	    
	    $user->host = HTTP::clientIp();
	    return $user;
	}
}

?>