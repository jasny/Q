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
