<?php
namespace Q;

/**
 * Interface for any class that can log messages.
 * 
 * @package Log
 */
interface Logger
{
	/**
	 * Log a message.
	 *
	 * @param string|array $message  Message or associated array with info
	 * @param string       $type
	 */
	function log($message, $type=null);
}
