<?php
namespace Q;

require_once 'Q/Exception';

/**
 * Exception for when execution of an external program fails.
 */
class ExecException extends Exception
{
	/**
	 * Output of the command on stdout.
	 * @var string
	 */
	protected $stdout;

	/**
	 * Output of the command on stderr.
	 * @var string
	 */
	protected $stderr;
	
	
	/**
	 * Class constructor.
	 * 
	 * @param string $message
	 * @param string $stdout       Content of stdout
	 * @param string $stderr       Content of stderr
	 * @param int    $return_var
	 */
	public function __construct($message, $return_var=-1, $stdout=null, $stderr=null)
	{
		$this->stdout = $stdout;
		$this->stderr = $stderr;
		parent::construct($message, $return_var);
	}

	
	/**
	 * Get content of stdout.
	 * 
	 * @return string
	 */
	public function getStdout()
	{
		return $this->stdout;
	}

	/**
	 * Get content of stderr.
	 * 
	 * @return string
	 */
	public function getStderr()
	{
		return $this->stderr;
	}
	
	/**
	 * Get the return code.
	 * 
	 * @return int
	 */
	public function getReturnVar()
	{
		return $this->getCode();
	}
}
