<?php
namespace Q;

require_once 'Q/Mock.php';

/**
 * Mock object for pretty syntax of with factory methods in combination with named instances.
 *
 * @package Mock
 */
class Mock_With extends Mock
{
    /**
     * Target class
     * @var string
     */
    protected $_class;
    
	/**
     * Instance name
     * @var string
     */
    protected $_name;
    
    /**
     * Class constructor
     *
     * @param string $class  Target class, should implement Factory and Multiton
     * @param string $name   Instance name
     */
    public function __construct($class, $name)
    {
    	$this->_class = $class;
        $this->_name = $name;
    }
    
	/**
	 * Create a new interface instance.
	 *
	 * @param string|array $dsn      Authuration options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return object
	 */
	public function with($dsn, $options=array())
	{
	    $instance = call_user_func(array($this->_class, 'with'), $dsn, $options);
	    $instance->useFor($this->_name);
	    
	    return $instance;
    }

	/**
	 * Alias of Mock_With::with().
	 *
	 * @param string|array $dsn      Authuration options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Auth
	 */
	public final function to($dsn, $options=array())
	{
		return $this->with($dsn, $options);
	}
    
    /**
     * Magic get method
     *
     * @param string $key
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($key)
    {
        if (!(call_user_func(array($this->_class, $this->_name)) instanceof Mock)) trigger_error("Illigal use of mock object '{$this->_class}::{$this->_name}()'.", E_USER_ERROR);
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
        if (!(call_user_func(array($this->_class, $this->_name)) instanceof Mock)) trigger_error("Illigal use of mock object '{$this->_class}::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("{$this->_class} interface '{$this->_name}' does not exist.");
    }
    
    /**
     * Magic call method
     *
     * @param string $name
     * @param array  $args
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __call($function, $args)
    {
        if (!(call_user_func(array($this->_class, $this->_name)) instanceof Mock)) trigger_error("Illigal use of mock object '{$this->_class}::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("{$this->_class} interface '{$this->_name}' does not exist.");
    }
}
