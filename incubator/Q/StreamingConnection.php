<?php
namespace Q;

/**
 * Interface for stream wrappers.
 * 
 * @package Connection
 */
interface StreamingConnection
{
	/**
	 * Check if the connection is open.
	 */
	public function isOpen();
	
	/**
	 * Reopen a closed connection.
	 */
	public function reconnect();
	
	/**
	 * Close the connection.
	 */
	public function close();

	
	/**
	 * Get the stream.
	 * 
	 * @return resource
	 */
	public function forInput();
	
	/**
	 * Get the stream.
	 * 
	 * @return resource
	 */
	public function forOutput();

	
	/**
	 * Get extra info from meta data.
	 * 
	 * @return string
	 */
	public function getExtraInfo();
	
	/**
	 * Return information about the connection.
	 *
	 * @return string
	 */
	public function about();
}

<<<<<<< HEAD:incubator/Q/StreamingConnection.php
=======
?>
>>>>>>> d2e0614a15905957b34d6b2bda5e2705da749dc6:incubator/Q/StreamingConnection.php
