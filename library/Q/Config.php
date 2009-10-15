<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/Config/Exception.php';

/**
 * Load a configuration settings
 * 
 * @package Config
 */
class Config extends \ArrayObject
{
    /**
     * Cache interface
     * @var Q\Config[]
     */
    static protected $instances = array();
	
	/**
     * Drivers with classname.
     * @var array
     */
    static public $drivers = array(
      'file'=>'Q\Config_File',
      'dir'=>'Q\Config_Dir'
    );

    
	/**
	 * Create a new config interface.
	 * @static
	 *
	 * @param string|array $dsn      Configuration options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Config
	 */
	static public function with($dsn, $options=array())
	{
		$options = extract_dsn($dsn) + (array)$options;
		
		if (get_called_class() !== __CLASS__) {
		    $class = get_called_class();
		    return new $class($options);
		}
		
		// If driver is unknown, assume driver is filename or extension and use driver 'file' or 'dir'
		if (!isset(self::$drivers[$options['driver']])) {
		    if (strpos($options['driver'], '.') === false && strpos($options['driver'], '/') === false) {
		        $options['ext'] = $options['driver'];
		        if (!isset($options['path']) && isset($options[0])) $options['path'] = $options[0];
		    } else {
		        $options['path'] = $options['driver'];
		    }
		    $options['driver'] = isset($options['path']) && (file_exists($options['path']) ? is_dir($options['path']) : strpos($options['path'], '.') === false) ? 'dir' : 'file';
		}
        
        if (!isset($options['driver'])) throw new Exception("Unable to create Config object: No driver specified");
        if (!isset(self::$drivers[$options['driver']])) throw new Exception("Unable to create Config object: Unknown driver '{$options['driver']}'");

        $class = self::$drivers[$options['driver']];
        if (!load_class($class)) throw new Exception("Unable to create $class object for driver '{$options['driver']}': Class does not exist.");
        
        unset($options['driver']);
        return new $class($options);
	}
	
	/**
	 * Magic method to retun specific instance
	 *
	 * @param string $name
	 * @return Config
	 */
	static public function getInstance($name)
	{
	    if (isset(self::$instances[$name])) return self::$instances[$name];

	    if ($name != 'i' && self::i()->exists()) $dsn = self::i()->get('config' . ($name != 'i' ? ".{$name}" : ''));

	    if (empty($dsn)) {
	        $const = 'CONFIG' . ($name != 'i' ? strtoupper("_{$name}") : ''); 
            if (!defined($const)) return new Mock(__CLASS__, $name);
            $dsn = constant($const);
	    }
	    
        self::$instances[$name] = self::with($dsn);
        return self::$instances[$name];
    }

    /**
     * Get default instance
     * 
     * @return Config
     */
    static public function i()
    {
        return isset(self::$instances['i']) ? self::$instances['i'] : self::getInstance('i');
    }

	/**
	 * Magic method to retun specific instance
	 *
	 * @param string $name
	 * @param string $args
	 * @return Config
	 */
	static public function __callstatic($name, $args)
	{
	    return isset(self::$instances[$name]) ? self::$instances[$name] : self::getInstance($name);
	}
	
	
	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	public function __construct($options=array())
	{
	   parent::__construct(array(), \ArrayObject::ARRAY_AS_PROPS);
	}
	
	/**
	 * Load all settings (eager load).
	 * (fluent interface)
	 * 
	 * @return Config
	 */
	protected function loadAll()
	{
	   return $this;
	}
}
