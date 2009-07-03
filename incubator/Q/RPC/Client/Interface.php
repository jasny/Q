<?php
namespace Q;

/**
 * Interface for RPC client.
 * Methods called on this interface are called on the server. 
 * 
 * @package RPC
 * @subpackage RPC_Client
 */
interface RPC_Client_Interface
{
	/**
	 * Return the bound RPC client
	 *
	 * @return RPC_Client_Handler
	 */
	function __getConnection();
}

?>