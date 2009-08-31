<?php

/**
 * Mock objects can be used instead of real objects to create a pretty interface.
 * 
 * @package Mock
 */
class Mock
{
	/**
	 * Methods.
	 * @var Closure[]
	 */
	protected $_methods;

	
    /**
     * Class constructor
     *
     * @param Closure[] $methods
     */
    public function __construct($methods)
    {
        $this->_methods = $methods;
    }

    
    /**
     * Magic get method
     *
     * @param string $key
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($key)
    {
        throw new Exception("Unable to get property {$key} of mock object.");
    }

    /**
     * Magic set method
     *
     * @param string $key
     * @param mixed  $value
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __set($key, $value)
    {
        throw new Exception("Unable to set property {$key} of mock object.");
    }
    
    /**
     * Magic call method
     *
     * @param string $function
     * @param array  $args
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __call($function, $args)
    {
        if (!isset($this->_methods[$function])) throw new Exception("Unable to call method {$function} of mock object.");
        call_user_func_array($this->_methods[$function], $args);
    }
}
