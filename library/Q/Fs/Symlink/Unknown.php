<?php
namespace Q;

require_once 'Q/Fs/Unknown.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to file with an unknown type.
 * 
 * @package Fs
 */
class Fs_Symlink_Unknown extends Fs_Unknown implements Fs_Symlink
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
	 * @return Fs_Unknown
	 */
	public function getTarget()
	{
		return Fs::get(readlink($this->path));
	}
}
