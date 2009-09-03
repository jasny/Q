<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/Exception.php';
require_once 'Transform/Handler.php';

/**
 * Class Factory for a class that can transform data
 *  * @package Transform
 */
abstract class Transform implements Transform_Handler
{
	/**
	 * Cache interface
	 * @var Q\Transform
	 */
	static protected $instance;

	/**
	 * Drivers with classname.
	 * @var array
	 */
	static public $drivers = array(
      'xsl' => 'Q\Transform_XSL',
	  'replace' => 'Q\Transform_Replace',
	  'php' => 'Q\Transform_PHP',
	  'array2xml' => 'Q\Transform_Array2XML',
	  'xml2array' => 'Q\Transform_XML2Array',
	  'text2html' => 'Q\Transform_Text2HTML'
	);
	
    /**
     * Next transform item in the chain
     * @var Transform
     */
    protected $chainNext;
	
	/**
	 * Create a new transformation interface.
	 * @static
	 *
	 * @param string|array $dsn      Transformation options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Transform
	 */
	public static function with($dsn, $options=array())
	{
		$options = (is_scalar($dsn) ? extract_dsn($dsn) : (array)$dsn) + (array)$options;
		$driver = $options['driver'];

		if (!isset(self::$drivers[$driver]) && strpos($driver, '.') !== false && isset(self::$drivers[pathinfo($driver, PATHINFO_EXTENSION)])) {
		    $options[0] = $driver;
		    $driver = pathinfo($driver, PATHINFO_EXTENSION);
		}

		if (!isset(self::$drivers[$driver])) throw new Exception("Unable to create Transform object: Unknown driver '$driver'");
		$class = self::$drivers[$driver];
		if (!load_class($class)) throw new Exception("Unable to create $class object: Class does not exist.");

		return new $class($options);
	}

	/**
	 * Class constructor
	 *
	 * @param array $options
	 */
	public function __construct($options=array())
	{
	    foreach ($options as $key=>$value) {
	        $this->$key = $value;
	    }
	}
	
    /**
     * Set the next transform handler in the chain.
     *
     * @param Transform $cache  Transform object, DNS string or options
     */
    public function chain($transform)
    {
        if (!($transform instanceof Transform)) $transform = self::with($transform);
        $this->chainNext = $transform;
    }
    
	/**
	 * Magic get method: get settings
	 *
	 * @param string $key
	 * @return mixed
	 */
/*
	public function __get($key)
	{
		return $this->get($key);
	}
*/	
	/**
	 * Magic set method: put settings
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
/*
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}
*/
}

/**
 * Mock object to create transform instance.
 * @ignore 
 */
class Transform_Mock
{
    /**
     * Instance name
     * @var string
     */
    protected $_name;
    
    /**
     * Class constructor
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }
    
	/**
	 * Create a new transform interface instance.
	 *
	 * @param string|array $dsn      Transformation options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Config
	 */
	public function with($dsn, $options=array())
	{
	    if (isset(self::$instance)) throw new Exception("Transform_Mock instance is already created.");
	    
		$options = (is_scalar($dsn) ? extract_dsn($dsn) : (array)$dsn) + (array)$options;
	    return new self($options);
	}
    
    /**
     * Magic get method
     *
     * @param string $key
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($key)
    {
        $name = $this->_name;
        throw new Exception("Config interface '{$this->_name}' does not exist.");
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
        $name = $this->_name;
        throw new Exception("Config interface '{$this->_name}' does not exist.");
    }
}
if (class_exists('Q\ClassConfig', false)) ClassConfig::applyToClass('Q\SiteTemplate');

/*
$transform = Transform::with('xsl:my.xsl');
$transform->chainInput(Tranform::with('php:data2xml.php'));

$transform->process($somedata);
*/