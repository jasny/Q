<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a socket file.
 * 
 * @package Fs
 */
class Fs_Socket extends Fs_Node
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (file_exists($path) && filetype(realpath($path)) != 'socket') throw new Fs_Exception("File '$path' is not a socket, but a " . filetype($path) . ".");
		 
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
		$resource = stream_socket_client('unix://' . $this->_path, $errno, $errstr);

		if (!$resource) throw new Fs_Exception("Failed to open socket: " . $errstr);
		return $resource;
	}
	
 	/**
 	 * Create this file.
 	 * Use Fs::PRESERVE to simply return if file already exists
 	 * 
 	 * @param int $mode   File permissions, umask applies
 	 * @param int $flags  Fs::% options
 	 * @throws Fs_Exception
 	 */
	public function create($mode=0666, $flags=0)
 	{
 		if ($this->exists() && $flags & Fs::PRESERVE) return;
 		throw new Fs_Exception("Unable to create socket '{$this->_path}'. Use Fs_Socket::open() instead");
 	}
}
