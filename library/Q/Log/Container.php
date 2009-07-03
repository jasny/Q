<?php
namespace Q;

require_once 'Q/Log.php';

/**
 * Combine multiple log objects.
 * Log::Container objects can be threated like an array.
 * 
 * @package Log
 */
class Log_Container extends Log implements IteratorAggregate, ArrayAccess, Countable
{
	/**
	 * Log handlers
	 * @var array
	 */
	protected $logs = array();
	
	/**
	 * Class constructor
	 *
	 * @param Arguments will be added as logs
	 */
	public function __construct()
	{
		if (func_num_args() > 0) {
			$args = func_get_args();
			$this->add($args);
		}
	}

	
	/**
	 * Return an iterator to walk through the nodes (as required by the IteratorAggregate interface)
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->logs);
	}
	
	/**
	 * Check if the offset exists. 
	 *
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return isset($this->logs[$offset]);
	}
	
	/**
	 * Return value at given offset. 
	 *
	 * @param mixed $offset
	 * @return Log_Handler
	 */
	public function offsetGet($offset)
 	{
		return $this->logs[$offset];
 	}
 	
	/**
	 * Set value at given offset. 
	 *
	 * @param mixed $offset
	 * @param mixed $log
	 */ 	
 	public function offsetSet($offset, $log)
 	{
 		if (is_string($log)) {
 			$log = Log::create($log);
 		} elseif (!($log instanceof Log_Handler)) {
 			trigger_error("Can't " . (isset($offset) ? "add log handler " : "set log handler at offset $offset") . ": value is not a Log::Handler or a DSN string.", E::USER_WARNING);
 			return; 
 		}
 		
 		if (!isset($offset)) {
 		    $this->logs[] = $log;
 		} else {
 		    $this->logs[$offset] = $log;
 		}
 	}
 	
	/**
	 * Delete the item at given offset 
	 *
	 * @param mixed $offset
	 */
 	public function offsetUnset($offset)
 	{
 		unset($this->logs[$offset]);
 	}
	
 	/**
 	 * Count the number of log handlers
 	 *
 	 * @return int
 	 */
 	public function count()
 	{
 		return count($this->logs);
 	}
 	
 	
 	/**
 	 * Add any number of log handlers.
 	 * (fluent interface)
 	 *
 	 * @param mixed $log  Log_Handler, dsn (string) or a set of log handlers as array  
 	 * @param More log handlers may be specified as additional arguments
 	 * @return Log_Container
 	 */
 	public function add($log)
 	{
 		if (func_num_args() > 1) $log = func_get_args();
 		
 		if (is_string($log)) {
 			try {
 				$log = Log::create($log);
 			} catch (\Exception $e) {
	 			trigger_error("Creation of log handler failed. " . $e->getMessage(), E_USER_WARNING);
	 			return $this;
			}
 		}
 		
 		if (is_array($log)) {
 			foreach ($log as $curlog) $this->add($curlog);
 			return $this;
 		}
 		
 		if (!($log instanceof Log_Handler)) {
 			trigger_error("Can't add log handler: value is not a " . __NAMESPACE__ . "::Log_Handler, DSN string or array.", E_USER_WARNING);
 			return $this;
 		}
 		
 		$this->logs[] = $log;
 		
 		return $this;
 	}
 	
 	
	/**
	 * Log a message.
	 *
	 * @param string $message
	 * @param string $type
	 */
	public function write($message, $type=null)
	{
		foreach ($this->logs as $log) {
			$log->log($message, $type);
		}
	}
}

?>