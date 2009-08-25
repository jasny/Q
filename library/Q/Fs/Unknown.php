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
		if (!file_exists($path)) throw new Fs_Exception("Can't load '$path'; File doesn't exists."); 
		if (filetype($path) != 'unknown') throw new Fs_Exception("File '$path' is not a file with an unknown type, but a " . filetype($path) . ".");
		 
		parent::__construct($path);
	}
}
