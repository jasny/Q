<?php
namespace Q;

/**
 * Interface for any kind of RPC server.
 * 
 * @package RPC
 * @subpackage RPC_Server
 */
interface RPC_Server_Handler
{
	/**
	 * Sets class which will handle RPC requests.
	 * Fluent interface.
	 *
	 * @param string $classname
	 * @param                    Additional arguments will be passed to the contructor
	 * @return RPC_Server_Handler
	 */
	public function setClass($classname);

	/**
	 * Sets object which will handle RPC requests
	 * Fluent interface.
	 * 
	 * @param object $object
	 * @return RPC_Server_Handler
	 */
	public function setObject($object);

	/**
	 * Gets object which handles RPC requests
	 * 
	 * @return object
	 */
	public function getObject();
	
	
	/**
	 * Handle RPC request(s).
	 * Fluent interface.
	 *
	 * @param boolean|string $request  RPC request (string), FALSE read from php://input once or TRUE to keep listing on php://input for multiple requests
	 * @return RPC_Server_Handler
	 */
	public function handle($request=false);
	
	
	/**
	 * Put additional information on an alternative channel (eg: http headers or stderr).  
	 * Fluent interface.
	 * 
	 * @param string $type
	 * @param string $value
	 * @return RPC_Server_Handler
	 */
	public function putExtraInfo($type, $value);
}

?>