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
