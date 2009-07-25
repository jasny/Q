<?php
namespace Q;

require_once 'Q/Auth.php';

/**
 * Auth user info.
 * 
 * @package Auth
 */
class Auth_User
{
    /**
     * User info
     * @var array
     */
    protected $info;
    
    /**
     * Class constructor
     *
     * @param array $info  User info
     */
    public function __construct($info)
    {
        if ($info instanceof self) $info = $info->getInfo();
          elseif (is_object($info)) $info = (array)$info;
        
        $info['active'] = !empty($info['active']);
        $info['groups'] = isset($info['groups']) ? (array)$info['groups'] : array();
        $this->info = $info;
    }
    
    /**
     * Magic get method
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->info[$key]) ? $this->info[$key] : null;
    }
    
    /**
     * Get user information
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
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
    	$missing = array_diff($groups, $this->info['groups']); 
    	if (!empty($missing)) throw new Authz_Exception("User '{$this->info['username']}' is not in group" . (count($missing) == 1 ? "" : "s") . " '" . join("', '", $missing) . "'.");
    }
}
