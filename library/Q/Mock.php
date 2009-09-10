<?php
namespace Q;

/**
 * Mock object for pretty syntax of Factory method used for a Multiton.
 *
 * @package Pattern
 */
class Mock
{
    /**
     * Target class
     * @var string
     */
    protected $_class;
    
    /**
     * Factory methods
     * @var array
     */
    protected $_methods;
    
    /**
     * Instance name
     * @var string
     */
    protected $_name;
    
    
    /**
     * Class constructor
     *
     * @param string  $class    Target class, should implement Factory and Multiton
     * @param string  $name     Instance name
     * @param array   $method   Factory method name(s)
     */
    public function __construct($class, $name, $method='with')
    {
    	$this->_class = $class;
    	$this->_methods = (array)$method;
        $this->_name = $name;
    }
    
    
    /**
     * Magic call method
     *
     * @param string $method
     * @param array  $args
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __call($method, $args)
    {
    	if (!in_array($method, $this->_methods)) {
	        if (!(call_user_func(array($this->_class, $this->_name)) instanceof Multiton_Mock)) trigger_error("Illigal use of mock object '{$this->_class}::{$this->_name}()'.", E_USER_ERROR);
	        throw new Exception("{$this->_class} interface '{$this->_name}' does not exist.");
    	}
    	
    	$instance = call_user_func_array(array($this->_class, $method), $args);
	    $instance->asInstance($this->_name);
	    
	    return $instance;
    }
    
    /**
     * Magic get method
     *
     * @param string $key
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($key)
    {
        if (!(call_user_func(array($this->_class, $this->_name)) instanceof Multiton_Mock)) trigger_error("Illigal use of mock object '{$this->_class}::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("{$this->_class} interface '{$this->_name}' does not exist.");
    }

    /**
     * Magic set method
     *
     * @param string $key
     * @param mixed  $value
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __set($key, $value)
    {
        if (!(call_user_func(array($this->_class, $this->_name)) instanceof Multiton_Mock)) trigger_error("Illigal use of mock object '{$this->_class}::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("{$this->_class} interface '{$this->_name}' does not exist.");
    }
}
