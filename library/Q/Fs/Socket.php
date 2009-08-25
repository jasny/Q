<?php
namespace Q;

require_once 'Q/Fs/Item.php';

/**
 * Interface of a socket file.
 * 
 * @package Fs
 */
class Fs_Socket extends Fs_Item
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (!file_exists($path)) throw new Fs_Exception("Can't load socket '$path'; File doesn't exists."); 
		if (filetype($path) != 'socket') throw new Fs_Exception("File '$path' is not a socket, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
	}
	
	/**
	 * Open the Unix domain socket connection.
	 * @see http://www.php.net/stream_socket_client 
	 * 
	 * @return resource
	 */
	public function open()
	{
		$errno = null;
		$errstr = null;
		$resource = stream_socket_client('unix://' . $this->path, $errno, $errstr);

		if (!$resource) throw new Fs_Exception("Failed to open socket; " . $errstr);
		return $resource;
	}	
}
