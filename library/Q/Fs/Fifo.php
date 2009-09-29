<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a named pipe.
 * 
 * @package Fs
 */
class Fs_Fifo extends Fs_Node
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (file_exists($path) && filetype(realpath($path)) != 'fifo') throw new Fs_Exception("File '$path' is not a pipe, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
	}
	
 	
	/**
	 * Reads all contents from named pipe into a string.
	 * 
	 * @param int $flags   FILE_% flags as binary set.
	 * @param int $offset  The offset where the reading starts.
	 * @param int $maxlen  Maximum length of data read.
	 * @return string
	 */
	public function getContents($flags=0, $offset=0, $maxlen=null)
	{
		return isset($maxlen) ?
		 @file_get_contents($this->_path, $flags, null, $offset, $maxlen) :
		 @file_get_contents($this->_path, $flags, null, $offset);
		 
		if ($ret === false) throw new Fs_Exception("Failed to read file '{$this->_path}'", error_get_last());
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
		if ($flags & Fs::RECURSIVE) $this->up()->create(0770, Fs::RECURSIVE | Fs::PRESERVE);
		
		$ret = @file_put_contents($this->_path, $data, $flags);
		if ($ret === false) throw new Fs_Exception("Failed to write to named pipe '{$this->_path}'", error_get_last());
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
	 * Open the file.
	 * @see http://www.php.net/fopen
	 * 
	 * @param string $mode  The mode parameter specifies the type of access you require to the stream.
	 * @return resource
	 */
	public function open($mode='r+')
	{
		$resource = @fopen($this->_path, $mode);
		if (!$resource) throw new Fs_Exception("Failed to open named pipe '{$this->_path}'", error_get_last());
		return $resource;
	}
	
	
	/**
 	 * Create this named pipe.
 	 * Use Fs::PRESERVE to simply return if file already exists
 	 * 
 	 * @param int $mode   File permissions, umask applies
 	 * @param int $flags  Fs::% options
 	 * @throws Fs_Exception if creating the pipe fails
 	 */
	public function create($mode=0666, $flags=0)
 	{
 		if ($this->exists()) {
 			if ($flags & Fs::PRESERVE) return;
 			throw new Fs_Exception("Unable to create '{$this->_path}': Named pipe already exists");
 		}
 		
 		$file = $this->realpath();
		$dir = $file->up();
		if (!$dir->exists()) {
			if (~$flags & Fs::RECURSIVE) throw new Fs_Exception("Unable to create '{$file->_path}': Directory '{$dir->_path}' does not exist");
			$dir->create($mode | (($mode & 444) >> 2), $flags);
		}
 		
 		if (!extension_loaded('posix')) throw new Exception("Unable to create named pipe '{$file->_path}': Posix extension is not available");
 		if (!@posix_mkfifo($this->_path, $mode)) throw new Fs_Exception("Failed to create named pipe '{$file->_path}'", error_get_last());
 	}
}
