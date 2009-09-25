<?php
namespace Q;

require_once 'Q/Fs/Block.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to a block device file.
 * 
 * @package Fs
 */
class Fs_Symlink_Block extends Fs_Block implements Fs_Symlink
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
	 * @return Fs_Block
	 */
	public function target()
	{
		$path = readlink($this->_path);
		return is_link($path) ? new Fs_Symlink_Block($path) : new Fs_Block($path);
	}
	
	/**
	 * Returns Fs_Item of canonicalized absolute pathname, resolving symlinks.
	 * 
	 * @return Fs_Block
	 */
	public function realpath()
	{
		$path = realpath($this->_path);
		if ($path) return new Fs_Block($path);

		$file = $this->realpathBestEffort();
		if (!$file) throw new Fs_Exception("Unable to resolve realpath of '{$this->_path}'; Too many levels of symbolic links.");
		return $file;
	}
	
	
	/**
	 * Checks whether a file or directory exists.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function exists($flags=0)
	{
		return file_exists($this->_path) || ($flags && Fs::NO_DEREFERENCE && is_link($this->_path));
	}
	
	/**
	 * Tells whether the file is executable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isExecutable($flags=0)
	{
		return $flags && Fs::NO_DEREFERENCE ? (bool)(($this->getAttribute('mode', Fs::NO_DEREFERENCE) >> $this->modeBitShift()) & 1) : is_executable($this->_path);
	}
	
	/**
	 * Tells whether the file is readable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isReadable($flags=0)
	{
		return $flags && Fs::NO_DEREFERENCE ? (bool)(($this->getAttribute('mode', Fs::NO_DEREFERENCE) >> $this->modeBitShift()) & 2) : is_readable($this->_path);
	}
	
	/**
	 * Tells whether the file is writable or creatable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isWritable($flags=0)
	{
		return $flags && Fs::NO_DEREFERENCE ? (bool)(($this->getAttribute('mode', Fs::NO_DEREFERENCE) >> $this->modeBitShift()) & 2) : is_writable($this->_path);
	}
	
	/**
	 * Return whether the current entry is deletable
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isDeletable($flags=0)
	{
		return $this->isWritable || (function_exists('posix_getuid') && posix_getuid() == 0) || $this->up()->isWritable() || ($this->exists() && $this->getAttribute('perms') & 01000 && $this->getAttribute('uid') == posix_getuid()); 
	}
}
