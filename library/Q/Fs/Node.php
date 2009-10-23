<?php
namespace Q;

require_once 'Q/Fs.php';
require_once 'Q/ExecException.php';
require_once 'Q/SecurityException.php';

/**
 * Base class for any type of file on the filesystem.
 * 
 * {@internal An object should only have property $_path so `Fs_Node == Fs_Node` will give the expected result.}} 
 * 
 * @package Fs
 */
abstract class Fs_Node implements \ArrayAccess, \Iterator, \Countable
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
		if ($path == '') throw new Exception("Can't create a " . get_class($this) . " object without a path");
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
	 * @param  string $suffix If the filename ends in suffix  this will also be cut off
	 * @return string
	 */
	public function basename($suffix=null)
	{
	    return basename($this->_path, $suffix);
	}
	
	/**
	 * Alias of Fs_Node::up().
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
	 * Interator; Returns the current file object.
	 * 
	 * @return Fs_Node
	 */
	public function current()
	{
		throw new Fs_Exception("Unable to traverse through '{$this->_path}': File is not a directory");
	}
	
	/**
	 * Interator; Returns the current filename.
	 * 
	 * @return string
	 */
	public function key()
 	{
		throw new Fs_Exception("Unable to traverse through '{$this->_path}': File is not a directory");
	}
 	
	/**
	 * Interator; Move forward to next item.
	 */
 	public function next()
 	{
 		throw new Fs_Exception("Unable to traverse through '{$this->_path}': File is not a directory");
 	}
 	
	/**
	 * Interator; Rewind to the first item.
	 */
 	public function rewind()
 	{
 		throw new Fs_Exception("Unable to traverse through '{$this->_path}': File is not a directory");
 	}
 	
	/**
	 * Interator; Check if there is a current item after calls to rewind() or next(). 
	 */
 	public function valid()
 	{
 		throw new Fs_Exception("Unable to traverse through '{$this->_path}': File is not a directory");
 	}
 	
 	/**
 	 * Countable; Count files in directory
 	 * @return int
 	 */
 	public function count()
 	{
 		throw new Fs_Exception("Unable to count items in '{$this->_path}': File is not a directory");
 	}	
	
 	
 	/**
 	 * Magic get method; Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public final function __get($name)
 	{
 		return $this->get($name);
 	}

 	/**
 	 * Magic get method; Check if file in directory exists.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public final function __isset($name)
 	{
 		return $this->has($name);
 	}
 	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function get($name)
 	{
 		throw new Fs_Exception("Unable to get '{$this->_path}/$name': '{$this->_path}' is not a directory, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
 	
 	/**
 	 * Check if file in directory exists.
 	 * 
 	 * @param string $name
 	 * @return boolean
 	 */
 	public function has($name)
 	{
 		throw new Fs_Exception("Unable to get '{$this->_path}/$name': '{$this->_path}' is not a directory, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
 	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_File
 	 */
 	public function file($name)
 	{
 		throw new Fs_Exception("Unable to get '{$this->_path}/$name': '{$this->_path}' is not a directory, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
 	
 	/**
 	 * Get subdirectory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Dir
 	 */
 	public function dir($name)
 	{
 		throw new Fs_Exception("Unable to get '{$this->_path}/$name': '{$this->_path}' is not a directory, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
 	
 	/**
 	 * Get block device in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Block
 	 */
 	public function block($name)
 	{
 		throw new Fs_Exception("Unable to get '{$this->_path}/$name': '{$this->_path}' is not a directory, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
 	
 	/**
 	 * Get char device in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Char
 	 */
 	public function char($name)
 	{
 		throw new Fs_Exception("Unable to get '{$this->_path}/$name': '{$this->_path}' is not a directory, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
 	
 	/**
 	 * Get fifo in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Fifo
 	 */
 	public function fifo($name)
 	{
 		throw new Fs_Exception("Unable to get '{$this->_path}/$name': '{$this->_path}' is not a directory, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
 	
 	/**
 	 * Get socket in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Socket
 	 */
 	public function socket($name)
 	{
 		throw new Fs_Exception("Unable to get '{$this->_path}/$name': '{$this->_path}' is not a directory, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
 	
    
	/**
	 * Returns the target of the symbolic link.
	 * 
	 * @return string
	 */
	public function target()
	{
		if (!($this instanceof Fs_Symlink)) throw new Fs_Exception("Unable to get target of '{$this->_path}: File is not a symbolic link.");
		
		$path = @readlink($this->_path);
		if ($path === false) throw new Fs_Exception("Unable to read link '{$this->_path}'", error_get_last());
		return $path;
	}
	
	/**
	 * Return final path for broken link.
	 * 
	 * @param int $count  Counter to break deadloop (max 16)
	 * @return Fs_Node
	 */
	protected function realpathBestEffort($flags, $count=0)
	{
		if ($count >= 16) return false;
				
		$target = $this->realpath($flags | Fs::NO_DEREFERENCE);
		return $target instanceof Fs_Symlink ? $target->realpathBestEffort($flags, $count+1) : $target;
	}
	
	/**
	 * Returns Fs_Node of canonicalized absolute pathname, resolving symlinks.
	 * Unlike the realpath() PHP function, this returns a best-effort for non-existent files.
	 * 
	 * Use Fs::NO_DEREFERENCE to not dereference if target is a symlink.
	 * 
	 * @param int $flags  Fs::% options
	 * @return Fs_Node
	 */
	public function realpath($flags=0)
	{
		if (!($this instanceof Fs_Symlink)) return $this;
		
		if ($flags & Fs::NO_DEREFERENCE) {
			$target = Fs::canonicalize($this->target(), dirname($this->_path));
			return is_link($target) ? new static($target) : call_user_func(array('Q\Fs', Fs::typeOfNode($this, Fs::ALWAYS_FOLLOW)), $target);
		
		} else {
			$path = realpath($this->_path);
			if ($path) return Fs::get($path);
			
			$file = $this->realpathBestEffort($flags);
			if (!$file) throw new Fs_Exception("Unable to resolve realpath of '{$this->_path}': Too many levels of symbolic links.");
			return $file;
		}
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
 	 * Find files matching a pattern, relative to this directory.
 	 * @see http://www.php.net/glob
 	 * 
 	 * @param string $pattern
 	 * @param int    $flags    GLOB_% options as binary set
 	 * @return Fs_Node[]
 	 */
 	public function glob($pattern, $flags=0)
 	{
 		throw new Fs_Exception("Unable to glob in '{$this->_path}': File is not a directory");
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
		
		if (extension_loaded('posix')) {
	    	$stat['owner'] = ($info = posix_getpwuid($stat['uid'])) ? $info['name'] : $stat['uid'];
	    	$stat['group'] = ($info = posix_getgrgid($stat['gid'])) ? $info['name'] : $stat['gid'];
		} else {
			$stat['owner'] = $stat['uid'];
			$stat['group'] = $stat['gid'];
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
		
		$file = $flags & Fs::NO_DEREFERENCE ? $this->_path : (string)$this->realpath();
		$attr = @xattr_list($file, $flags);
		
		if ($attr === false) throw new Fs_Exception("Failed to get extended attributes of '{$this->_path}'", error_get_last());
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
			if (!$this->exists()) throw new Fs_Exception("Unable to get attribute '$att' of '{$this->_path}': " . (is_link($this->_path) ? "File is a broken link" : "File does not exist"));
			throw new Fs_Exception("Unable to get attribute '$att' of '{$this->_path}'", $err);
		}
		
    	if (isset($stat[$att])) return $stat[$att];
		
    	if ($att == 'type') return Fs::mode2type($stat['mode']);
    	if ($att == 'perms') return Fs::mode2perms($stat['mode']);
    	if ($att == 'owner') return (extension_loaded('posix') && ($info = posix_getpwuid($stat['uid']))) ? $info['name'] : $stat['uid'];
    	if ($att == 'group') return (extension_loaded('posix') && ($info = posix_getgrgid($stat['gid']))) ? $info['name'] : $stat['gid'];
    	
    	if (!extension_loaded('xattr') || !xattr_supported($this->_path, $flags)) {
	    	trigger_error("Unable to get attribute '$att' of '{$this->_path}': Extended attributes are not supported.", E_USER_NOTICE);
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
		return in_array($att, array('size', 'type', 'atime', 'ctime', 'mtime', 'mode', 'perms', 'uid', 'owner', 'gid', 'group', 'dev', 'ino', 'nlink', 'rdev', 'blksize', 'blocks'))
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
	 * @param  int $flags  FS::% options
	 * @return int
	 * @throws Exception if posix extension is not available.
	 */
	public function modeBitShift($user, $flags=0)
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
		return file_exists($this->_path) || ($this instanceof Fs_Symlink && $flags && Fs::NO_DEREFERENCE && is_link($this->_path));
	}
	
	/**
	 * Tells whether the file is executable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isExecutable($flags=0)
	{
		return ($this instanceof Fs_Symlink && $flags && Fs::NO_DEREFERENCE) ?
		 (bool)(($this->getAttribute('mode', Fs::NO_DEREFERENCE) >> $this->modeBitShift()) & 1) :
		 is_executable($this->_path);
	}
	
	/**
	 * Tells whether the file is readable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isReadable($flags=0)
	{
		return ($this instanceof Fs_Symlink && $flags && Fs::NO_DEREFERENCE) ?
		 (bool)(($this->getAttribute('mode', Fs::NO_DEREFERENCE) >> $this->modeBitShift()) & 2) :
		 is_readable($this->_path);
	}
	
	/**
	 * Tells whether the file is writable or creatable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isWritable($flags=0)
	{
		return (($this instanceof Fs_Symlink && $flags && Fs::NO_DEREFERENCE) ?
		  (bool)(($this->getAttribute('mode', Fs::NO_DEREFERENCE) >> $this->modeBitShift()) & 4) :
		  is_writable($this->_path)) || (!$this->exists() && $this->realpath(Fs::ALWAYS_FOLLOW)->up()->isWritable($flags & ~Fs::NO_DEREFERENCE));
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
	 * Tells whether the file was uploaded via HTTP POST.
	 * 
	 * @return boolean
	 */
	public function isUploadedFile()
	{
		return is_uploaded_file($this->_path);
	}
		
 	/**
 	 * Return the number of bytes on the corresponding filesystem or disk partition.
 	 * 
 	 * @return float
 	 */
 	public function diskTotalSpace()
 	{
 		throw new Fs_Exception("Unable to get total disk space of '{$this->_path}': File is not a directory");
 	}
 	
 	/**
 	 * Return the number of bytes available on the corresponding filesystem or disk partition.
 	 * 
 	 * @return float
 	 */
 	public function diskFreeSpace()
 	{
 		throw new Fs_Exception("Unable to get free disk space of '{$this->_path}': File is not a directory");
 	}
	 	
    /**
     * Sets access and modification time of file.
     * @see http://www.php.net/touch
     * 
     * @param int|string|\DateTime $time   Defaults to time()
     * @param int|string|\DateTime $atime  Defaults to $time
     * @param int                  $flags  Fs::% options as binary set
     * @throws Fs_Exception if touch fails.
     * 
     * @todo Implement support for several options of $flags for Fs_Node::touch() like Fs:NO_DEREFERENCE (symlink)
     */
    public function touch($time=null, $atime=null, $flags=0)
    {
		if (!$this->exists()) {
		    if (!($this instanceof Fs_File)) throw new Fs_Exception("Unable to touch '{$this->_path}': File does not exist and is not a regular file");
		    
			$dir = $this->realpath()->up();
			if (!$dir->exists()) {
				if (~$flags & Fs::RECURSIVE) throw new Fs_Exception("Unable to touch '{$this->_path}': Directory '{$dir->_path}' does not exist");
				$dir->create(0770, $flags);
			}
		}
    	
    	if (!isset($time)) $time = time();
    	  elseif (is_string($time)) $time = strtotime($time);
    	  elseif ($time instanceof \DateTime) $time = $time->getTimestamp();
    	 
    	if (!isset($atime)) $atime = $time;
    	  elseif (is_string($atime)) $atime = strtotime($atime);
    	  elseif ($atime instanceof \DateTime) $atime = $atime->getTimestamp();
    	
    	if (!@touch($this->_path, $time, $atime)) throw new Fs_Exception("Touch '{$this->_path}' failed", error_get_last());
    	$this->clearStatCache();
    }
    
    /**
     * Changes file mode.
     * Use of $flags might cause an exception based on the operation system.
     * 
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
        if (strpos($owner, ':')) throw new SecurityException("Won't change owner of '{$this->_path}' to user '$owner': To change both owner and group, user array(owner, group) instead");
		if (!$this->exists($flags)) throw new Fs_Exception("Unable to change owner of '{$this->_path}' to user '$owner': " . ($this instanceof Fs_Symlink && is_link($this->_path) ? "Unable to dereference symlink" : "File does not exist"));
		
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
 	 * Create this file.
 	 * Use Fs::PRESERVE to simply return if file already exists
 	 * 
 	 * @param int $mode   File permissions, umask applies
 	 * @param int $flags  Fs::% options
 	 * @throws Fs_Exception
 	 */
	public function create($mode=0666, $flags=0)
 	{
 		if ($this->exists() && $flags & Fs::PRESERVE) return;
 		throw new Fs_Exception("Unable to create '{$this->_path}': File is a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
 	}
		
	/**
	 * Copy or rename/move this file.
	 * 
	 * @param callback $fn     copy or rename
	 * @param Fs_Dir   $dir
	 * @param string   $name
	 * @param int      $flags  Fs::% options as binary set
	 * @return Fs_Node
	 */
	protected function doCopyRename($fn, $dir, $name, $flags=0)
	{
		if (empty($name) || $name == '.' || $name == '..' || strpos('/', $name) !== false) throw new SecurityException("Unable to $fn '{$this->_path}' to '$dir/$name': Invalid filename '$name'");
		
		if (!($dir instanceof Fs_Dir)) $dir = Fs::dir($dir, dirname($this->_path));
		
		if (!$dir->exists()) {
			if (~$flags & Fs::MKDIR) throw new Fs_Exception("Unable to " . ($fn == 'rename' ? 'move' : $fn) . " '{$this->_path}' to '$dir/': Directory does not exist");
			$dir->create();
		} elseif ($dir->has($name)) {
			$dest = $dir->$name;
			
			if ($dest instanceof Fs_Dir && !($dest instanceof Fs_Symlink) && count($dest) != 0) throw new Fs_Exception("Unable to $fn '{$this->_path}' to '{$dest->_path}': Target is a non-empty directory");
			if ($flags & Fs::UPDATE == Fs::UPDATE && $dest['ctime'] >= $this['ctime']) return false;
			if (~$flags & Fs::OVERWRITE) throw new Fs_Exception("Unable to $fn '{$this->_path}' to '{$dest->_path}': Target already exists");
			
			if ($dest instanceof Fs_Dir) {
				if (!@rmdir($dest->_path)) throw new Fs_Exception("Failed to $fn '{$this->_path}' to '$dir/$name'", error_get_last());
			} elseif ($this instanceof Fs_Dir) {
			    if (!unlink($dest->_path)) throw new Fs_Exception("Failed to $fn '{$this->_path}' to '$dir/$name'", error_get_last());
			}
		}

		if ($fn == 'copy' && $flags & Fs::NO_DEREFERENCE) return Fs::symlink($this->target(), "$dir/$name");
		
		if (!@$fn($this->_path, "$dir/$name")) throw new Fs_Exception("Failed to $fn '{$this->_path}' to '$dir/$name'", error_get_last());
		return Fs::get("$dir/$name");
	}
	
	/**
	 * Create a copy of this file. 
	 * 
	 * @param string $newname
	 * @param int    $flags
	 * @return Fs_Node
	 */
	public function copy($newname, $flags=0)
	{
		return $this->doCopyRename('copy', dirname($newname), basename($newname), $flags);
	}

	/**
	 * Copy this to another directory.
	 * 
	 * @param string|Fs_Dir $dir
	 * @param int           $flags  Fs::% options as binary set
	 * @return Fs_Node
	 */
	public function copyTo($dir, $flags=0)
	{
		return $this->doCopyRename('copy', $dir, $this->basename(), $flags);
	}
	
	/**
	 * Rename this file.
	 * 
	 * @param string $newname
	 * @param int    $flags  Fs::% options as binary set
	 * @return Fs_Node
	 */
	public function rename($newname, $flags=0)
	{
		return $this->doCopyRename('rename', dirname($newname), basename($newname), $flags);
	}

	/**
	 * Move this file to another directory.
	 * 
	 * @param string|Fs_Dir $dir
	 * @param int           $flags  Fs::% options as binary set
	 * @return Fs_Node
	 */
	public final function moveTo($dir, $flags=0)
	{
		return $this->doCopyRename('rename', $dir, $this->basename(), $flags);
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
	 * Reads entire file into a string.
	 * 
	 * @param int $flags   FILE_% flags as binary set.
	 * @param int $offset  The offset where the reading starts.
	 * @param int $maxlen  Maximum length of data read.
	 * @return string
	 */
	public function getContents($flags=0, $offset=0, $maxlen=null)
	{
		throw new Fs_Exception("Unable to get the contents of '{$this->_path}': File is a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
	}

	/**
	 * Write a string to a file.
	 * 
	 * @param mixed $data   The data to write; Can be either a string, an array or a stream resource. 
	 * @param int   $flags  Fs::RECURSIVE and/or FILE_% flags as binary set.
	 * @return int
	 */
	public function putContents($data, $flags=0)
	{
		throw new Fs_Exception("Unable to write data to '{$this->_path}': File is a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
	}
	
	/**
	 * Output contents of the file.
	 * 
	 * @return int
	 */
	public function output()
	{
		throw new Fs_Exception("Unable to output data from '{$this->_path}': File is a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
	}
	
	/**
	 * Open the file.
	 * @see http://www.php.net/fopen
	 * 
	 * @param string $mode  The mode parameter specifies the type of access you require to the stream.
	 * @return resource
	 */
	public function open($mode='r+')
	{
		throw new Fs_Exception("Unable to open '{$this->_path}': File is a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
	}

    /**
     * Open the connection as a server.
     * 
     * @return resource
     */
    public function listen()
    {
    	throw new Fs_Exception("Unable to listen to '{$this->_path}': File is a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
    }	
	
	/**
	 * Execute file and return content of stdout.
	 * 
	 * @param Parameters will be escaped and passed as arguments.
	 * @return string
	 * @throws Fs_Exception if execution is not possible.
	 * @throws ExecException if execution fails.
	 */
	public function exec()
	{
		throw new Fs_Exception("Unable to execute '{$this->_path}': This is not a regular file, but a " . Fs::typeOfNode($this, Fs::DESCRIPTION));
	}

	/**
	 * Magic method for when object is used as function; Calls Fs_Node::exec().
	 * 
	 * @param Parameters will be escaped and passed as arguments.
	 * @return string
	 * @throws ExecException if execution fails.
	 */
	public final function __invoke()
	{
		$args = func_get_args();
		return call_user_func_array(array($this, 'exec'), $args);
	}
	
	
	/**
	 * This static method is called for classes exported by var_export(). 
	 *  
	 * @param array $props
	 * @return Fs_Node
	 */
	public static function __set_state($props)
	{
		return new static($props['_path']);
	}
	
	/**
	 * Call added functionality by mixins.
	 * 
	 * @param string $method
	 * @param array  $args
	 * @return mixed
	 */
	public  function __call($method, $args)
	{
		if (!ctype_alnum($method)) throw new SecurityException("Won't call '$method' for " . get_class($this) . ": Invalid method name.");
		
		foreach (Fs::$mixins as $mixin) {
			if (is_callable(array($mixin, $method))) return eval("return {$this->mixin}::$method(" . (!empty($args) ? '$args[' . join('], $args[', array_keys($args)) . ']' : '') . ");");
		}
		trigger_error("Call to undefined method " . get_class($this) . "::$method()", E_USER_ERROR);
	}
}
