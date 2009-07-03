<?php
namespace Q;

require_once 'Q/Log/Handler.php';
require_once 'Zend/Log.php';

/**
 * Wrapper class to use Zend_Log object in Q.
 * 
 * @package Log
 */
class Log_Zend implements Log_Handler  
{
	/**
	 * Alias for types
	 * @var array
	 */
	public $alias = array(
		null=>\Zend_Log::INFO,
		'emerg'=>\Zend_Log::EMERG,
		'alert'=>\Zend_Log::ALERT,
		'crit'=>\Zend_Log::CRIT,
		'err'=>\Zend_Log::ERR,
		'warn'=>\Zend_Log::WARN,
		'notice'=>\Zend_Log::NOTICE,
		'info'=>\Zend_Log::INFO,
		'debug'=>\Zend_Log::DEBUG
	);
	
	/**
	 * Wrapped zend log object.
	 * @var Zend_Log
	 */
	protected $zendlog;
	
	
	/**
	 * Class constructor.
	 *
	 * @param Zend_Log $zendlog
	 */
	function __construct(\Zend_Log $zendlog)
	{
	    $this->zendlog = $zendlog;		
	    parent::__construct();
	}
	
	
	/**
	 * Add a filter to log/not log messages of a specific type.
	 * Fluent interface.
	 *
	 * @param string  $type    Filter type, action may be negated by prefixing it with '!'
	 * @param boolean $action  Q\Log::FILTER_* constant
	 * @return Log
	 */
	public function setFilter($type, $action=LOG::FILTER_INCLUDE)
	{
		if ($type[0] == '!') {
			$action = !$action;
			$type = substr($type, 1);
		}
		
        if ($action == LOG::FILTER_EXCLUDE) {
            trigger_error("Filters to exclude types aren't supported in Zend_Log.", E_USER_WARNING);
            return;
        }
        
        $priority = isset($this->alias[$type]) ? $this->alias[$type] : $this->alias[null];
        $filter = new Zend_Log_Filter_Priority($priority);
        $logger->addFilter($filter);
	}
    
	
	/**
	 * Log a message.
	 *
	 * @param string $message
	 * @param string $type
	 */
	function write($message, $type=null)
	{
	    if (isset($message['type']) && !isset($type)) $type = $message['type'];
	    unset($message['type']);
	    
		if (is_array($message)) $message = count($message) == 1 ? reset($message) : '[' . join('] [', $message) . ']';
		
		try {
			$this->zendlog->log($message, isset($this->alias[$type]) ? $this->alias[$type] : $this->alias[null]);
		} catch (\Exception $e) {
			trigger_error('Logging using ' . get_class($this->zendlog) . ' failed: ' . $e->getMessage(), E_USER_WARNING);
		}
	}
	
	/**
	 * Magic method: call method for Zend_Log.
	 *
	 * @param string $method
	 * @param string $args
	 * @return mixed
	 */
	function __call($method, $args)
	{
		return call_user_func_array(array($this->zendlog, $method), $args);
	}
}

?>