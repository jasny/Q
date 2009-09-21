<?php
namespace Q;

require_once 'Q/Fs/Fifo.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a FIFO file.
 * 
 * @package Fs
 */
class Fs_Symlink_Fifo extends Fs_Fifo implements Fs_Symlink
{
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return Fs_Fifo
	 */
	public function target()
	{
		return Fs::get(readlink($this->_path));
	}
}
