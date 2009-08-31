<?php
namespace Q;

/**
 * Array object for event values.
 * 
 * Static event variables can be set in advance and used as event value.
 * Use PHP closures if you need to use callbacks. 
 * 
 * @package Log
 */
class Log_EventValues extends \ArrayObject
{
	/**
	 * Variable values for events.
	 * @var array
	 */
	public static $vars;

	
	/**
	 * Add a variable value which will be used in the log line.
	 * This is only needed in combination with Log_EventValues::getAll().
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
		
		if (isset($this->values[$key])) $var = $this->values[$key];
		 elseif (isset(self::$vars[$key])) $var = self::$vars[$key];
		 else return null;

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
	    $values = $this->values;
	    
		foreach ($values as &$var) {
    		if ($var instanceof \Closure) $var = $var();
		}
		return $values;
	}

	
    /**
     * Set the default event variables.
     */
    public static function initVars()
    {
    	self::$vars = array();
        self::$vars['application'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : ($_SERVER['PWD'] == '/' ? '' : $_SERVER['PWD']) . '/' . $_SERVER['SCRIPT_NAME'];
        self::$vars['server'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : php_uname('n');
        
        self::$vars['phpversion'] = phpversion();
        self::$vars['system-pid'] = getmypid();
        $sysuser = posix_getpwuid(posix_getuid());
        self::$vars['system-user'] = $sysuser['name'];
        
        self::$vars['system-info'] = php_uname();
        self::$vars['system-os'] = php_uname('s');
        self::$vars['system-hostname'] = php_uname('n');
        self::$vars['system-kernel'] = php_uname('r');
        self::$vars['system-versioninfo'] = php_uname('v');
        self::$vars['system-machine'] = php_uname('m');
        
        self::$vars['error_prepend'] = ini_get('error_prepend_string');
        self::$vars['error_append'] = ini_get('error_append_string');
        
        self::$vars['time'] = function () { return date("Y-m-d H:i:s O"); };
        self::$vars['trace'] = function () { return serialize_trace(debug_backtrace(), 2); };
        
        self::$vars += array_change_key_case($_SERVER, CASE_LOWER);
        
        if (class_exists('Q\Config', false) && Config::i()->exists() && Config::i()->log_vars) {
        	self::$vars += (array)Config::i()->log_vars;
        }
    }
}

Log_EventValues::initVars();
