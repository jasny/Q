<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/Exception.php';
require_once 'Q/Transformer.php';
require_once 'Q/Factory.php';

/**
 * Base class for Transform interfaces.
 *  * @package Transform
 */
abstract class Transform implements Transformer, Factory
{
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
	 * Create a new Transform interface.
	 *
	 * @param string|array $dsn      Transformation options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Transformer
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
}