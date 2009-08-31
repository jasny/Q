<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/SecurityException.php';
require_once 'Q/misc.php';

require_once 'Q/Log/Handler.php';
require_once 'Q/Log/EventValues.php';
require_once 'Q/Log/Filters.php';

/**
 * Base class for interfaces that log messages or notify users. 
 * 
 * DSN: 'driver:arg1;arg2;filter=!warning,!notice;alias[sql]=info'
 * 
 * A container with multiple log handlers can be created using DSN:
 *  'firephp + errors@myorg.com;filters=!notice,!info,!debug + mysys.log;format="[type] message" + error.log;filters=error,crit,alert,emerg + firephp + filters=error,warning' 
 * 
 * Available drivers:
 *  file:FILENAME    - Log to a file
 *  logfile:FILENAME - Log to a file using format '[{$time}] [{$type}] {$message}'
 *  output           - Log to screen
 *  stderr           - Log to stderr (console)
 *  sapi             - Log to webserver (when PHP runs as webserver module)
 *  syslog           - Log to syslog
 *  mail:TO          - Send an e-mail
 *  firephp          - Write message in the FireBug console using FirePHP
 *  firephp-table    - Collect messages to create a table in the FireBug console
 *  header           - Write an HTTP header
 *
 * The driver can be omitted when:
 *  .txt, .csv         -> file
 *  .log               -> logfile
 *  an e-mail address  -> mail
 *  
 * @package Log
 * 
 * @todo Use Transform for Log::getLine().
 * @todo Move filter functionality to Log_Filter class.
 */
abstract class Log implements Log_Handler
{
	/** Include message of type */
	const FILTER_INCLUDE = true;
	
	/** Exclude message of type */
	const FILTER_EXCLUDE = false;
	

	/**
	 * Registered instances
	 * @var Q\Log[]
	 */
	static protected $instances = array();
	
	/**
	 * Default configuration options
	 * @var array
	 */
	static public $defaultOptions = array();
	
	
	/**
	 * Values that can be used in event item.
	 * Can be used as array.
	 *
	 * @var Q\Log_EventValues
	 */
	protected $_eventValues;
	
	/**
	 * Alias for types.
	 * @var array
	 */
	public $alias = array(
		0=>'emerg',
		1=>'alert',
		2=>'crit',
		3=>'err',
		4=>'warn',
		5=>'notice',
		6=>'info',
		7=>'debug'
	);
	
	/**
	 * Drivers with classname or as array(classname, arg, ...).
	 * @var array
	 */
	static public $drivers = array(
		'container'=>'Q\Log_Container', 
		'file'=>'Q\Log_Text',
		'stream'=>'Q\Log_Text',
		'logfile'=>array('Q\Log_Text', 'format'=>'[{$time}] [{$type}] {$message}'),
		'output'=>array('Q\Log_Text', 'php://output'),
		'stderr'=>array('Q\Log_Text', 'php://strerr'),
	    'sapi'=>'Q\Log_Sapi',
		'syslog'=>'Q\Log_Syslog',
	    'mail'=>'Q\Log_Mail',
		'firephp'=>'Q\Log_FirePHP',
		'firephp-table'=>'Q\Log_FirePHPTable',
		'header'=>'Q\Log_Header',
	    'db'=>'Q\Log_DB'
	);

	
	/**
	 * Log (don't skip) if a type is not in filter list.
	 * @var boolean
	 */
	protected $filterDefault = true;//Log::FILTER_INCLUDE;
	
	/**
	 * Filter list 
	 * @var array
	 */
	protected $filters;
	
	
	/**
	 * The format or delimiter of a log line.
	 * '{$KEY}' will be replaced to a event value or variable.
	 * 
	 * @var string
	 */
	public $format = " | ";

	/**
	 * The format for each value as sprintf format (key is '%1$s', value is '%2$s').
	 * @var string
	 */
	public $formatValue;
	
	/**
	 * Implode arguments when item is an array.
	 * array('glue'=>glue, 'prefix'=>group_prefix, 'suffix'=>group_suffix)
	 * 
	 * @var array
	 */
	public $arrayImplode=array('glue'=>', ', 'prefix'=>'(', 'suffix'=>')');	
	
	/**
	 * Quote values.
	 * When using formatValue, enabling quoting will just escape quotes in value and key.
	 * 
	 * @var boolean
	 */
	public $quote=false;
		
