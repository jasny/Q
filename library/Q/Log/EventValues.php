<?php
namespace Q;

/**
 * Array object for event values.
 * 
 * @package Log
 */
class Log_EventValues implements \Iterator, \ArrayAccess, \Countable  
{
	/**
	 * Variable values for events.
	 * @var array
	 */
	public static $vars;

	/**
	 * Event values.
	 * @var array
	 */
	public $values = array();
	
	
	/**
	 * Class constructor
	 *
	 * @param array $values
	 */
	public function __construct($values=array())
	{
	    $this->values = (array)$values;
	} 
	
	/**
	 * Add a value which can be used in the log line.
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function setValue($key, $value)
	{
		$this->values[$key] = $value;
	}

	/**
	 * Add a variable value which can be used in the log line.
	 *
	 * @param string $key
	 * @param string $var
	 */
	public function useVar($key, $var=null)
	{
	    if (!isset($var)) $var = $key;
	    
        if (!isset(self::$vars[$var])) self::$vars[$var] = null;
	    $this->values[$key] =& self::$vars[$var];
	}
	
	/**
	 * Get the value of a log event item.
	 * 
	 * @param string $key
	 * @return string 
	 */
	public function getValue($key)
	{
		if (is_array($key)) $key = $key[1]; // for preg_replace_callback
		
		if (isset($this->values[$key])) {
    		$var = $this->values[$key];
		} elseif (isset(self::$vars[$key])) {
            $var = self::$vars[$key];
		} else {
    		return null;
		}

		if ($var instanceof \Closure) $var = $var();
		return $var;
	}

	/**
	 * Get the values of all log event items.
	 * 
	 * @return array
	 */
	public function getAll()
	{
	    $values = array();
	    
		foreach ($this->values as &$var) {
    		if ($var instanceof \Closure) $var = $var();
		}
		
		return $values;
	}

	
    /**
     * Set the default event variables.
     */
    public static function initVars()
    {
        self::$vars['application'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : php_uname('n');
        
        self::$vars['phpversion'] = phpversion();
        self::$vars['system-pid'] = getmypid();
        $sysuser = posix_getpwuid(posix_getuid());
        self::$vars['system-user'] = $sysuser['name'];
        
        self::$vars['system-info'] = php_uname();
        self::$vars['system-os'] = php_uname('o');
        self::$vars['system-hostname'] = php_uname('h');
        self::$vars['system-kernel'] = php_uname('r');
        self::$vars['system-versioninfo'] = php_uname('v');
        self::$vars['system-machine'] = php_uname('m');
        
        self::$vars['ini-prepend'] = ini_get('error_prepend_string');
        self::$vars['ini-append'] = ini_get('error_append_string');
        
        self::$vars['time'] = function () { return strftime("%Y-%m-%d %H:%M:%S"); };
        self::$vars['trace'] = function () { return serialize_trace(debug_backtrace(), 2); };
        
        self::$vars += array_change_key_case($_SERVER, CASE_LOWER);
    }
    
	/**
	 * Check whether the offset exists.
	 * Needed for ArrayAccess
	 *
	 * @param string $key
	 */
	public function offsetExists($key)
	{
	    return array_key_exists($key, $this->values);
	}

	/**
	 * Alias for getValue.
	 * Needed for ArrayAccess
	 *
	 * @param string $key
	 */
	public function offsetGet($key)
	{
	    return $this->getValue($key);
	}
	
	/**
	 * Add a value or var which can be used in the log line.
	 * Needed for ArrayAccess 
	 *
	 * @param string $key
	 * @param mixed  $value  Use '{$VAR}' to use a variable.
	 */
	public function offsetSet($key, $value)
	{
	    if (!isset($key)) {
            trigger_error(__CLASS__ . ' should only be used as associated array', E_USER_WARNING);
            return;
	    }
	    
	    $matches = null;
        if (preg_match('/^\\${([^}]++)}$/', $key, $matches)) $this->useVar($key, $matches[1]);
         else $this->setValue($key, $value);
	}
	
	/**
	 * Remove a value.
	 * Needed for ArrayAccess 
	 *
	 * @param string $key
	 */
	public function offsetUnset($key)
	{
	    unset($this->values[$key]);
	}
	
	/**
	 * Count the number of event values.
	 * Needed for Countable
	 * 
	 * @return int
	 */
	public function count()
	{
	    return count($this->values);
	}
	
	/**
	 * Return the current element. 
	 * Needed for Iterator
	 *
	 * @return string
	 */
	public function current()
	{
	    $var = current($this->values);
		if ($var instanceof Closure) $var = $var();
		
		return is_scalar($var) ? $var : (string)$var;
	}
	
	/**
	 * Return the key of the current element. 
	 * Needed for Iterator
	 * 
	 * @return string
	 */
	public function key()
	{
	    return key($this->values);
	}
	
	/**
	 * Move forward to next element.
	 * Needed for Iterator
	 */
	public function next()
	{
	    next($this->values);
	}
	
	/**
	 * Rewind array back to the start.
	 * Needed for Iterator
	 */
	public function rewind()
	{
	    reset($this->values);
	}
	
	/**
	 * Check whether array contains more entries.
	 * Needed for Iterator
	 * 
	 * @return boolean
	 */
	public function valid()
	{
	    return key($this->values) !== null;
	}
}

Log_EventValues::initVars();

?>