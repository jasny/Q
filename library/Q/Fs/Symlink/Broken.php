<?php
namespace Q;

require_once 'Q/Fs/Unknown.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink with a target that doesn't exist.
 * 
 * @package Fs
 */
class Fs_Symlink_Broken extends Fs_Item implements Fs_Symlink
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (!is_link($path)) throw new Fs_Exception("File '$path' is not a symlink.");
		if (file_exists($path)) throw new Fs_Exception("File '$path' is not a broken link, the target is a " . filetype(realpath($path)) . ".");
		parent::__construct($path);
	}
	
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return string
	 */
	public function target()
	{
		return readlink($this->_path);
	}
}
