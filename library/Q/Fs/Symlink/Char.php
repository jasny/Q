<?php
namespace Q;

require_once 'Q/Fs/Char.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to a char device.
 * 
 * @package Fs
 */
class Fs_Symlink_Char extends Fs_Char implements Fs_Symlink
{
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return Fs_Char
	 */
	public function target()
	{
		return Fs::get(readlink($this->_path));
	}
}
