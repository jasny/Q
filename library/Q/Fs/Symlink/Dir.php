<?php
namespace Q;

require_once 'Q/Fs/Dir.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a directory.
 * 
 * @package Fs
 */
class Fs_Symlink_Dir extends Fs_Dir implements Fs_Symlink
{
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return Fs_Dir
	 */
	public function target()
	{
		return Fs::get(readlink($this->_path));
	}
}
