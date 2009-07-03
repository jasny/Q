<?php
namespace Q;

require_once 'Q/RPC/Fault.php';

/**
 * Exception for an error returned from the RPC server.
 * 
 * @package RPC
 */
class RPC_XMLRPCFault extends RPC_Fault
{	
	/**
	 * Error code
	 * @var int
	 */
	public $faultCode;
	
	/**
	 * Error message
	 * @var string
	 */
	public $faultString;
	
	
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
		$this->faultCode = $code;
		$this->faultString = $message;
		
		$this->connect_info = is_object($connect_info) ? $connect_info->about() : $connect_info;
		$this->details = $details;
	}

	/**
	 * Cast object to string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return "XMLRPC Fault on {$this->connect_info}: " . $this->faultString . (isset($this->details) ? "\n\n" . $this->details : '');
	}
	
	
	/**
	 * Decode an XMLRPC fault
	 *
	 * @param string|array $fault
	 */
	public static function decode($connect_info, $fault, $details=null)
	{
		if (is_string($fault)) $fault = xmlRPC_decode($fault);
		if (!xmlRPC_is_fault($fault)) throw new Exception("Argument is not an XMLRPC fault.");
		
		return new self($connect_info, $fault['faultString'], $fault['faultCode'], $details);
	}
	
	/**
	 * Return fault as XMLRPC encoded string
	 *
	 * @return string
	 */
	public function encode()
	{
		return xmlRPC_encode($this);
	}
}

?>