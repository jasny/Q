<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * Exception for an error returned from the RPC server.
 * 
 * @package RPC
 */
class RPC_Fault extends Exception
{	
	/**
	 * Information about the RPC connection
	 * @var string
	 */
	protected $connect_info;

	/**
	 * Additional details about the error, not suitable to show the user
	 * @var string
	 */
	protected $details;
	
	/**
	 * Class constructor
	 *
	 * @param string $connect_info  Information about the RPC connection
	 * @param string $message       Message to show the user
	 * @param int    $code
	 * @param string $details       Additional details about the error, not suitable to show the user
	 */
	public function __construct($connect_info, $message, $code=0, $details=null)
	{
		parent::__construct($message, $code);
		
		$this->connect_info = is_object($connect_info) ? $connect_info->about() : $connect_info;
		$this->details = $details; 
	}

	
	/**
	 * Get information about the RPC connection
	 *
	 * @return string
	 */
	public function getConnectInfo()
	{
		return $this->connect_info;
	}
		
	/**
	 * Get additional details about the error
	 *
	 * @return string
	 */
	public function getDetails()
	{
		return $this->details;
	}

	/**
	 * Cast object to string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return "RPC Fault on {$this->connect_info}: {$this->details}";
	}
}

?>