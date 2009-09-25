<?php
namespace Q;

require_once 'Q/SecurityException.php';

/**
 * Base class for any type of file on the filesystem.
 * 
 * {@internal An object should only have property $_path so `Fs_item == Fs_item` will give the expected result.}} 
 * 
 * @package Fs
 */
abstract class Fs_Item implements \ArrayAccess
{
	/**
	 * File path.
	 * @var string
	 */
	protected $_path;

	
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->_path = Fs::canonicalize($path);
	}
	
	/**
	 * Return  
	 *
	 * @return unknown_type
	 */
	public function __toString()
	{
		return $this->_path;
	}
	
	
	/**
	 * Get the file path.
	 * 
	 * @return string
	 */
	public function path()
	{
		return $this->_path;
	}
	
	/**
	 * Returns filename component of path.
	 * 
	 * @return string
	 */
	public function basename()
	{
		return basename($this->_path);
	}
	
	/**
	 * Alias of Fs_Item::up().
	 * 
	 * @return Fs_Dir
	 */
	public function dirname()
	{
		return $this->up();
	}
	
	/**
	 * Returns extension component of the path.
	 * 
	 * @return string
	 */
	public function extension()
	{
		return pathinfo($this->_path, PATHINFO_EXTENSION);
	}
	
	/**
	 * Returns filename component (without extension) of the path.
	 * 
	 * @return string
	 */
	public function filename()
	{
		return pathinfo($this->_path, PATHINFO_FILENAME);
	}
	
	
 	/**
 	 * Magic get method.
 	 * 
 	 * @param string $name
 	 * @throws Fs_Exception
 	 */
 	public function __get($name)
 	{
 		throw new Fs_Exception("Unable to get {$this->_path}/$name: File {$this->_path} is not a directory.");
 	}
	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @throws Fs_Exception
 	 */
 	public function file($name)
 	{
 		throw new Fs_Exception("Unable to get {$this->_path}/$name: File {$this->_path} is not a directory.");
 	}
 	
 	/**
 	 * Get subdirectory.
 	 * 
 	 * @param string $name
 	 * @throws Fs_Exception
 	 */
 	public function dir($name)
 	{
 		throw new Fs_Exception("Unable to get {$this->_path}/$name: File {$this->_path} is not a directory.");
 	}
 	
	/**
	 * Return final path for broken link.
	 * 
	 * @param int $count  Counter to break deadloop (max 16)
	 * @return Fs_Block
	 */
	protected function realpathBestEffort($count=0)
	{
		if ($count >= 16) return false;
		
		$target = $this->target(); // A bit dodgy, this should only be called by Fs_Symlink classes. 
		return $target instanceof Fs_Symlink ? $target->realpathBestEffort($count) : $target; 
	}
	
 	/**
	 * Returns Fs_Item of canonicalized absolute pathname, resolving symlinks.
	 * Unlike the realpath() function, this returns a best-effort for non-existent files. 
	 * 
	 * @return Fs_Item
	 */
	public function realpath()
	{
		return $this;
	}
	
 	/**
	 * Get parent directory of this file.
	 * 
	 * @return Fs_Dir
	 */
	public function up()
	{
		return Fs::dir(dirname($this->_path));
	}

 	/**
 	 * Check if this file is in directory $dir.
 	 * 
 	 * @param string $dir
 	 * @param int    $flags  Options, default: Fs::NO_DEREFERENCE | Fs::RECURSIVE 
 	 * @return boolean
 	 */
 	public function isIn($dir, $flags=0x0201)
 	{
 		$path = $flags & Fs::NO_DEREFERENCE ? $this->_path : (string)$this->realpath();
 		$len = strlen($dir);
 		return strncmp($path, Fs::canonicalize($dir) . '/', $len) == 0 && ($flags & Fs::RECURSIVE || strpos($path, '/', $len+1) !== false); 
 	}
	
 	/**
 	 * Use file as named path
 	 * 
 	 * @param string $name
 	 */
 	public function setAs($name)
 	{
 		Fs::setPath($name, $this);
 	}
 	
 	
	/**
	 * Gives information about a file.
	 * @see http://www.php.net/stat
	 * 
	 * Also includes
	 *  'type'  => file type
	 *  'perms' => file permission in human readable format
	 *  'umask' => reverse mode
	 *  'owner' => owner username
	 *  'group' => group name
	 * 
	 * @return array
	 */
	public function stat($flags=0)
	{
		$stat = $flags & Fs::NO_DEREFERENCE ? @lstat($this->_path) : @stat($this->_path);
		
		if ($stat === false) {
			$err = error_get_last();
			throw new Fs_Exception("Failed to stat {$this->_path}", error_get_last());
		}

		$stat['type'] = Fs::mode2type($stat['mode']);
		$stat['perms'] = Fs::mode2perms($stat['mode']);
		$stat['umask'] = Fs::mode2umask($stat['mode']);
		
		if (extension_loaded('posix')) {
	    	$stat['owner'] = ($info = posix_getpwuid(fileowner($this->_path))) ? $info['name'] : fileowner($this->_path);
	    	$stat['group'] = ($info = posix_getgrgid(filegroup($this->_path))) ? $info['name'] : filegroup($this->_path);
		} else {
			$stat['owner'] = fileowner($this->_path);
			$stat['group'] = filegroup($this->_path);
		}
		
		return $stat;
	}

	/**
	 * Get a list of extended attributes.
	 * @see http://www.php.net/xattr_list
	 * 
	 * @param int $flags  FS::% and/or XATTR_% options as binary set
	 * @return array
	 */
	public function getXattributes($flags=0)
	{
		if (!extension_loaded('xattr') || !xattr_supported($this->_path, $flags)) throw new Fs_Exception("Unable to get attributes of {$this->_path}; Extended attributes are not supported.");
		
		$file = $flags & Fs::NO_DEREFERENCE  ? $this->_path : (string)$this->realpath();
		$attr = @xattr_list($file, $flags);
		
		if ($attr === false) throw new Fs_Exception("Failed to get extended attributes of {$this->_path}", error_get_last());
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
    	clearstatcache($clear_realpath_cache, $this->_path);
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
    	$stat = $flags & Fs::NO_DEREFERENCE ? @lstat($this->_path) : @stat($this->_path);
		if ($stat === false) {
			$err = error_get_last();
			throw new Fs_Exception("Failed to stat {$this->_path}", error_get_last());
		}
		
    	if (isset($stat[$att])) return $stat[$att];
		
    	if ($att == 'type') return Fs::mode2type($stat['mode']);
    	if ($att == 'perms') return Fs::mode2perms($stat['mode']);
    	if ($att == 'umask') return Fs::mode2umask($stat['mode']);
    	if ($att == 'owner') return (extension_loaded('posix') && ($info = posix_getpwuid(fileowner($this->_path)))) ? $info['name'] : fileowner($this->_path);
    	if ($att == 'group') return (extension_loaded('posix') && ($info = posix_getgrgid(filegroup($this->_path)))) ? $info['name'] : filegroup($this->_path);
    	
    	if (!extension_loaded('xattr') || !xattr_supported($this->_path, $flags)) {
	    	trigger_error("Unable to get attribute '$att' of {$this->_path}: Extended attributes are not supported.", E_USER_NOTICE);
	    	return null;
    	}
    	
    	$value = xattr_get($this->_path, $att);
    	return $value !== false ? $value : null;
    }
	
    /**
     * Set file or extended attribute.
     * 
     * @param string $att    Attribute name
     * @param mixed  $value  Attribute value
     * @param int    $flags  FS::% options and/or XATTR_% options as binary set
     */
    public function setAttribute($att, $value, $flags=0)
    {
    	if ($value === null && in_array($att, array('mode', 'perms', 'uid', 'owner', 'gid', 'group'))) throw new Exception("Unable to set attribute '$att' to null.");
    	
    	switch ($att) {
    		case 'mode':
    		case 'perms': $this->chmod($value, $flags); break;
    		case 'uid':
    		case 'owner': $this->chown($value, $flags); break;
    		case 'gid':      
    		case 'group': $this->chgrp($value, $flags); break;

    		case 'mtime': 
    		case 'atime': throw new Exception("Unable to set attribute '$att'; Use touch() method instead.");
    		
    		case 'ctime':
    		case 'size':
    		case 'type':
    		case 'dev':
    		case 'ino':
    		case 'nlink':
    		case 'rdev':
    		case 'blksize':
    		case 'blocks':	throw new Exception("Unable to set attribute '$att'; Attribute is read-only.");
    		
    		default:
		    	if (!extension_loaded('xattr')) throw new Exception("Unable to set attribute '$att' for '{$this->_path}': Not a file attribute and extended attributes are not supported.");
		    	if (!xattr_supported($this->_path, $flags)) throw new Fs_Exception("Unable to set attribute '$att' for '{$this->_path}': Extended attributes are not supported for that filesystem.");
		    	
		    	$ret = $value === null ? @xattr_remove($this->_path, $att, $flags) : @xattr_set($this->_path, $att, $value, $flags);
		    	if (!$ret) {
		    		$error = error_get_last();
		    		throw new Fs_Exception("Failed to set extended attribute '$att' for '{$this->_path}': {$error['message']}");
		    	}
		    	
		    	$this->clearStatCache();
    	}
	}
	
	/**
	 * ArrayAccess; Check if attribute exists.
	 * 
	 * @param string $att Attribute name
	 * @return boolean
	 */
	public function offsetExists($att)
	{
		return in_array($att, array('size', 'type', 'atime', 'ctime', 'mtime', 'mode', 'uid', 'owner', 'gid', 'group', 'dev', 'ino', 'nlink', 'rdev', 'blksize', 'blocks'))
		  || (extension_loaded('xattr') && xattr_supported($this->_path) && xattr_get($this->_path, $att) !== false); 
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
	 * Get the number of bits to shift to get the privileges of the user of the current process.
	 * 
	 * {@example
	 *  $mode = ($file['mode'] >> $file->modeBitShift()) & 7;          // Privileges for the current user as bitset (rwx)
	 *  $privs = substr($file['privs'], 7 - $file->modeBitShift(), 3); // Privileges for the current user as string
	 * }}
	 * 
	 * @return int
	 * @throws Exception if posix extension is not available.
	 */
	public function modeBitShift($flags=0)
	{
		if (!extension_loaded('posix')) throw new Exception("Unable to determine the which part of the mode of '{$this->_path}' applies to the current user: Posix extension not avaible.");
		return $this->getAttribute('uid', $flags) == posix_getuid() ? 6 : (in_array($this->getAttribute('gid', $flags), posix_getgroups()) ? 3 : 0);
	}
	
	/**
	 * Checks whether the file exists.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function exists($flags=0)
	{
		return file_exists($this->_path);
	}
	
	/**
	 * Tells whether the file is executable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isExecutable($flags=0)
	{
		return is_executable($this->_path);
	}
	
	/**
	 * Tells whether the file is readable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isReadable($flags=0)
	{
		return is_readable($this->_path);
	}
	
	/**
	 * Tells whether the file is writable or creatable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isWritable($flags=0)
	{
		return is_writable($this->_path) || !$this->exists() && $this->up()->isWritable($flags);
	}
	
	/**
	 * Return whether this file is deletable.
	 * Will never follow symlinks, regardless of $flags.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isDeletable($flags=0)
	{
		$dir = $this->up();
		if (!$dir->isWritable()) return false;
		if (!function_exists('posix_getuid')) return true;
		
		$uid = posix_getuid();
		return $uid == 0 || !($dir->getAttribute('mode') & 01000) || $this->getAttribute('uid') == $uid; 
	}
	
	/**
	 * Tells whether the file is hidden.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isHidden($flags=0)
	{
		return $this->_path[0] == '.';
	}
	
	
    /**
     * Sets access and modification time of file.
     * @see http://www.php.net/touch
     * 
     * @param int|string|\DateTime $time   Defaults to time()
     * @param int|string|\DateTime $atime  Defaults to $time
     * @param int                  $flags  Fs::% options as binary set
     * @throws Fs_Exception or ExecException if chown fails.
     * 
     * @todo Implement support for several options of $flags for Fs_Item::touch()
     */
    public function touch($time=null, $atime=null, $flags=0)
    {
		if (!$this->exists()) throw new Fs_Exception("Unable to touch '{$this->_path}': " . ($this instanceof Fs_Symlink && is_link($this->_path) ? "Unable to dereference symlink" : "File does not exist"));
    	
    	if (!isset($time)) $time = time();
    	 elseif (is_string($time)) $time = strtotime($time);
    	 elseif ($time instanceof \DateTime) $time = $time->getTimestamp();
    	 
    	if (!isset($atime)) $atime = $time;
    	 elseif (is_string($atime)) $atime = strtotime($atime);
    	 elseif ($atime instanceof \DateTime) $atime = $atime->getTimestamp();
    	
    	if (!@touch($this->_path, $time, $atime)) throw new Fs_Exception("touch '{$this->_path}' failed", error_get_last());
    	$this->clearStatCache();
    }
    
    /**
     * Changes file mode.
     * Use of $flags might cause an exception based on the operation system.
     * 
     * @param string     $path   Path to the file
     * @param int|string $mode   Octal mode (int) or symbolic mode (string) 
     * @param int        $flags  Fs::% options as binary set
     * @throws Fs_Exception or ExecException if chown fails.
     */
	public function chmod($mode, $flags=0)
	{
		if (!$this->exists($flags)) throw new Fs_Exception("Unable to change mode of '{$this->_path}': " . ($this instanceof Fs_Symlink && is_link($this->_path) ? "Unable to dereference symlink" : "File does not exist"));
		
		if ($flags == 0 && (is_int($mode) || ctype_digit($mode))) {
			if (!@chmod($this->_path, is_int($mode) ? $mode : octdec($mode))) throw new Fs_Exception("Failed to change mode of '{$this->_path}'", error_get_last());
		} else {
			if (is_int($mode)) $mode = sprintf('%04o', $mode);
			Fs::bin('chmod')->exec($mode, $this->_path, $flags & Fs::RECURSIVE ? '--recursive' : null);
		}
		
		$this->clearStatCache();
	}

    /**
     * Changes file owner.
     * Use of $flags might cause an exception based on the operation system.
     * 
     * @param int|string $owner  User id, username or array(user, group)
     * @param int        $flags  Fs::% options as binary set
     * @throws Fs_Exception or ExecException if chown fails.
     */
	public function chown($owner, $flags=0)
	{
		if (!$this->exists($flags)) throw new Fs_Exception("Unable to change owner of '{$this->_path}' to user '$owner': " . ($this instanceof Fs_Symlink && is_link($this->_path) ? "Unable to dereference symlink" : "File does not exist"));
		if (strpos($owner, ':')) throw new SecurityException("Won't change owner of '{$this->_path}' to user '$owner': To change both owner and group, user array(owner, group) instead");
		
		if ($flags == 0) {
			if (is_array($owner)) { $group = $owner[1]; $owner = $owner[0]; }
			 
			if (!@chmod($this->_path, $owner)) throw new Fs_Exception("Failed to change owner of '{$this->_path}' to user '$owner'", error_get_last());
			if (isset($group)) $this->chgrp($group, $flags);
			
		} else {
			Fs::bin('chown')->exec(is_array($owner) ? join(':', $owner) : $owner, $this->_path, $flags & Fs::RECURSIVE ? '-r' : null, $flags & self::NO_DEREFERENCE ? '--no-dereference' : null, $flags & self::ALWAYS_FOLLOW ? '-L' : null);
		}
		
		$this->clearStatCache();
	}
	
    /**
     * Changes file group.
     * Use of $flags might cause an exception based on the operation system.
     * 
     * @param int|string $group  Group id or groupname 
     * @param int        $flags  Fs::% options as binary set
     * @throws Fs_Exception or ExecException if chown fails.
     */
	public function chgrp($group, $flags=0)
	{
		if (!$this->exists($flags)) throw new Fs_Exception("Unable to change group of '{$this->_path}' to '$group': " . ($this instanceof Fs_Symlink && is_link($this->_path) ? "Unable to dereference symlink" : "File does not exist"));
		
		if ($flags == 0) {
			if (!@chgrp($this->_path, $group)) throw new Fs_Exception("Failed to change group of '{$this->_path}' to '$group'", error_get_last());
		} else {
			Fs::bin('chgrp')->exec($group, $this->_path, $flags & Fs::RECURSIVE ? '-r' : null, $flags & Fs::NO_DEREFERENCE ? '--no-dereference' : null, $flags & Fs::ALWAYS_FOLLOW ? '-L' : null);
		}
		
		$this->clearStatCache();
	}
	
	
	/**
	 * Copy or rename/move this file.
	 * 
	 * @param callback $fn     Function name; copy or rename
	 * @param Fs_Dir   $dir
	 * @param string   $name
	 * @param int      $flags  Fs::% options as binary set
	 * @return Fs_Item
	 */
	protected function doCopyRename($fn, $dir, $name, $flags)
	{
		if (empty($name) || $name == '.' || $name == '..' || strpos('/', $name) !== false) throw new SecurityException("Unable to $fn '{$this->_path}' to '$dir/$name': Invalid filename '$name'");
		
		if (!($dir instanceof Fs_Dir)) $dir = Fs::dir($dir);
		
		if (!$dir->exists()) {
			if (~$flags & Fs::RECURSIVE) throw new Fs_Exception("Unable to $fn '{$this->_path}' to '$dir/$name': Directory does not exist.");
			$dir->create();
		}
		
		if ($dir->has($name)) {
			$dest = $dir->$name;
			if ($flags & Fs::OVERWRITE);
			  elseif ($flags & Fs::UPDATE && $dest['ctime'] >= $this['ctime']) return $this->_path;
			  else throw new Fs_Exception("Unable to $fn '{$this->_path}' to '$dir/$name': Target already exists.");

			$dest->clearStatCache();
			$dest->delete();
		}
		
		if (!@$fn($this->_path, "$dir/$name")) throw new Fs_Exception("Failed to $fn '{$this->_path}' to '$dir/$name'", error_get_last());
		return "$dir/$name";
	}
	
	/**
	 * Create a copy of this file. 
	 * 
	 * @param string $newname
	 * @param int    $flags
	 * @return Fs_Item
	 */
	public function copy($newname, $flags=0)
	{
		return new static($this->doCopyRename('copy', dirname($newname), basename($newname), $flags));
	}

	/**
	 * Copy this to another directory.
	 * 
	 * @param string|Fs_Dir $dir
	 * @param int           $flags  Fs::% options as binary set
	 * @return Fs_Item
	 */
	public function copyTo($dir, $flags=0)
	{
		return new static($this->doCopyRename('copy', $dir, $this->basename(), $flags));
	}
	
	/**
	 * Rename this file.
	 * 
	 * @param string $newname
	 * @param int    $flags  Fs::% options as binary set
	 * @return Fs_Item
	 */
	public function rename($newname, $flags=0)
	{
		$this->_path = $this->doCopyRename('rename', dirname($newname), basename($newname), $flags);
		return $this;
	}

	/**
	 * Move this file to another directory.
	 * 
	 * @param string|Fs_Dir $dir
	 * @param int           $flags  Fs::% options as binary set
	 * @return Fs_Item
	 */
	public final function moveTo($dir, $flags=0)
	{
		$this->_path = $this->doCopyRename('rename', $dir, $this->basename(), $flags);
		return $this;
	}
	
	/**
	 * Delete the file.
	 * 
	 * @param int $flags  Fs::% options as binary set
	 */
	public function delete($flags=0)
	{ 
		if (!@unlink($this->_path)) throw new Fs_Exception("Failed to delete '{$this->_path}'", error_get_last());
		$this->clearStatCache();
	}
	
	
	/**
	 * Magic method for when object is used as function.
	 * 
	 * @throws Fs_Exception
	 */
	public function __invoke()
	{
		throw new Fs_Exception("Unable to execute {$this->_path}: This is not a regular file, but a " . $this->realpath()->getAttribute('type') . ".");
	}
	
	/**
	 * This static method is called for classes exported by var_export(). 
	 *  
	 * @param array $props
	 * @return Fs_Item
	 */
	public static function __set_state($props)
	{
		return new static($props['_path']);
	}
}
