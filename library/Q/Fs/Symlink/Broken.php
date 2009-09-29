<?php
namespace Q;

require_once 'Q/Fs/Node.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink with a target that doesn't exist.
 * 
 * @package Fs
 */
class Fs_Symlink_Broken extends Fs_Node implements Fs_Symlink
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (!is_link($path)) throw new Fs_Exception("File '$path' is not a symlink");
		if (file_exists($path)) throw new Fs_Exception("File '$path' is not a broken link, the target is a " . filetype(realpath($path)));
		parent::__construct($path);
	}
	
 	/**
 	 * Create this file.
 	 * Use Fs::PRESERVE to simply return if file already exists
 	 * 
 	 * @param int $mode
 	 * @param int $flags  Fs::% options
 	 * @throws Fs_Exception
 	 */
	public function create($mode=0666, $flags=0)
 	{
 		if ($this->exists() && $flags & Fs::PRESERVE) return;
 		throw new Fs_Exception("Unable to create file: Unable to dereference link '{$this->_path}'");
 	}
}
