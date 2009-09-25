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
	 */
	public function __construct($message, $err=null)
	{
		if (isset($err) && is_array($err)) {
			$message .= (strpos($err['message'], ':') === false ? ': ' : '') . strpbrk($err['message'], ':');
			unset($err);
		}
		
		parent::__construct($message, $err);
	}
}
