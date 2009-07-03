<?php
namespace Q;

/**
 * Interface for any kind of RPC client.
 * 
 * @package RPC
 * @subpackage RPC_Client
 */
interface RPC_Client_Handler
{
	/**
	 * Close the connection.
	 * If possible the connection should be reopened when execute is called.
	 */
	public function close();
	
	/**
	 * Call a function on the server.
	 * 
	 * @param string $function  Function name
	 * @param array  $args      Function arguments
	 * @return mixed
	 */
	public function execute($function, $args);
	
	/**
	 * Get interface for making remote calls.
	 *
	 * @return RPC_Client_Iface
	 */
	public function getInterface();
	
	/**
	 * Grab any extra info the server has send on an alternative channel.
	 * These are usually warning and notices.
	 * 
	 * @param string  $type  Find specific extra info
	 * @param boolean $all   Return the extra info of all calls made, instead of only of the last call
	 * @return array
	 */
	public function getExtraInfo($type=null, $all=false);
	
	/**
	 * Get information about the connection
	 *
	 * @return string
	 */
	public function about();	
}

?>