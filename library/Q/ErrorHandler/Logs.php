<?php
namespace Q;

/**
 * Array object for log handlers of error handler.
 * 
 * @package ErrorHandler
 */
class ErrorHandler_Logs implements \IteratorAggregate, \ArrayAccess, \Countable  
{
	/**
	 * The log handlers that should be called when an error occurs. 
	 * @var array  
	 */
	protected $logs;
	
	
	/**
	 * Class constructor.
	 *
	 * @param array $logs
	 */
	public function __construct($logs=array())
	{
	    $this->logs = (array)$logs;
	} 
	
    /**
     * Get binary set of error number for type string
     *
     * @param string $type
     * @return int
     */
	protected static function errnoForType($type)
	{
		// Type might be an E_* constant as string or an expression of those
		if(substr($type, 0, 2) == 'E_' && defined($type)) {
			$type = constant($type);
		} elseif (preg_match('/[\|\&\~]/', $type)) {
			if (!preg_match('/^[\s|\(]*(?:\w++|\)|[\|\&\~][\s|\(]*|\s++)*$/', $type)) {
				trigger_error("Found potential harmfull code for type expression at set actions: skipping. $type", E_USER_WARNING);
				return $this;
			}
			$type = (int)eval("return {$type};"); 
		}
	}
	
	/**
	 * Add a log handler.
	 * Works as a fluent interface.
	 * 
	 * @param mixed         $type  Error number (int) or Exception class name.
	 * @param Q\Log_Handler $log   May also be a DSN string.
	 * @param More logs can be specified as additional arguments.
	 * @return ErrorHandler_Log
	 */
	public function setLog($type, $log)
	{
		if (is_string($type)) $type = self::errnoForType($type);
	    
		$logs = array();
		$logs_dsn = func_get_args();
		array_shift($logs_dsn);
		
		foreach ($logs_dsn as $log_dsn) {
		    try {
		        $logs[] = Log::create($log_dsn); 
		    } catch (Exception $e) {
                trigger_error("Failed to create Log '$log_dsn'.\n" . (string)$e, E_USER_WARNING);
		    }
		}
		
		// Set logs for specific error code(s) or exception
		if (is_int($type)) {
    		for ($i=1; $i<=4096; $i = $i << 1) {
    		    if ($i & $type) $this->addLogForType($type, $log);
    		}
        } else {
            $this->addLogForType($type, $log);
		}
		
		return $this;
	}
		
	/**
	 * Add a log handler for a type.
	 * 
	 * @param int|string     $type  Error number (int) or Exception class name.
	 * @param Q\Log_Handler $log
	 */
	protected function addLogForType($type, Q\Log_Handler $log)
    {
		if (isset($this->logs[$type])) {
            if (!($this->logs[$type] instanceof Q\Log_Container)) $this->logs[$type] = new Q\Log_Container($this->logs[$type]);  
            $this->logs[$type]->add($log);
        } else {
		    $this->logs[$type] = is_array($log) ? new Q\Log_Container($log) : $log;
		}
    }
	
	
	/**
	 * Get the appropriate log handlers for an error or exception.
	 *
	 * @param mixed $type  Error number (int) or Exception object
	 */
	protected function getLogs($type)
	{
		if (!is_object($type)) {
			$logs = isset($this->logs[$type]) ? array($this->logs[$type]) : array();
		} else {
		    $logs = array();
		    foreach ($this->logs as $logtype=>$log) {
                if ($type instanceof $logtype) $logs[] = $log;
		    }
		}
		
		return $logs;
	}
	
	
	/**
	 * Create a new iterator.
	 * Needed for IteratorAggregate 
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
	    return new ArrayIterator($this->logs);
	}

	/**
	 * Check whether the offset exists.
	 * Needed for ArrayAccess
	 *
	 * @param string $key
	 */
	public function offsetExists($key)
	{
	    return (bool)$this->getLogs($key);
	}

	/**
	 * Alias for getValue.
	 * Needed for ArrayAccess
	 *
	 * @param string $key
	 */
	public function offsetGet($key)
	{
	    return $this->getLogs($key);
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
	    
	    $this->setLog($key, $value);
	}
	
	/**
	 * Remove a value.
	 * Needed for ArrayAccess 
	 *
	 * @param string $key
	 */
	public function offsetUnset($key)
	{
	    if (is_string($key)) $key = self::errnoForType($key);
	    
		// Set logs for specific error code(s) or exception
		if (is_int($key)) {
    		for ($i=1; $i<=4096; $i = $i << 1) {
    		    if ($i & $key) unset($this->logs[$key]);
    		}
        } else {
            unset($this->logs[$key]);
		}	    
	}
	
	/**
	 * Count the number of event values.
	 * Needed for Countable
	 * 
	 * @return int
	 */
	public function count()
	{
	    return count($this->logs);
	}
	
	/**
	 * Check whether array contains more entries.
	 * Needed for Iterator
	 * 
	 * @return boolean
	 */
	public function valid()
	{
	    return key($this->logs) === null;
	}	
}