	/**
	 * Make sure each log event is on a single line.
	 * @var string
	 */
	public $singleline=true;
	
	
	/**
	 * Extract the connection parameters from a DSN string.
	 * Returns array(driver, filters, props)
	 * 
	 * @param string|array $dsn
	 * @return array
	 */
	static public function extractDSN($dsn)
	{
		$args = array();
		$filters = array();
		$props = array();
		$matches = null;
		
		// Extract DSN
		if (!is_string($dsn)) {
		    $props = $dsn;
		    $driver = strtolower(array_shift($props));
		    
		} elseif (strpos($dsn, '+') !== false && preg_match_all('/((?:\"(?:[^\"\\\\]++|\\\\.)++\")|(?:\'(?:[^\'\\\\]++|\\\\.)++\')|[^\+\"\']++)++/', $dsn, $matches) >= 2) {
		    $a = null;
			$driver = 'container';
			$props = $matches[0];
			$filters = null;
			foreach ($props as $i=>$prop) {
			    if (preg_match('/^\s*(filter\s*(?:\[("(?:\\\\"|[^"])*")|(\'(?:\\\\\'|[^\'])*\'|[^\]]+)\]\s*)?)=(.*)$/', $prop, $filters)) {
                    parse_str($filters[1] . '=' . unquote(trim($filters[2])), $a);
                    $filters = array_replace_recursive($filters, $a);
                    unset($props[$i]);
                }
			}
			
		} else {
			$props = extract_dsn($dsn);
			$driver = strtolower(array_shift($props));
		}

		// Get filters and properties from arguments
		if (isset($args['filter'])) {
		    $filters = $args['filter'];
            unset($args['filter']);
		    if (!is_array($filters)) $filters = split_set(',', $filters);
		}
		
		return array($driver, $filters, $props);
	}	

	/**
	 * Create a new Log interface.
	 *
	 * @param string|array $dsn     DSN/driver (string) or array(driver[, arg1, ...])
	 * @param array        $filter
	 * @param array        $props   Values for public properties
	 * @return Log
	 */
	public function to($dsn, $filters=array(), $props=array())
	{
	    if (isset($this) && $this instanceof self) throw new Exception("Log instance is already created.");
	    if ($dsn instanceof Log_Handler) return $dsn;

	    $filters = array();
	    $props = array();
	    
		// Extract info
		list($driver, $dsn_filters, $dsn_props) = self::extractDSN($dsn);

		if ($driver == 'aliasof') {
		    if (!isset($props[0])) throw new Exception("When using 'aliasof', it's required to specify which instance to use.");
		    $instance = $props[0];
		    return $instance instanceof self ? $instance : self::$instance();
		}
		
		if (!isset(self::$drivers[$driver])) throw new Exception("Unable to create Log interface: Unknown driver '$driver'");
		
		$class_options = (array)self::$drivers[$driver];
		$class = array_shift($class_options);
		
		if (!empty($dsn_filters)) $filters = array_merge($filters, $dsn_filters);
		if (!empty($dsn_props)) $props = array_merge_recursive($props, $dsn_props);
		
		// Create object
		if (!load_class($class)) throw new Exception("Unable to create $class object: Class does not exist.");		
		$object = new $class($props);

		foreach ($filters as $filter) $object->setFilter($filter);
		return $object;
	}

	/**
	 * Alias of Log::to().
	 *
	 * @param string|array $dsn     DSN/driver (string) or array(driver[, arg1, ...])
	 * @param array        $filter
	 * @param array        $props   Values for public properties
	 * @return Log
	 */
	static public function with($dsn, $filters=array(), $props=array())
	{
		self::to($dsn, $filters, $props);
	}
	
	/**
	 * Magic method to return specific instance.
	 *
	 * @param string $name
	 * @param array  $args
	 * @return DB
	 */
	static public function __callstatic($name, $args)
	{
		if (!isset(self::$instances[$name])) {
		    if (!class_exists('Q\Config') || !Config::i()->exists() || !($dsn = Config::i()->get('log' . ($name != 'i' ? ".{$name}" : '')))) return new Log_Mock($name);
	        self::$instances[$name] = self::to($dsn);
		}
		
		return self::$instances[$name];
    }	
	
	/**
	 * Check if instance exists.
	 * 
	 * @param string $name
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
	 * Class constructor.
	 * 
	 * @param array $props
	 */
	public function __construct($props=array())
	{
		foreach ($props as $prop=>$value) {
			$this->$pros = $value;
		}
		
        $this->_eventValues = new Log_EventValues();
	}
	
	
	/**
	 * Add a filter to log/not log messages of a specific type.
	 * Fluent interface
	 *
	 * @param string  $type    Filter type, action may be negated by prefixing it with '!'
	 * @param boolean $action  Q\Log::FILTER_* constant
	 * @return Log
	 */
	public function setFilter($type, $action=self::FILTER_INCLUDE)
	{
		if ($type[0] == '!') {
			$action = !$action;
			$type = substr($type, 1);
		}
        $this->filters[$type] = (bool)$action;
		$this->filterDefault = !array_sum($this->filters);
		
		return $this;
	}

