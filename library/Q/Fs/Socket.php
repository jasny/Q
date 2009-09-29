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
		parent::__construct($path);
		
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (file_exists($path) && filetype(realpath($path)) != 'socket') throw new Fs_Exception("File '$path' is not a socket, but a " . filetype($path) . ".");
	}
	

	/**
	 * Reads all contents from socket into a string.
	 * 
	 * @param int $flags   FILE_% flags as binary set.
	 * @param int $offset  The offset where the reading starts.
	 * @param int $maxlen  Maximum length of data read.
	 * @return string
	 */
	public function getContents($flags=0, $offset=0, $maxlen=null)
	{
		$ret = isset($maxlen) ?
		 @file_get_contents($this->_path, $flags, null, $offset, $maxlen) :
		 @file_get_contents($this->_path, $flags, null, $offset);
		 
		if ($ret === false) throw new Fs_Exception("Failed to read from socket '{$this->_path}'", error_get_last());
		return $ret;
	}

	/**
	 * Write a string to a file.
	 * 
	 * @param mixed $data   The data to write; Can be either a string, an array or a stream resource. 
	 * @param int   $flags  Fs::RECURSIVE and/or FILE_% flags as binary set.
	 * @return int
	 */
	public function putContents($data, $flags=0)
	{
		$ret = @file_put_contents($this->_path, $data, $flags);
		if ($ret === false) throw new Fs_Exception("Failed to write to socket '{$this->_path}'", error_get_last());
		return $ret;
	}
	
	/**
	 * Output contents of the file.
	 * 
	 * @return int
	 */
	public function output()
	{
		readfile($this->_path);
	}
		
	/**
	 * Open the Unix domain socket connection.
	 * @see http://www.php.net/stream_socket_client 
	 * 
	 * @return resource
	 */
	public function open($mode='r+')
	{
		$errno = null;
		$errstr = null;
		$resource = stream_socket_client('unix://' . $this->_path, $errno, $errstr);

		if (!$resource) throw new Fs_Exception("Failed to open socket '{$this->_path}': " . $errstr);
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
