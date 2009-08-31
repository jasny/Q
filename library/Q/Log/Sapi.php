<?php
namespace Q;

require_once 'Q/Log/Text.php';

/**
 * Log using the error logger of the webserver.
 * 
 * @package Log
 */
class Log_Sapi extends Log
{
	/**
	 * Write the log entry.
	 *
	 * @param array $args
	 */
    protected function write($args)
    {
		error_log($this->getLine($args), 0);
    }
}

