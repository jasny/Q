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
		throw new Fs_Exception("Unable to get contents of socket '{$this->_path}'. Use Fs_Socket::open() + fread() instead");
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
        throw new Fs_Exception("Unable to write contents to socket '{$this->_path}'. Use Fs_Socket::open() + fwrite() instead");
	}
	
	/**
	 * Output contents of the file.
	 * 
	 * @return int
	 */
	public function output()
	{
		throw new Fs_Exception("Unable to get contents of socket '{$this->_path}'. Use Fs_Socket::open() + fread() instead");
	}
	
	/**
	 * Open the Unix domain socket connection as a client.
	 * @see http://www.php.net/stream_socket_client 
	 * 
	 * @return resource
	 */
	public function open($mode='r+')
	{
		$errno = null;
		$errstr = null;
		$resource = @stream_socket_client('unix://' . $this->_path, $errno, $errstr);
        
		if (!$resource) throw new Fs_Exception("Failed to open socket '{$this->_path}'" . ($errstr ? ": $errstr" : ''), error_get_last());
		return $resource;
	}
	
    /**
     * Open the Unix domain socket connection as a server.
     * @see http://www.php.net/stream_socket_server
     * 
     * @return resource
     */
    public function listen()
    {
        $errno = null;
        $errstr = null;
        $resource = @stream_socket_server('unix://' . $this->_path, $errno, $errstr);
        
        if (!$resource) throw new Fs_Exception("Failed to create socket '{$this->_path}'" . ($errstr ? ": $errstr" : ''), error_get_last());
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
 		throw new Fs_Exception("Unable to create socket '{$this->_path}'. Use Fs_Socket::listen() instead");
 	}
 	
    /**
     * Copy or rename/move this file.
     * 
     * @param callback $fn     copy or rename
     * @param Fs_Dir   $dir
     * @param string   $name
     * @param int      $flags  Fs::% options as binary set
     * @throws Fs_Exception
     */
    protected function doCopyRename($fn, $dir, $name, $flags=0)
    {
        throw new Fs_Exception("Unable to " . ($fn == 'rename' ? 'move' : $fn). " '{$this->_path}': File is a socket");
    }
}
