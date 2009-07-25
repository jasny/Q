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
     * @param string $group  Group name, multiple groups may be supplied
     * @throws Authorization_Exception if the user is not in one of the groups
     */
    public function authzFor($group)
    {
    	$missing = array_diff(func_get_args(), $this->info['groups']); 
    	if (!empty($missing)) throw new Auth_Exception("Not in group" . (count($missing) == 1 ? "" : "s") . " '" . join("', '", $missing));
    }
}
