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
	 * @param string|array $message  Message or associated array with info
	 * @param string       $type
	 */
	function log($message, $type=null);
	
	/**
	 * Magic invoke; Alias of Log_Handler::log().
	 * 
	 * @param string|array $message  Message or associated array with info
	 * @param string       $type
	 */
	public static function __invoke($message, $type);
	
	
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

