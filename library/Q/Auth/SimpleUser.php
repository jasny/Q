<?php
namespace Q;

require_once 'Q/Auth.php';

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
     * Active state
     * @var boolean
     */
    public $active=true;
    
    /**
     * Timestamp when password expires (and user need to submit a new one).
     * @var int
     */
    public $expires = PHP_INT_MAX;
    
    /**
     * Groups to which the user is a member of.
     * @var array
     */
    public $groups=array();
    
    
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
        
        if (!is_array($this->groups)) $this->groups = (array)$this->groups;
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
    public function getGroups()
    {
        return (array)$this->groups;
    }
    
    
    /**
     * Check if user is in specific group(s)
     * 
     * @param string $group  Group name, multiple groups may be supplied as array
     * @param Multiple groups may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in one of the groups
     */
    public function authz($group)
    {
        $groups = is_array($group) ? $group : func_get_args();
    	$missing = array_diff($groups, $this->groups); 
    	if (!empty($missing)) throw new Authz_Exception("User '{$this->username}' is not in group" . (count($missing) == 1 ? "" : "s") . " '" . join("', '", $missing) . "'.");
    }
    
    /**
     * Check if user has one of the specified groups
     * 
     * @param string $groups  group; multiple groups may be supplied as array
     * @param Multiple groups may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in any of the $groups
     */
    public function authzAny($group)
    {
        $groups = is_array($groups) ? $groups : func_get_args();
    	$found = array_intersect($groups, $this->getgroups()); 
    	if (empty($found)) throw new Authz_Exception("User '{$this->username}' does not have any of the groups '" . join("', '", $groups) . "'.");
    }    
}
