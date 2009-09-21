<?php
namespace Q;

require_once 'Q/Fs/Socket.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to a socket.
 * 
 * @package Fs
 */
class Fs_Symlink_Socket extends Fs_Socket implements Fs_Symlink
{
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return Fs_Socket
	 */
	public function target()
	{
		return Fs::get(readlink($this->_path));
	}
}
