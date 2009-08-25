<?php
namespace Q;

require_once 'Q/Fs/Item.php';

/**
 * Interface of a block device file.
 * 
 * @package Fs
 */
class Fs_Block extends Fs_Item
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (!file_exists($path)) throw new Fs_Exception("Can't load block device '$path'; File doesn't exists."); 
		if (filetype(realpath($path)) != 'block') throw new Fs_Exception("File '$path' is not a block device, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
	}
}
