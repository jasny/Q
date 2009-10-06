<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a file with an unknown type.
 * 
 * @package Fs
 */
class Fs_Unknown extends Fs_Node
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		parent::__construct($path);
		
        if (file_exists($path) && filetype(realpath($path)) != 'unknown') throw new Fs_Exception("File '$path' is not a file with an unknown type, but a " . Fs::typeOfNode($path, Fs::DESCRIPTION));
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
	}
}
