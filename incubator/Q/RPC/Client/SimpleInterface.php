<?php
namespace Q;

require_once 'Q/RPC/Client/Interface.php';

/**
 * Basic implementation of an RPC client interface.
 * Methods called on the this interface directed to the server.
 * 
 * @package RPC
 * @subpackage RPC_Client
 */
class RPC_Client_SimpleInterface implements RPC_Client_Interface
{
	/**
	 * RPC connection
	 * @var RPC_Client_Handler
	 */
	protected $__connection;
	
	/**
	 * Class constructor
	 *
	 * @param RPC_Client_Handler $connection
	 */
	function __construct($connection)
	{
		$this->__connection = $connection;
	}

	/**
	 * Magic method for calling
	 *
	 * @param string $function
	 * @param array  $args
	 */
	function __call($function, $args)
	{
		return $this->__connection->execute($function, $args);
	}
	
	
	/**
	 * Return the bound RPC client
	 *
	 * @return RPC_Client_Handler
	 */
	function __getConnection()
	{
		return $this->__connection;
	}
}

?>