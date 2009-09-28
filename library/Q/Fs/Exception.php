<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * Exception for Fs assertions.
 */
class Fs_Exception extends Exception
{
	/**
	 * Class constructor
	 * 
	 * @param string $message
	 * @param array  $err      Return of error_get_last()
	 * @param mixed  $p2
	 */
	public function __construct($message, $err=null, $p2=null)
	{
		if (isset($err) && is_array($err) && isset($err['message'])) {
			$message .= (strpos($err['message'], ':') === false ? ': ' : '') . strpbrk($err['message'], ':');
			parent::__construct($message, $p2);
		} else {
			parent::__construct($message, $err, $p2);
		}
	}
}
