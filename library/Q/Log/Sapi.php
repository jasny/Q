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
	 * Class constructor
	 */
	public function __construct()
	{
	}
    
	/**
	 * Write the log entry
	 *
	 * @param string $line
	 * @param string $type
	 */
    protected function writeLine($line, $type)
    {
		error_log($line, 0);
    }
}

?>