<?php
namespace Q;

require_once 'Q/Auth/User.php';
require_once 'Q/Authz/Exception.php';

/**
 * Auth user info.
 * Other properties than the ones defined in the class may be created by the Auth interface.
 * 
 * @package Auth
 */
class Auth_SimpleUser implements Auth_User
{
    /**
     * User id
     * @var int|string
     */
    public $id;
    
    /**
     * Username
     * @var string
     */
    public $username;
    
    /**
     * Hashed password
     * @var string
     */
    public $password;
    
    /**
     * Full name of the user
     * @var string
     */
    public $fullname;
    
    /**
     * Full name of the user
     * @var string
     */
    public $email;
    
    /**
     * Active state
     * @var boolean
     */
    public $active=true;
    
    /**
     * Timestamp when password expires (and user need to submit a new one).
     * @var int
     */
    public $expires;
    
    /**
     * Roles to which the user is a member of.
     * @var array
     */
    public $roles;
    
    
    /**
     * Class constructor
     *
     * @param array $info  User info
     */
    public function __construct($info)
    {
        foreach ($info as $prop=>$value) {
            $this->$prop = $value;
        }
        
        if (!isset($this->expires)) $this->expires = PHP_INT_MAX;
    }
    
    
    /**
     * Get the user id for Auth
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Get the username
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get the hashed password
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Get the username
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Get the username
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Check if user is still active
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Get date when password expires
     * @return int
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Get date when password expires
     * @return int
     */
    public function getRoles()
    {
        return (array)$this->roles;
    }
    
    
    /**
     * Check if user is in specific role(s)
     * 
     * @param string $role  Role name, multiple roles may be supplied as array
     * @param Multiple roles may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in one of the roles
     */
    public function authz($role)
    {
        $roles = is_array($role) ? $role : func_get_args();
    	$missing = array_diff($roles, (array)$this->roles); 
    	if (!empty($missing)) throw new Authz_Exception("User '{$this->username}' is not in role" . (count($missing) == 1 ? "" : "s") . " '" . join("', '", $missing) . "'.");
    }
    
    /**
     * Check if user has one of the specified roles
     * 
     * @param string $roles  role; multiple roles may be supplied as array
     * @param Multiple roles may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in any of the $roles
     */
    public function authzAny($role)
    {
        $roles = is_array($roles) ? $roles : func_get_args();
    	$found = array_intersect($roles, $this->getroles()); 
    	if (empty($found)) throw new Authz_Exception("User '{$this->username}' does not have any of the roles '" . join("', '", $roles) . "'.");
    }    
}