	/**
	 * Check if a message should be logged based on the filters.
	 *
	 * @param string $type  Filter type
	 * @return boolean           
	 */
	public function shouldLog($type)
	{
		return isset($this->filters[$type]) ? $this->filters[$type] : $this->filterDefault; 
	}
	
	/**
	 * Log a message.
	 *
	 * @param string|array $message  Message or associated array with info
	 * @param string       $type
	 */
	public function log($message, $type=null)
	{
	    if (is_array($message) && !isset($type) && isset($message['type'])) $type = $message['type'];
	    if (isset($this->alias[$type])) $type = $this->alias[$type];
	    if (!$this->shouldLog($type)) return;
		
	    $args = is_array($message) ? $message : array('message'=>$message);
	    if (!empty($type)) $args = array('type'=>$type) + $args;
	    
		$this->write($args);
	}
	
	/**
	 * Magic invoke; Alias of Log::log().
	 * 
	 * @param string|array $message  Message or associated array with info
	 * @param string       $type
	 */
	final public static function __invoke($message, $type)
	{
		$this->log($message, $type);
	}  
	
	
	/**
	 * Write a log message.
	 *
	 * @param array $args
	 */
	abstract protected function write($args);
	
	
	/**
	 * Get the line for logging.
	 *
	 * @param array $args
	 * @return string
	 */
	protected function getLine($args)
	{
	    return strpos($this->format, '%{') === false ?
	      $this->getLine_Join($args) :
	      $this->getLine_Parse($args);
	}
	
	/**
	 * Get the line for logging joining the arguments.
	 *
	 * @param array $args
	 * @return string
	 */
	protected function getLine_Join($args)
	{
        $args = array_merge($this->_eventValues->getAll(), $args);

	    if (count($args) == 1 && reset($args) instanceof \Exception) {
	        $e = reset($args);
	        $line = '(' . get_class($e) . ') ' . (string)$e;
	        if ($this->singleline) $line = str_replace(array("\n", "\r"), array(chr(182), ''), $line);
	        return $line;
	    }
        
        foreach (array_keys($args) as $key) {
            if (!is_scalar($args[$key])) $args[$key] = $this->arrayImplode['prefix'] . implode_recursive($this->arrayImplode['glue'], $args[$key], $this->arrayImplode['prefix'], $this->arrayImplode['suffix']) . $this->arrayImplode['suffix'];
            if (!empty($this->formatValue)) $args[$key] = sprintf($this->formatValue, $this->quote ? addcslashes($key, '"') : $key, $this->quote ? addcslashes($args[$key], '"') : $args[$key]);
              elseif ($this->quote) $args[$key] = '"' . addcslashes($args[$key], '"') . '"';
        }

        $line = join($this->format, $args);
        if ($this->singleline) $line = str_replace(array("\n", "\r"), array(chr(182), ''), $line);
        return $line;
    }

	/**
	 * Get the line for logging parsing the arguments.
	 *
	 * @param array $args
	 * @param string $template  Template for line, uses format property by default
	 * @return string
	 */
	protected function getLine_Parse($args, $template=null)
	{
	    if (!isset($template)) $template = $this->format;
	    if (strpos($this->format, '%{') === false) return $template;
	    
	    if (count($args) == 1 && reset($args) instanceof \Exception) {
	        $e = reset($args);
	        $args = array('type'=>get_class($e), 'message'=>$e->getMessage(), 'code'=>$e->getCode, 'file'=>$e->getFile, 'line'=>$e->getLine, 'trace'=>$e->getTraceAsString());
	    }

		$replace = array();
		foreach (array_keys($args) as $key) $replace[] = '%{' . $key . '}';

        foreach (array_keys($args) as $key) {
            if (!is_scalar($args[$key])) $args[$key] = $this->arrayImplode['prefix'] . implode_recursive($this->arrayImplode['glue'], $args[$key], $this->arrayImplode['prefix'], $this->arrayImplode['suffix']) . $this->arrayImplode['suffix'];
            if (!empty($this->formatValue)) $args[$key] = sprintf($this->formatValue, $this->quote ? addcslashes($key, '"') : $key, $this->quote ? addcslashes($args[$key], '"') : $args[$key]);
              elseif ($this->quote) $args[$key] = '"' . addcslashes($args[$key], '"') . '"';
        }
		$line = str_ireplace($replace, array_values($args), $template);
		
		if (strpos($line, '%') !== false) {
			$line = preg_replace_callback('/%\{([^\}]++)\}/', array($this, 'quoteEventValue'), $line);
		}

        if ($this->singleline) $line = str_replace(array("\n", "\r"), array(chr(182), ''), $line);		
		return $line;
	}
	
