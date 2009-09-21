<?php
namespace Q;

require_once 'Q/Fs/File.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to a regular file.
 * 
 * @package Fs
 */
class Fs_Symlink_File extends Fs_File implements Fs_Symlink
{
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return Fs_File
	 */
	public function target()
	{
		return Fs::get(readlink($this->_path));
	}
}
