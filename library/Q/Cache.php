<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/Exception.php';
require_once 'Q/Cache/Handler.php';

/**
 * Cache objects can be used for saving otherwise persistant data.
 * 
 * @package Cache
 */
abstract class Cache implements Cacher
{
    /** All cache */
    const ALL=0x1;
    /** Don't cascade through chained interfaces */
    const NOCASCADE=0x2;
    
    
	/**
	 * Registered instances
	 * @var Cacher[]
	 */
	static protected $instances = array();
	
	/**
	 * Default configuration options
	 * @var array
	 */
	static public $defaultOptions = array(
		'lifetime'=>3600,
		'gc_probability'=>0.01,
	    'overwrite'=>true,
	    'serialize'=>'serialize',
		'unserialize'=>'unserialize'
	);

	/**
	 * Drivers with classname.
	 * 
	 * @var array
	 */
	static public $drivers = array(
	  'var'=>'Q\Cache_Var',
	  'file'=>'Q\Cache_File',
	  'files'=>'Q\Cache_File',
	  'apc'=>'Q\Cache_APC'
	);	

	
	/**
	 * Configuration options
	 * @var array
	 */
	public $options;

	/**
	 * Flag that this cache object is used as session handler
	 * @var boolean
	 */
	protected $isSessionHandler = false;

	/**
	 * Next cache item in the chain
	 * @var Cacher
	 */
	protected $chainNext;
	
	/**
	 * Create a new config interface.
	 *
	 * @param string|array $dsn      Configuration options, may be serialized as assoc set (string)
	 * @param array        $options  Configuration options (which do not appear in DSN)
	 * @return Cacher
	 */
	static public function with($dsn, $options=array())
	{
		$dsn_options = is_string($dsn) ? extract_dsn($dsn) : $dsn;
		$options = (array)$dsn_options + (array)$options;
		
		if ($options['driver'] == 'alias') return self::getInstance(isset($options[0]) ? $options[0] : null);
		
		if (!isset(self::$drivers[$options['driver']])) throw new Exception("Unable to create Cache object: Unknown driver '{$options['driver']}'");
		$class = self::$drivers[$options['driver']];
		if (!load_class($class)) throw new Exception("Unable to create $class object: Class does not exist.");
		
		return new $class($options);
	}
	
	/**
	 * Magic method to return specific instance
	 *
	 * @param string $name
	 * @param array  $args
	 * @return Cacher
	 */
	static public function __callstatic($name, $args)
	{
		if (!isset(self::$instances[$name])) {
		    if (!class_exists('Q\Config') || !Config::i()->exists() || !($dsn = Config::i()->get('cache' . ($name != 'i' ? ".{$name}" : '')))) return new Cache_Mock($name);
	        self::$instances[$name] = self::with($dsn);
		}
		
		return self::$instances[$name];
    }	
	
	/**
	 * Check if instance exists.
	 * 
	 * @return boolean
	 */
	public final function exists()
	{
	    if (func_num_args() > 0) trigger_error("Method exists() isn't expecting any arguments, it simply check if the interface exists. Perhaps you wanted to use has(), which checks if id is cached.", E_USER_NOTICE);
	    return true;
	}
	
	/**
	 * Register instance.
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
	 * @param array $options  Configuration options
	 */	
	function __construct($options=array())
	{
		if (!isset($options['app'])) $options['app'] = md5(isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname($_SERVER['PHP_SELF']));
		  elseif (preg_match('/[^\w\-\.]/', $options['app'])) $options['app'] = md5($options['app']);
		
		$this->options = $options + self::$defaultOptions;
	}
	
	/**
	 * Class destructor
	 */
	function __destruct()
	{
        if ($this->isSessionHandler) session_write_close(); 	    
	}
	
	/**
	 * Use cache object as session handler.
	 */
	public function asSessionHandler()
	{
	    $fn = function () { return true; };
	    $cache = $this;
	    session_set_save_handler($fn, $fn, array($this, 'doGet'), array($this, 'doSave'), array($this, 'doRemove'), function () { $cache->clean(); });
	    
	    $this->isSessionHandler = true;
	}
	
