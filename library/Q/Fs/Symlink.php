<?php
namespace Q;

/**
 * Interface to indicate a Fs_Item is a symbolic link.
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
	public function getTarget();
}