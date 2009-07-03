<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/Exception.php';

/**
 * Load a configuration settings
 *
 * Options:
 *   caching       Enable caching: TRUE for Q\Cache::i(), Q\Cache DSN (string), Q\Cache object or Cache_Lite object.
 *   caching_id    Required if caching is enabled
 * 
 * @package Config
 */
abstract class Config
{
	/**
	 * Cache interface
	 * @var Q\Config[]
	 */
	static protected $instances = array();

	/**
	 * Default configuration options
	 * @var array
	 */
	static public $defaultOptions = array(
	  'driver'=>'none',
	  'caching'=>false
	);

	/**
	 * Drivers with classname.
	 * 
	 * @var array
	 */
	static public $drivers = array(
	  'none'=>'Q\Config_None',
	  'ini'=>'Q\Config_Ini',
	  'advini'=>'Q\Config_AdvIni',
      'xml'=>'Q\Config_XML',
	  'json'=>'Q\Config_Json',
	  'yaml'=>'Q\Config_Yaml',
	);
		
	
	/**
	 * Object options
	 * @var array
	 */
	protected $_options;

	/**
	 * Caching object
	 * @var Q\Cache
	 */
	protected $_cache;
		
	/**
	 * CRC32 hash of settings from cache, used to see if settings are changed.
	 * @var int
	 */
	protected $_cache_check;

	/**
	 * Flag to specify that everything is loaded
	 * @var boolean
	 */
	protected $_loadedAll=false;
	
	/**
	 * All cached values
	 * @var array
	 */
	protected $_settings = array();	
		

	/**
	 * Create a new config interface.
	 * @static
	 *
	 * @param string|array $dsn      Configuration options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Config
	 */
	public function with($dsn, $options=array())
	{
	    if (isset($this) && $this instanceof self) throw new Exception("Config instance is already created.");
	    
		$options = (is_scalar($dsn) ? extract_dsn($dsn) : (array)$dsn) + (array)$options + self::$defaultOptions;
		$driver = $options['driver'];
		
		if ($driver == 'aliasof') {
		    if (!isset($options[0])) throw new Exception("When using 'aliasof', it's required to specify which instance to use.");
		    $instance = $options[0];
		    return $instance instanceof self ? $instance : self::$instance();
		}
		
		if (!isset(self::$drivers[$driver]) && strpos($driver, '.') !== false && isset(self::$drivers[pathinfo($driver, PATHINFO_EXTENSION)])) {
		    $options[0] = $driver;
		    $driver = pathinfo($driver, PATHINFO_EXTENSION);
		}
		
		if (!isset(self::$drivers[$driver])) throw new Exception("Unable to create Config object: Unknown driver '$driver'");
		$class = self::$drivers[$driver];
		if (!load_class($class)) throw new Exception("Unable to create $class object: Class does not exist.");
		
		return new $class($options);
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
	    if (!isset(self::$instances[$name])) {
    	    if ($name != 'i' && self::i()->exists()) $dsn = self::i()->get('config' . ($name != 'i' ? ".{$name}" : ''));
    
    	    if (empty($dsn)) {
    	        $env = 'Q_CONFIG' . (isset($name) ? strtoupper("_{$name}") : ''); 
                if (!isset($_ENV[$env])) return new Config_Mock($name);
                $dsn = $_ENV[$env];
    	    }
    	    
            self::$instances[$name] = self::with($dsn);
	    }
	    
        return self::$instances[$name];
    }
	
	/**
	 * Check is singeton object exists
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function exists()
	{
	    return true;
	}

	/**
	 * Register instance
	 * 
	 * @param string $name
	 */
	public final function useFor($name)
	{
		self::$instances[$name] = $this;
	}	
	
	
	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	public function __construct($options=array())
	{
		// Init caching
		if ((int)$options['caching'] == 1 || $options['caching'] === 'on') {
		    if (!isset($options['cache_id'])) trigger_error("Unable to cache configuration: No 'cache_id' option.", E_USER_NOTICE);
		      elseif (!Cache::hasInstance()) trigger_error("Unable to use caching, general Q\Cache instance has not (yet) been created.", E_USER_NOTICE);
	          else $this->_cache = Cache::i();
		} elseif (is_object($options['caching'])) {
	        $this->_cache = $options['caching'];
	        unset($options['caching']);
	    } elseif (is_string($options['caching']) && $options['caching'] !== 'off') {
            $this->_cache = Cache::with($options['caching'], array_chunk_assoc($options, 'caching'));
	    }
	    
	    if ($this->_cache) {
		    $this->_settings = $this->_cache->get($options['cache_id']);
		    if (empty($this->_settings)) $this->_settings = array();
		    $this->_cache_check = crc32(serialize($this->_settings));
	    }

		// Save options
		$this->_options = $options;
		
		// Optionaly load all settings
		if (!empty($this->_options['load_all'])) $this->getSettings();
	}

