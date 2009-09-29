<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a block device file.
 * 
 * @package Fs
 */
class Fs_Block extends Fs_Node
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (!file_exists($path)) throw new Fs_Exception("Can't load block device '$path'; File doesn't exists."); 
		if (filetype(realpath($path)) != 'block') throw new Fs_Exception("File '$path' is not a block device, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
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
 		throw new Fs_Exception("Unable to create '{$this->_path}': File is a block device");
 	}
}
