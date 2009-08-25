<?php
namespace Q;

require_once 'Q/Fs/Dir.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a directory.
 * 
 * @package Fs
 */
class Fs_Symlink_Dir extends Fs_Symlink implements Fs_Symlink
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (!is_link($path)) throw new Fs_Exception("File '$path' is not a symlink.");
		parent::__construct($path);
	}
	
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return string
	 */
	public function getTarget()
	{
		return readlink($this->path);
	}
}