	/**
	 * Class destructor
	 */
	public function __destruct()
	{
		// Cache settings to disk using Q\Cache
		if (($this->_options['caching'] === 'on' || isset($this->_cache)) && $this->_cache_check != crc32(serialize($this->_settings))) {
			$ci = isset($this->_cache) ? $this->_cache : Cache::i();
			$ci->save($this->_settings, $this->_options['cache_id'], 'Q\Config');
		}
	}

	/**
	 * Magic get method: get settings
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function &__get($key)
	{
		return $this->get($key);
	}
	
	/**
	 * Magic set method: put settings
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}
	
	
	/**
	 * Return a valid group name
	 * 
	 * @param string $group
	 * @return string
	 */
	public function groupName($group)
	{
		return $group;
	}
	

	/**
	 * Load a config file or dir and save it to cache
	 * 
	 * @param string $group
	 * @return array
	 */
	abstract protected function loadToCache($group=null);	
	
	/**
	 * Get reference to cached settings for group.
	 * 
	 * @param mixed $key  Key(string), array(group, ..., key) or NULL for all settings
	 * @return array
	 */
	protected function &getFromCache($key=null)
	{
		if ($key === null) return $this->_settings;

		$settings =& $this->_settings;
		foreach ((array)$key as $k) {
			if (!isset($settings[$k])) $settings[$k] = null;
			$settings =& $settings[$k];
		}
		
		return $settings;
	}
	
	/**
	 * Clear config from cache
	 *
	 * @param string $key
	 */
    public function clearCache($key=null)
    {
        if ($key) $this->_settings[$key] = null;
         else $this->_settings = array();
        
		$this->_loadedAll = false;
		$settings = null;
    }
	
	/**
	 * Check if a setting is in cache.
	 * 
	 * @param string|array $key
	 * @param Additional arguments may be passed for sublevels of $key
	 * @return bool
	 */
	public function isCached($key)
	{
	    $keys = is_array($key) ? $key : func_get_args();

	    $settings =& $this->_settings;
		foreach ((array)$keys as $k) {
			if (!array_key_exists($k, $settings)) return false;
			$settings =& $settings[$k];
		}
		
		return true;
	}

	/**
	 * Return a setting.
	 * 
	 * @param string $key      
	 * @param Additional arguments may be passed for sublevels of $key
	 * @return mixed
	 */
	function &get($key=null)
	{
		$this->loadToCache($key);
		
		$keys = is_array($key) ? $key : func_get_args();
		return $this->getFromCache($keys, true);
	}
	
	/**
	 * Cache the settings of a group.
	 * 
	 * @param string $key      
	 * @param array  $value
	 * @param Additional arguments may be passed for sublevels of $key, the last argument will always be seen as the value
	 */
	public function set($key, $value)
	{
	    $this->loadToCache($key);
	    
		if (!is_array($key) && func_num_args() > 2) {
		    $keys = func_get_args();
		    $value = array_pop($keys);
		} else {
		    $keys = (array)$key;
		}
		
		$cache_ref =& $this->getFromCache($keys, true);
		$cache_ref = $value;
	}
}

/**
 * Mock object to create config instance.
 * @ignore 
 */
class Config_Mock
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
	 * Create a new config interface instance.
	 *
	 * @param string|array $dsn      Configuration options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Config
	 */
	public function with($dsn, $options=array())
	{
	    $instance = Config::with($dsn, $options + array('cache_id'=>'config' . ($this->_name == 'i' ? '' : ".{$this->_name}")));
	    $instance->useFor($this->_name);
	    
	    return $instance;
    }
    
    
    /**
     * Check if instance exists.
     *
     * @return boolean
     */
    public function exists()
    {
        return false;
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
        if (Config::$name()->exists()) trigger_error("Illigal of mock object 'Q\Config::{$this->_name}()'.", E_USER_ERROR);
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
        if (Config::$name()->exists()) trigger_error("Illigal of mock object 'Q\Config::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Config interface '{$this->_name}' does not exist.");
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
        $name = $this->_name;
        if (Config::$name()->exists()) trigger_error("Illigal of mock object 'Q\Config::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Config interface '{$this->_name}' does not exist.");
    }
}

if (class_exists('Q\ClassConfig', false)) ClassConfig::applyToClass('Q\Config');

?>