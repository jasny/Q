<?php

namespace Q;

require_once "Q/Exception.php";

/**
 * Hold static (config) properties for a class, wich may set it's configuration when it is loaded.
 * This prevents loading classes that are not used.
 *
 * Use ClassConfig::forClass(classname) to create a bin.
 * Don't use virtual properties or the virtual return of a call as property or argument. This will result in unexpected behaviour.
 * 
 * @package ClassConfig
 */
class ClassConfig implements \ArrayAccess
{
	/**
	 * Registered instances.
	 * @var array
	 */
	static protected $_instances=array();

	
	/**
	 * The registered values set using array access.
	 * @var array
	 */
	protected $_values=null;
	
	/**
	 * The registered properties.
	 * @var array
	 */
	protected $_properties=array();

	/**
	 * The registered function calls.
	 * @var array
	 */
	protected $_calls=array();


	/**
	 * Class constructor.
	 */	
	protected function __construct() { }
	
	/**
	 * Create an object for a class or return if already created (factory method).
	 *
	 * @param string $class  Class name
	 * @return ClassConfig
	 */
	public static function forClass($class)
	{
	    if (class_exists($class, false)) throw new Exception("Can't use class config for '$class': Class is already loaded.");
		
	    if (!isset(self::$_instances[$class])) self::$_instances[$class] = new self();
        return self::$_instances[$key];		
	}
	
	/**
	 * Set properties and call methods for class.
	 *
	 * @param string $class  Class name
	 */
	public function applyToClass($class)
	{
	    if (!isset(self::$_instances[$class])) return;

        $ref = new \ReflectionClass($class);
        
        foreach ($this->_properties as $key=>$value) {
		    if ($value instanceof self) $value = $value->applyToValue($ref->getStaticPropertyValue($key));
		    $ref->setStaticPropertyValue($key, $value);
		}
		
		foreach ($this->_calls as $call) {
			$result = call_user_func_array(array($class, $call->function), $call->args);
			if (is_object($result)) $call->return->applyToValue($result);
		}
	}

	/**
	 * Set array items, properties and call methods for value.
	 *
	 * @param string $target
	 */
	protected function applyToValue(&$target)
	{
	    if (isset($this->_values)) {
	        if (!isset($target)) $target = array();
	    
            foreach ($this->_values as $key=>$value) {
    		    if ($value instanceof self) {
    		        $target[$key] = null;
    		        $this->applyToValue($target[$key], $value);
    		    } else {
    		        $target[$key] = $value;
    		    }
    		}
	    }
	    
	    foreach ($this->_properties as $key=>$value) {
		    if ($value instanceof self) $this->applyToValue($target->$key, $value);
		      else $target->$key = $value;
        }
		
		foreach ($this->_calls as $call) {
			$result = call_user_func_array(array($target, $call->function), $call->args);
			if (is_object($result)) $call->return->applyToValue($result);
		}
	}
	
	
	/**
	 * Save a property which should be set.
	 *
	 * @param string $key    The name of the property. 
	 * @param mixed  $value
	 */
	public function __set($key, $value)
	{
	    $this->_properties[$key] = $value;
	}

	/**
	 * Save a property which should be set.
	 *
	 * @param string $property  The name of the property. 
	 * @param mixed  $value
	 */
	public function __get($key, $value)
	{
	    if (!isset($this->_properties[$key])) $this->_properties[$key] = new self();
	    return $this;
	}
	
	/**
	 * Register a method that should be called.
	 *
	 * @param string $function  The method name
	 * @param array  $args
	 * @return ClassConfig
	 */
	public function __call($function, $args)
	{
	    $return = new self();
		$this->_calls[] = (object)array('function'=>$function, 'args'=>$args, 'return'=>$return);

	    return $return;
	}
	
	
	/**
	 * Check whether the offset exists.
	 * Needed for ArrayAccess
	 *
	 * @param string $key
	 */
	public function offsetExists($key)
	{
	    return array_key_exists($key, $this->_values);
	}

	/**
	 * Get a value from this virtual array.
	 * Needed for ArrayAccess
	 *
	 * @param string $key
	 */
	public function offsetGet($key)
	{
	    if (!isset($this->_values[$key])) $this->_values[$key] = new self();
	    return $this->_values[$key];
	}
	
	/**
	 * Set a value in this virtual array.
	 * Needed for ArrayAccess 
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function offsetSet($key, $value)
	{
	    $this->_values[$key] = $value;
	}
	
	/**
	 * Remove a value.
	 * Needed for ArrayAccess 
	 *
	 * @param string $key
	 */
	public function offsetUnset($key)
	{
	    unset($this->_values[$key]);
	}
}

?>