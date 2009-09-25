<?php
namespace Q;

/**
 * Interface to indicate a Fs_Item is a symbolic link.
 * 
 * {@internal Since PHP doesn't support multiple inheritence, we need to duplicate some code.}} 
 * 
 * @package Fs
 */
interface Fs_Symlink
{
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return string
	 */
	public function target();
}