	/**
	 * Set the next cache handler in the chain.
	 *
	 * @param Cacher $cache  Cache object, DNS string or options
	 */
	public function chain($cache)
	{
	    if (!($cache instanceof Cacher)) $cache = self::with($cache);
	    $this->chainNext = $cache;
	}
	
	
	/**
	 * Test if cache is available.
	 * 
	 * @param string $id   Cache id
	 * @param int    $opt  Cache::% options
	 * @return boolean 
	 */
	public function has($id, $opt=0)
	{
	    return $this->doHas($id) || (isset($this->chainNext) && ~$opt & self::NOCASCADE && $this->chainNext->has($id));
	}
	
	/**
	 * Test if a cache is available and (if yes) return it.
	 * 
	 * @param string $id  Cache id
	 * @return mixed
	 */
	public function get($id, $opt=0)
	{
	    $data = $this->doGet($id, $opt);
	    
	    if (!isset($data) && isset($this->chainNext) && ~$opt & self::NOCASCADE) {
	        $data = $this->chainNext->get($id, $opt);
	        if (isset($data)) $this->doSave($id, $data, $opt);
	    }
	    
	    return $data;
	}
	
	/**
	 * Save data into cache.
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 * @param int    $opt   Cache::% options
	 */
	public function set($id, $data, $opt=0)
	{
	    $this->doSet($id, $data, $opt);
	    if (isset($this->chainNext) && ~$opt & self::NOCASCADE) $this->chainNext->set($data);
	}

	/**
	 * Alias of Q/Cache->set().
	 *
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 */
	final public function save($id, $data, $opt=0)
	{
	    $this->save($id, $data, $opt); 
	}
	
	/**
	 * Remove data from cache.
	 * 
	 * @param string  $id   Cache id
	 * @param int     $opt  Cache::% options
	 */
	public function remove($id, $opt=0)
	{
	    $this->doRemove($id);
	    if (isset($this->chainNext) && ~$opt & self::NOCASCADE) $this->chainNext->remove($id);
	}
	
	/**
	 * Remove old/all data from cache.
	 * 
	 * @param int $opt  Cache::% options
	 */
	public function clean($opt=0)
	{
		$this->doClean($opt);
	    if (isset($this->chainNext) && ~$opt & self::NOCASCADE) $this->chainNext->clean($opt);
	}
	
	
	/**
	 * Test if a cache is available in backend.
	 * 
	 * @param string $id   Cache id
	 * @param int    $opt  Cache::% options
	 * @return boolean
	 */
	abstract protected function doHas($id, $opt=0);
		
	/**
	 * Test if a cache is available in backend and (if yes) return it.
	 * Return null if not available.
	 * 
	 * @param string $id   Cache id
	 * @param int    $opt  Cache::% options
	 * @return mixed
	 */
	abstract protected function doGet($id, $opt=0);
	
	/**
	 * Save data into cache backend
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 * @param int    $opt   Cache::% options
	 */
	abstract protected function doSet($id, $data, $opt=0);
	
	/**
	 * Remove data from cache backend
	 * 
	 * @param string $id   Cache id
	 * @param int    $opt  Cache::% options
	 */
	abstract protected function doRemove($id, $opt=0);

	
	/**
	 * Remove old/all data from cache backend
	 * 
	 * @param int $opt  Cache::% options
	 */
	abstract protected function doClean($opt=0);
}

/**
 * Mock object to create Cache instance.
 * @ignore 
 */
class Cache_Mock
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
	 * Create a new Cache interface instance.
	 *
	 * @param string|array $dsn      Cacheuration options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Cache
	 */
	public function with($dsn, $options=array())
	{
	    $instance = Cache::with($dsn, $options);
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
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($key)
    {
        $name = $this->_name;
        if (Cache::$name()->exists()) trigger_error("Illigal use of mock object 'Q\Cache::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Cache interface '{$this->_name}' does not exist.");
    }

    /**
     * Magic set method
     *
     * @param string $key
     * @param mixed  $value
     * 
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __set($key, $value)
    {
        $name = $this->_name;
        if (Cache::$name()->exists()) trigger_error("Illigal use of mock object 'Q\Cache::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Cache interface '{$this->_name}' does not exist.");
    }
    
    /**
     * Magic call method
     *
     * @param string $name
     * @param array  $args
     * 
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __call($function, $args)
    {
        $name = $this->_name;
        if (Cache::$name()->exists()) trigger_error("Illigal use of mock object 'Q\Cache::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Cache interface '{$this->_name}' does not exist.");
    }
}
