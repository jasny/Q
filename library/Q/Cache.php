<?
namespace Q;

require_once 'Q/misc.php';

/**
 * Base class for cache interfaces.
 * 
 * @package Cache
 */
abstract class Cache
{
	/**
	 * Registered instances
	 * @var Q\Cache[]
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
	public $options = array();

	/**
	 * Flag that this cache object is used as session handler
	 * @var boolean
	 */
	protected $isSessionHandler = false;

	/**
	 * Next cache item in the chain
	 * @var Cache
	 */
	protected $chainNext;
	
	/**
	 * Create a new config interface.
	 * @static
	 *
	 * @param string|array $dsn      Configuration options, may be serialized as assoc set (string)
	 * @param array        $options  Configuration options (which do not appear in DSN)
	 * @return Cache
	 */
	public function with($dsn, $options=array())
	{
	    if (isset($this) && $this instanceof self) throw new Exception("Cache instance is already created.");
	    
		$dsn_options = is_string($dsn) ? extract_dsn($dsn) : $dsn;
		$options = (array)$dsn_options + (array)$options + self::$defaultOptions;
		
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
	 * @return Cache
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
		
		$this->options = $options;
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
	 * @param Cache $cache  Cache object, DNS string or options
	 */
	public function chain($cache)
	{
	    if (!($cache instanceof Cache)) $cache = self::with($cache);
	    $this->chainNext = $cache;
	}
	
	
	/**
	 * Test if a cache is available and (if yes) return it
	 * 
	 * @param string $id  Cache id
	 * @return mixed
	 */
	public function get($id)
	{
	    $data = $this->doGet($id);
	    
	    if (!isset($data) && isset($this->chainNext)) {
	        $data = $this->chainNext->get();
	        if (isset($data)) $this->doSave($id, $data);
	    }
	    
	    return $data;
	}
	
	/**
	 * Save data into cache
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 */
	public function save($id, $data)
	{
	    $this->doSave($id, $data);
	    if (isset($this->chainNext)) $this->chainNext->save($data);
	}

	/**
	 * Alias of Q/Cache->save()
	 *
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 */
	final public function set($id, $data)
	{
	    $this->save($id, $data); 
	}
	
	/**
	 * Remove data from cache
	 * 
	 * @param string $id  Cache id
	 */
	public function remove($id)
	{
	    $this->doRemove($id);
	    if (isset($this->chainNext)) $this->chainNext->remove($data);
	}
	
	/**
	 * Remove old/all data from cache
	 * 
	 * @param boolean $all    Remove all data, don't check age
	 * @param boolean $chain  Also clean other cache in chain
	 */
	public function clean($all=false, $chain=true)
	{
		$this->doClean($all);
	    if ($chain && isset($this->chainNext)) $this->chainNext->clean($all, true);
	}
	
	
	/**
	 * Test if a cache is available in backend and (if yes) return it.
	 * Return false if not available.
	 * 
	 * @param string $id  Cache id
	 * @return mixed
	 */
	abstract protected function doGet($id);
	
	/**
	 * Save data into cache backend
	 * 
	 * @param string  $id         Cache id
	 * @param mixed   $data       Data to put in the cache
	 */
	abstract protected function doSave($id, $data);
	
	/**
	 * Remove data from cache backend
	 * 
	 * @param string $id  Cache id
	 */
	abstract protected function doRemove($id);

	
	/**
	 * Remove old/all data from cache backend
	 * 
	 * @param boolean $all  Remove all data, don't check age
	 */
	abstract protected function doClean($all=false);
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
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($key)
    {
        $name = $this->_name;
        if (Cache::$name()->exists()) trigger_error("Illigal of mock object 'Q\Cache::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Cache interface '{$this->_name}' does not exist.");
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
        if (Cache::$name()->exists()) trigger_error("Illigal of mock object 'Q\Cache::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Cache interface '{$this->_name}' does not exist.");
    }
    
    /**
     * Magic call method
     *
     * @param string $name
     * @param array  $args
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __call($function, $args)
    {
        $name = $this->_name;
        if (Cache::$name()->exists()) trigger_error("Illigal of mock object 'Q\Cache::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Cache interface '{$this->_name}' does not exist.");
    }
}

if (class_exists('Q\ClassConfig', false)) ClassConfig::applyToClass('Q\Cache');

?>