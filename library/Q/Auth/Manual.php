<?php
namespace Q;

require_once "Q/Auth.php";
require_once "Q/Auth/SimpleUser.php";

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
	 * May return an Auth::UNKNOWN_USER or Auth::INCORRECT_PASSWORD (int) is login failed.
	 *
	 * @param string $username
     * @param string $password
	 * @return Auth_SimpleUser
	 */
	public function authUser($username, $password)
	{
	    $user = $this->doFetchUser('username', $username);

	    if (!isset($user)) return Auth::UNKNOWN_USER;
        if ($user->password != $this->encryptPassword($password, $user->password)) return Auth::INCORRECT_PASSWORD;
        
	    return $user; 
    }
	
    /**
	 * Fetch user.
	 *
	 * @param string $field  Criteria field
	 * @param mixed  $value  Criteria value
	 * @return Auth_SimpleUser
	 */
    protected function doFetchUser($field, $value)
    {
	    foreach ($this->users as $curusr) {
	        if ($curusr->$field == $value) {
	            $user = new Auth_SimpleUser($curusr);
	            break;
            }
	    }

	    return isset($user) ? $user : null;
    }	
    
    /**
	 * Fetch user based on id.
	 *
	 * @param int|string $uid
	 * @return Auth_SimpleUser
	 */
	public function fetchUser($uid)
	{
	    return $this->doFetchUser('id', $uid);
	}
	
	/**
	 * Fetch user based on username.
	 *
	 * @param string $username
	 * @return Auth_SimpleUser
	 */
	public function fetchUserByName($username)
	{
		return $this->doFetchUser('username', $username);
	}	
}
