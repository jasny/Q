<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/Config/Exception.php';
require_once 'Q/Fs.php';

/**
 * Load a configuration settings
 * 
 * @package Config
 */
class Config /* implements \Iterator */
{
    /**
     * Cache interface
     * @var Q\Config[]
     */
    static protected $instances = array();
    
    /**
	 * Class foe each type.
	 * @var array
	 */
	static public $types = array(
	  'file'=>'Q\Config_File',
	  'dir'=>'Q\Config_Dir',
	);

	/**
	 * Driver in use
	 */
	protected $_driver;
	
	/**
     * Drivers with classname.
     * @var array
     */
    static public $drivers = array(
      'json',
      'xml',
      'yaml',
      'ini',
    );

    /**
     * Default configuration options
     * @var array
     */
    static public $defaultOptions = array();
    
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
     * File path
     * @Fs_Node
     */
    protected $_path;
    
	/**
	 * Object options
	 * @var array
	 */
	protected $_options;

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
		$options = (is_scalar($dsn) ? extract_dsn($dsn) : (array)$dsn) + (array)$options + self::$defaultOptions;
		$driver = $options['driver'];
		
		if (!isset($driver) && !in_array($driver, self::$drivers) && strpos($driver, '.') !== false && in_array(pathinfo($driver, PATHINFO_EXTENSION), self::$drivers)) {
		    $options[0] = $driver;
		    $driver = pathinfo($driver, PATHINFO_EXTENSION);
		}
		
		if (!in_array($driver, self::$drivers)) throw new Exception("Unable to create Config object: Unknown driver '$driver'");
//		$class = self::$drivers[$driver];
        $options['driver'] = $driver;
		
        if (isset($options[0])) $options['path'] = Fs::get($options[0]);
          elseif (isset($options['path'])) $options['path'] = Fs::get($options['path']);
        unset($options[0]);
		
        if (!isset($options['path'])) throw new Config_Exception("Unable to create Config object: Unknown path");
        
        if (!isset(self::$types[Fs::typeOfNode($options['path'])])) throw new Exception("Unable to create Config object: Unknown or wrong file type");
        $class = self::$types[Fs::typeOfNode($options['path'])];
        if (!load_class($class)) throw new Transform_Exception("Unable to create $class object: Class does not exist.");
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
            if (!defined($const)) return new Config_Mock($name);
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
//        if (empty($this->_settings)) $this->_settings = array();
		// Save options
//		$this->_options = $options;
		
		// Optionaly load all settings
//		if (!empty($this->_options['load_all'])) $this->getSettings();
	}

	/**
	 * Class destructor
	 */
	public function __destruct()
	{
		// Cache settings to disk using Q\Cache
/*
	    if (($this->_options['caching'] === 'on' || isset($this->_cache)) && $this->_cache_check != crc32(serialize($this->_settings))) {
			$ci = isset($this->_cache) ? $this->_cache : Cache::i();
			$ci->save($this->_settings, $this->_options['cache_id'], 'Q\Config');
		}
*/
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
	protected function loadToCache($group=null){}	
	
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
        if (Config::$name()->exists()) trigger_error("Incorrect use of mock object 'Q\Config::{$this->_name}()'.", E_USER_ERROR);
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
        if (Config::$name()->exists()) trigger_error("Incorrect use of mock object 'Q\Config::{$this->_name}()'.", E_USER_ERROR);
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
        if (Config::$name()->exists()) trigger_error("Incorrect use of mock object 'Q\Config::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Config interface '{$this->_name}' does not exist.");
    }
}
