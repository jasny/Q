<?php
namespace Q;

require_once 'Q/Fs';

/**
 * Base class for any type of file on the filesystem.
 * 
 * {@internal An object should only have property $path so `Fs_item == Fs_item` will give the expected result.}} 
 * 
 * @package Fs
 */
abstract class Fs_Item implements \ArrayAccess
{
	/**
	 * File path.
	 * @var string
	 */
	protected $path;

	
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = Fs::canonicalize((string)$path);
	}
	
	/**
	 * Return  
	 *
	 * @return unknown_type
	 */
	public function __toString()
	{
		return $this->path;
	}
	
	
	/**
	 * Get the file path.
	 * 
	 * @return string
	 */
	public function path()
	{
		return $this->path;
	}
	
	/**
	 * Returns filename component of path.
	 * 
	 * @return string
	 */
	public function basename()
	{
		return basename($this->path);
	}
	
	/**
	 * Returns directory name component of path.
	 * 
	 * @return string
	 */
	public function dirname()
	{
		return dirname($this->path);
	}
	
	/**
	 * Returns extension component of the path.
	 * 
	 * @return string
	 */
	public function extenstion()
	{
		return pathinfo($this->path, PATHINFO_EXTENSION);
	}
	
	/**
	 * Returns filename component (without extension) of the path.
	 * 
	 * @return string
	 */
	public function extenstion()
	{
		return pathinfo($this->path, PATHINFO_FILENAME);
	}
	
	
 	/**
 	 * Magic get method.
 	 * 
 	 * @param string $name
 	 * @throws Fs_Exception
 	 */
 	public function __get($name)
 	{
 		throw new Fs_Exception("Unable to get {$this->path}/$name; File {$this->path} is not a directory.");
 	}
	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @throws Fs_Exception
 	 */
 	public function file($name)
 	{
 		throw new Fs_Exception("Unable to get {$this->path}/$name; File {$this->path} is not a directory.");
 	}
 	
 	/**
 	 * Get subdirectory.
 	 * 
 	 * @param string $name
 	 * @throws Fs_Exception
 	 */
 	public function dir($name)
 	{
 		throw new Fs_Exception("Unable to get {$this->path}/$name; File {$this->path} is not a directory.");
 	}
 	
	/**
	 * Returns Fs_Item of canonicalized absolute pathname.
	 * 
	 * @return Fs_Item
	 */
	public function realpath()
	{
		return Fs::get(realpath($this->path));
	}
	
 	/**
	 * Get parent directory of this file.
	 * 
	 * @return Fs_Dir
	 */
	public function up()
	{
		return Fs::dir($this->dirname());
	}

 	
	/**
	 * Gives information about a file.
	 * @see http://www.php.net/stat
	 * 
	 * @return array
	 */
	public function stat($flags=0)
	{
		$stat = $flags & Fs::DONTFOLLOW ? @lstat($this->path) : @stat($this->path);
		
		if ($stat === false) {
			$err = error_get_last();
			throw new Fs_Exception("Failed to stat {$this->path}; " . $err['message']);
		}
		
		return $stat;
	}

	/**
	 * Get a list of extended attributes.
	 * @see http://www.php.net/stat
	 * 
	 * @param int $flags  FS::% and/or XATTR_% options as binary set
	 * @return array
	 */
	public function getXattributes($flags=0)
	{
		if (!extension_loaded('xattr') || !xattr_supported($this->path, $flags)) throw new Fs_Exception("Unable to get attributes of {$this->path}; Extended attributes are not supported.");
		
		$attr = @xattr_list($this->file, $flags);
		
		if ($attr === false) {
			$err = error_get_last();
			throw new Fs_Exception("Failed to get extended attributes of {$this->path}; " . $err['message']);
		}
		
		return $attr;		
	}
	
	/**
     * Clears file status cache.
     * @see http://www.php.net/clearstatcache
     * 
     * @param boolean $clear_realpath_cache  Whenever to clear realpath cache or not.
     */
    public function clearStatCache($clear_realpath_cache=false)
    {
    	clearstatcache($clear_realpath_cache, $this->path);
    }
    
    /**
     * Sets access and modification time of file
     * 
     * @param int|\DateTime $time
     * @param int|\DateTime $atime
     * @return boolean
     */
    public function touch($time=null, $atime=null)
    {
    	if ($time instanceof \DateTime) $time = $time->getTimestamp();
    	if ($atime instanceof \DateTime) $atime = $atime->getTimestamp();
    	
    	touch($this->path, $time, $atime);
    }
    
    /**
     * Get file or extended attribute.
     * @see http://www.php.net/stat
     * 
     * @param string $att    Attribute name
     * @param int    $flags  FS::% and/or XATTR_% options as binary set
     * @return mixed
     */
    public function getAttribute($att, $flags=0)
    {
    	$stat = $this->stat($flags);
    	if (isset($stat[$att])) return $stat[$att];
    	
    	if (!extension_loaded('xattr') || !xattr_supported($this->path, $flags)) {
	    	trigger_error("Unable to get attribute '$att' of {$this->path}; Extended attributes are not supported.", E_USER_NOTICE);
	    	return null;
    	}
    	
    	$value = xattr_get($this->path, $att);
    	return $value !== false ? $value : null;
    }
	
    /**
     * Set file or extended attribute.
     * 
     * @param string $att    Attribute name
     * @param mixed  $value  Attribute value
     * @param int    $flags  FS::% options and/or XATTR_% options as binary set
     * @param boolean
     */
    public function setAttribute($att, $value, $flags=0)
    {
    	switch ($att) {
    		case 'size':
    		case 'inode':
    		case 'type':  throw new Exception("Unable to set attribute '$att'; Attribute is read-only.");
    		
    		case 'atime': return $this->touch(filemtime($this->path), $value);
    		case 'ctime': throw new Exception("Unable to set attribute '$att'; Attribute is read-only.");
    		case 'mtime': return $this->touch($value);

    		case 'perms':      return $this->chmod($value, $flags);
    		case 'owner':
    		case 'owner_name': return $this->chown($value, $flags);
    		case 'group':      
    		case 'group_name': return $this->chgrp($value, $flags);
    	}
    	
    	if (!extension_loaded('xattr') || !xattr_supported($this->path, $flags)) throw new Exception("Unable to set attribute '$att'; Not a file attribute and extended attributes are not supported.");
    	return $value === null ? xattr_remove($this->path, $att, $flags) : xattr_set($this->path, $att, $value, $flags);
	}
	
	/**
	 * ArrayAccess; Check if attribute exists.
	 * 
	 * @param string $att Attribute name
	 * @return boolean
	 */
	public function offsetExists($att)
	{
		return in_array($att, array('size', 'inode', 'type', 'atime', 'ctime', 'mtime', 'perms', 'owner', 'owner_name', 'group', 'group_name'))
		  || (extension_loaded('xattr') && xattr_supported($this->path) && xattr_get($this->path, $att) !== false); 
	}
	
	/**
	 * ArrayAccess; Get attribute.
	 * 
	 * @param string $att Attribute name
	 * @return string
	 */
	public function offsetGet($att)
	{
		return $this->getAttribute($att); 
	}
	
	/**
	 * ArrayAccess; Set attribute.
	 * 
	 * @param string $att    Attribute name
	 * @param string $value  Attribute value
	 */
	public function offsetSet($att, $value)
	{
		$this->setAttribute($att, $value); 
	}
	
	/**
	 * ArrayAccess; Unset attribute.
	 * 
	 * @param string $att    Attribute name
	 */
	public function offsetUnset($att)
	{
		$this->setAttribute($att, null); 
	}
	

	/**
	 * Checks whether a file or directory exists.
	 * 
	 * @return boolean
	 */
	public function exists()
	{
		return file_exists($this->path);
	}
	
	/**
	 * Tells whether the file is executable.
	 *  
	 * @return boolean
	 */
	public function isExecutable()
	{
		return is_executable($this->path);
	}
	
	/**
	 * Tells whether the file is readable.
	 * 
	 * @return boolean
	 */
	public function isReadable()
	{
		return is_readable($this->path);
	}
	
	/**
	 * Tells whether the file is writable.
	 * 
	 * @return boolean
	 */
	public function isWritable()
	{
		return is_writable($this->path);
	}
	
	/**
	 * Return whether the file can be created if it does not exist.
	 * 
	 * @return boolean
	 */
	public function isCreatable()
	{
		if ($this->exists()) return false;
		
		$dir = $this->up();
		return $dir->exists() ? $dir->isWritable() : $dir->isCreatable();
	}
	
	/**
	 * Return whether the current entry is deletable
	 * 
	 * @return boolean
	 */
	public function isDeletable()
	{
		return $this->isWritable() || $this->up()->isWritable() || ($this['perms'] & 01000 && function_exists('posix_getuid') && $this['owner'] == posix_getuid()); 
	}
	
	/**
	 * Tells whether the file is hidden.
	 * 
	 * @return boolean
	 * 
	 * @todo Fs_Item::isHidden doesn't work for windows hidden flag.
	 */
	public function isHidden()
	{
		return $this->path[0] == '.';
	}
	
	
    /**
     * Changes file mode.
     * 
     * @param string     $path   Path to the file
     * @param int|string $mode   Octal mode (int) or symbolic mode (string) 
     * @param int        $flags  Fs::% options as binary set
     * @throws Fs_ExecException if chmod fails.
     * 
     * @todo Fs_Item::chmod() won't work for windows.
     */
	public static function chmod($mode, $flags=0)
	{
		if (is_int($mode)) $mode = sprintf('%0-4o', $mode);
		Fs::which('chmod')->exec($mode, $this->path, $flags & self::RECURSIVE ? '--recursive' : null);
	}

    /**
     * Changes file owner.
     * 
     * @param int|string $owner  User id, username or user:group
     * @param int        $flags  Fs::% options as binary set
     * @throws Fs_ExecException if chown fails.
     * 
     * @todo Fs_Item::chown() won't work for windows.
     */
	public static function chown($owner, $flags=0)
	{
		Fs::which('chown')->exec($owner, $this->path, $flags & self::RECURSIVE ? '--recursive' : null, $flags & self::DONTFOLLOW ? '--no-dereference' : null, $flags & self::ALWAYSFOLLOW ? '-L' : null);
	}
	
    /**
     * Changes file group.
     * 
     * @param int|string $group  Group id or groupname 
     * @param int        $flags  Fs::% options as binary set
     * @throws Fs_ExecException if chgrp fails.
     * 
     * @todo Fs_Item::chgrp() won't work for windows.
     */
	public static function chgrp($group, $flags=0)
	{
		Fs::which('chgrp')->exec($group, $this->path, $flags & Fs::RECURSIVE ? '--recursive' : null, $flags & Fs::DONTFOLLOW ? '--no-dereference' : null, $flags & Fs::ALWAYSFOLLOW ? '-L' : null);
	}	
	
	
	/**
	 * Copy this file.
	 * 
	 * @param string|Fs_Item $dest
	 * @param int            $flags  Fs::% options as binary set
	 * @return Fs_Item
	 * @throws Fs_ExecException if chgrp fails.
	 */
	public function copy($dest, $flags=0)
	{
		Fs::which('cp')->exec($this->path, $dest, $flags & Fs::RECURSIVE ? '--recursive' : null, $flags & Fs::DONTFOLLOW ? '--no-dereference' : null, $flags & Fs::ALWAYSFOLLOW ? '--dereference' : null, $flags & Fs::PRESERVE ? '--preserve' : null, $flags & Fs::OVERWRITE ? '--force' : null, $flags & Fs::UPDATE ? '--update' : null);
		return Fs::get($dest);
	}
	
	/**
	 * Rename/move this file.
	 * 
	 * @param string|Fs_Item $dest
	 * @param int            $flags  Fs::% options as binary set
	 * @return Fs_Item
	 */
	public function rename($dest, $flags=0)
	{
		Fs::which('mv')->exec($this->path, $dest, $flags & Fs::OVERWRITE ? '--force' : null, $flags & Fs::UPDATE ? '--update' : null);
		return Fs::get($dest);
	}

	/**
	 * Alias of Fs_Item::rename().
	 * 
	 * @param string|Fs_Item $dest
	 * @param int            $flags  Fs::% options as binary set
	 * @return Fs_Item
	 */
	public final function move($dest, $flags=0)
	{
		return $this->rename($dest, $flags);
	}
	
	/**
	 * Delete the file.
	 * 
	 * @param int $flags  Fs::% options as binary set
	 */
	public function delete($flags=0)
	{
		Fs::which('rm')->exec($this->path, '--interactive=none', $flags & Fs::RECURSIVE ? '--recursive' : null);
	}

	/**
	 * Alias of Fs_item::delete().
	 * 
	 * @param int $flags  Fs::% options as binary set
	 * @return boolean
	 */
	public final function unlink($flags=0)
	{
		return $this->delete($flags);
	}
	
	
	/**
	 * Magic method for when object is used as function.
	 * 
	 * @throws Fs_Exception
	 */
	public function __invoke()
	{
		throw new Fs_Exception("Unable to execute {$this->path}; This is not a regular file, but a " . filetype((string)$this->realpath()) . ".");
	}	
}