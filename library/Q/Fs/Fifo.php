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
