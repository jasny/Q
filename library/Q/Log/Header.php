<?php
namespace Q;

require_once 'Q/Log.php';
require_once 'Q/HTTP.php';

/**
 * Log by sending a HTTP header.
 * 
 * @package Log
 */
class Log_Header extends Log
{
	/**
	 * Counter
	 * @var int
	 */
	static protected $counter=0;

		
	/**
	 * Log a message.
	 *
	 * @param string $message
	 * @param string $type
	 */
	protected function writeLine($message, $type)
	{
	    if (empty($type)) $type = "Log";
		HTTP::header('X-' . ucfirst(strtolower($type)) . '-' . ++self::$counter . ': ' . str_replace(array("/r", "/n", "/t"), " ", $message));
	}
	
	/**
	 * Get the line for logging joining the arguments
	 *
	 * @param array $args
	 * @return string
	 */
	protected function getLine_Join($args)
	{
	    unset($args['type']);
	    return parent::getLine_Join($args);	
	}
}

?>