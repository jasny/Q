<?php
namespace Q;

require_once 'Q/Fs/Item.php';

/**
 * Interface of a char file.
 * 
 * @package Fs
 */
class Fs_Char extends Fs_Item
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (!file_exists($path)) throw new Fs_Exception("Can't load char file '$path'; File doesn't exists."); 
		if (filetype($path) != 'char') throw new Fs_Exception("File '$path' is not a char file, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
	}
}
