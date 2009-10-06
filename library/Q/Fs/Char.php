<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a character device (eg /dev/null, /dev/zero).
 * 
 * @package Fs
 */
class Fs_Char extends Fs_Node
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
		if (file_exists($path) && filetype(realpath($path)) != 'char') throw new Fs_Exception("File '$path' is not a character device, but a " . filetype($path) . ".");
	}
	
	/**
	 * Reads entire file into a string.
	 * 
	 * @param int $flags   FILE_% flags as binary set.
	 * @param int $offset  The offset where the reading starts.
	 * @param int $maxlen  Maximum length of data read.
	 * @return string
	 */
	public function getContents($flags=0, $offset=-1, $maxlen=1)
	{
		$ret = @file_get_contents($this->_path, $flags, null, $offset, $maxlen);
		if ($ret === false) throw new Fs_Exception("Failed to read from character device '{$this->_path}'", error_get_last());
		return $ret;
	}

	/**
	 * Write a string to a file.
	 * 
	 * @param mixed $data   The data to write; Can be either a string, an array or a stream resource. 
	 * @param int   $flags  FILE_% flags as binary set.
	 * @return int
	 */
	public function putContents($data, $flags=0)
	{
		if (!$this->exists()) throw new Fs_Exception("Can't write data to character device '{$this->_path}': File doesn't exists."); 
		
		$ret = @file_put_contents($this->_path, $data, $flags);
		if ($ret === false) throw new Fs_Exception("Failed to write to character device '{$this->_path}'", error_get_last());
		return $ret;
	}
	
	/**
	 * Output contents of the file.
	 * 
	 * @return int
	 */
	public function output()
	{
		throw new Fs_Exception("Unable to output the contents of '{$this->_path}': File is a character device");
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
		if (!$this->exists()) throw new Fs_Exception("Can't open character device '{$this->_path}': File doesn't exists.");
		
		$resource = @fopen($this->_path, $mode);
		if (!$resource) throw new Fs_Exception("Failed to open character device '{$this->_path}'", error_get_last());
		return $resource;
	}
}
