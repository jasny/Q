<?php
namespace Q;

require_once 'Q/Fs/Item.php';

/**
 * Interface of a file with an unknown type.
 * 
 * @package Fs
 */
class Fs_Unknown extends Fs_Item
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (!file_exists($path)) throw new Fs_Exception("Can't load '$path'; File doesn't exists."); 
		if (filetype(realpath($path)) != 'unknown') throw new Fs_Exception("File '$path' is not a file with an unknown type, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
	}
}
