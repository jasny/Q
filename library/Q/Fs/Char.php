<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a char device (eg /dev/null, /dev/zero).
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
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (!file_exists($path)) throw new Fs_Exception("Can't load char device '$path'; File doesn't exists."); 
		if (filetype(realpath($path)) != 'char') throw new Fs_Exception("File '$path' is not a char device, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
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
		return file_get_contents($flags, $offset, $maxlen);
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
		return file_put_contents($data, $flags);
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
 		throw new Fs_Exception("Unable to create '{$this->_path}': File is a char device");
 	}
}
