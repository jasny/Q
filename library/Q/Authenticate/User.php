<?php
namespace Q;

/**
 * Authenticate user info.
 * 
 * @package Authenticate
 */
class Authenticate_User
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
}

?>