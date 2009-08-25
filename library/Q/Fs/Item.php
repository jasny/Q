<?php
namespace Q;

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
		$this->path = rtrim((string)$path, '/\\');
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
	 * Returns canonicalized absolute pathname
	 * 
	 * @param int $flags  Optional Fs::DONTFOLLOW
	 * @return string
	 */
	public function realpath($flags=0)
	{
		return $flags & Fs::DONTFOLLOW ? Fs::canonicalize($this->path) : realpath($this->path);
	}
	
	
	/**
	 * Get parent directory of this file.
	 * 
	 * @return Fs_Dir
	 */
	public function up()
	{
		return new Fs_Dir($this->dirname());
	}
	
	
	/**
	 * Gives information about a file.
	 * @see http://www.php.net/stat
	 * 
	 * @return array
	 */
	public function stat($flags=0)
	{
		return stat($this->path);
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
     * 
     * @param string $att    Attribute name
     * @param int    $flags  FS::% options
     * @return mixed
     */
    public function getAttribute($att, $flags=0)
    {
    	switch ($att) {
    		case 'size':  return filesize($this->path);
    		case 'perms': return fileperms($this->path);
    		case 'inode': return fileinode($this->path);
    		case 'type':  return filetype($this->path);
    		
    		case 'atime': return fileatime($this->path);
    		case 'ctime': return filectime($this->path);
    		case 'mtime': return filemtime($this->path);
    		
    		case 'owner':      return fileowner($this->path);
    		case 'owner_name': $info = posix_getpwuid(fileowner($this->path)); return $info['name'];
    		case 'group':      return filegroup($this->path);
    		case 'group_name': $info = posix_getgrgid(filegroup($this->path)); return $info['name'];
    	}
    	
    	if (!extension_loaded('xattr') || !xattr_supported($this->path, $flags)) {
	    	trigger_error("Unable to get attribute '$att'; Extended attributes are not supported.", E_USER_NOTICE);
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

    		case 'perms':      return Fs::chmod($this, $value, $flags);
    		case 'owner':
    		case 'owner_name': return Fs::chown($this, $value, $flags);
    		case 'group':      
    		case 'group_name': return Fs::chgrp($this, $value, $flags);
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
	 * Copy this file.
	 * 
	 * @param string|Fs_Item $dest
	 * @param int            $flags  Fs::% options as binary set
	 * @return Fs_Item
	 */
	public function copy($dest, $flags=0)
	{
		Fs::copy($this, $dest, $flags);
		return Fs::open($dest);
	}
	
	/**
	 * Rename/move this file.
	 * 
	 * @param string|Fs_Item $newname
	 * @param int            $flags    Fs::% options as binary set
	 * @return Fs_Item
	 */
	public function rename($newname, $flags=0)
	{
		Fs::rename($this, $newname, $flags);

		$this->path = $newname;
		return $this;
	}

	/**
	 * Alias of Fs_Item::rename().
	 * 
	 * @param string|Fs_Item $newname
	 * @param int            $flags      Fs::% options as binary set
	 * @return Fs_Item
	 */
	public final function move($newname, $flags=0)
	{
		return $this->rename($newname, $flags);
	}
	
	/**
	 * Delete the file.
	 * 
	 * @param int $flags  Fs::% options as binary set
	 * @return boolean
	 */
	public function delete($flags=0)
	{
		return Fs::delete($this->path, $flags);
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
}