	/**
	 * Get the value of a log event item, quote if needed.
	 * 
	 * @param string $key
	 * @return string 
	 */
	protected function quoteEventValue($key)
	{
	    if (is_array($key)) $key = $key[1]; // for preg_replace_callback
	    
	    $value = $this->_eventValues[$key];
        if (!is_scalar($value)) $value = $this->arrayImplode['prefix'] . implode_recursive($this->arrayImplode['glue'], $value, $this->arrayImplode['prefix'], $this->arrayImplode['suffix']) . $this->arrayImplode['suffix'];

        if (!empty($this->formatValue)) $value = sprintf($this->formatValue, $this->quote ? addcslashes($key, '"') : $key, $this->quote ? addcslashes($value, '"') : $value);
          elseif ($this->quote) $value = '"' . addcslashes($value, '"') . '"';
        
	    return $value;
	}
	
	
    /**
     * Magic method to set a property.
     * Used to protect the eventValues property.
     * 
     * @param string $var
     * @param mixed  $value
     */
    public function __set($var, $value)
    {
        if (strtolower($var) == 'eventvalues') {
            if (!is_array($value) && !($value instanceof ArrayAccess)) {
                trigger_error("Property eventValues can only be an array, not a " . gettype($value) . ".", E_USER_WARNING);
                return;
            }
            $this->_eventValues = new Log_EventValues($value);
        }
        
        $this->$var = $value; 
    }    

    /**
     * Magic method to get a property.
     * Used to protect the eventValues property.
     *
     * @param string $var
     * @return mixed
     */
    public function __get($var)
    {
        if (strtolower($var) == 'eventvalues') return $this->_eventValues;
        trigger_error('Undefined property: ' . get_class($this) . "::$var", E_USER_NOTICE); 
    }
    
    
    /**
     * Zend_Log compatibilty; Log an 'emerg' message
     * 
     * @param string $message
	 */
    public function emerg($message)
    {
		$this->write($message, __FUNCTION__);
    }
    
    /**
     * Zend_Log compatibilty; Log an 'alert' message
     * 
     * @param string $message
	 */
    public function alert($message)
    {
		$this->write($message, __FUNCTION__);
    }
    
    /**
     * Zend_Log compatibilty; Log a 'crit' message
     * 
     * @param string $message
	 */
    public function crit($message)
    {
		$this->write($message, __FUNCTION__);
    }
    
    /**
     * Zend_Log compatibilty; Log a 'warn' message
     * 
     * @param string $message
	 */
    public function warn($message)
    {
		$this->write($message, __FUNCTION__);
    }
    
    /**
     * Zend_Log compatibilty; Log a 'notice' message
     * 
     * @param string $message
	 */
    public function notice($message)
    {
		$this->write($message, __FUNCTION__);
    }
    
    /**
     * Zend_Log compatibilty; Log an 'info' message
     * 
     * @param string $message
	 */
    public function info($message)
    {
		$this->write($message, __FUNCTION__);
    }

    /**
     * Zend_Log compatibilty; Log a 'debug' message
     * 
     * @param string $message
	 */
    public function debug($message)
    {
		$this->write($message, __FUNCTION__);
    }
}


/**
 * Mock object to create Log instance.
 * @ignore 
 */
class Log_Mock
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
	 * Create a new Log interface.
	 *
	 * @param string|array $dsn     DSN/driver (string) or array(driver[, arg1, ...])
	 * @param array        $filter
	 * @param array        $props   Values for public properties
	 * @return Log
	 */
	public function to($dsn, $filters=array(), $props=array())
	{
    	$instance = Log::to($dsn, $props);
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
        if (Log::$name()->exists()) trigger_error("Illigal use of mock object 'Q\Log::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Log interface '{$this->_name}' does not exist.");
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
        if (Log::$name()->exists()) trigger_error("Illigal use of mock object 'Q\Log::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Log interface '{$this->_name}' does not exist.");
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
        if (Log::$name()->exists()) trigger_error("Illigal use of mock object 'Q\Log::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Log interface '{$this->_name}' does not exist.");
    }
}
