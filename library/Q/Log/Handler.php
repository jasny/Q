<?php

namespace Q;

/**
 * Interface for any class that can log messages.
 * 
 * @package Log
 */
interface Log_Handler
{
	/**
	 * Log a message.
	 *
	 * @param string $message
	 * @param string $type
	 */
	function log($message, $type=null);
	
	/**
	 * Add a filter to log/not log messages of a specific type.
	 * Fluent interface.
	 *
	 * @param string  $type   Filter type, action may be negated by prefixing it with '!'
	 * @param boolean $action Q\Log::FILTER_* constant
	 * @return Log
	 */
	public function setFilter($type, $action=LOG::FILTER_INCLUDE);
}

?>