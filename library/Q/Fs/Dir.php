<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a directory.
 * 
 * @package Fs
 */
class Fs_Dir extends Fs_Node
{
	/**
	 * Directory handles for traversing.
	 * 
	 * Resources are stored statically by object hash and not in object, because this will cause
	 * the == operator to work as expected.
	 * 
	 * @var array
	 */
	static private $handles;
	
	
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink.");
		if (file_exists($path) && !is_dir($path)) throw new Fs_Exception("File '$path' is not a directory, but a " . filetype($path) . "."); 
		parent::__construct($path);
	}
	
	/**
	 * Class destructor; Clean up handle.
	 */
	public function __destruct()
	{
		unset(self::$handles[spl_object_hash($this)]);
	}
	
	/**
	 * Get directory resource.
	 * 
	 * @return resource
	 */
	protected function getHandle()
	{
		$id = spl_object_hash($this);
		if (isset(self::$handles[$id])) return self::$handles[$id];
			
		$resource = opendir($this->_path);
		if (!$resource) throw new Fs_Exception("Unable to traverse through directory '{$this->_path}'; Failed to read directory.");
		
		self::$handles[$id] = (object)array('resource'=>$resource);
		return self::$handles[$id];
	}
	
	/**
	 * Interator; Returns the current file object.
	 * 
	 * @return Fs_Node
	 */
	public function current()
	{
		$handle = $this->getHandle(); 
		while (!isset($handle->current) || $handle->current == '.' || $handle->current == '..') $handle->current = readdir($handle->resource);
		
		return Fs::open($this->getHandle()->current);
	}
	
	/**
	 * Interator; Returns the current filename.
	 * 
	 * @return string
	 */
	public function key()
 	{
 		return $this->getHandle()->current;
 	}
 	
	/**
	 * Interator; Move forward to next item.
	 */
 	public function next()
 	{
 		$handle = $this->getHandle(); 
 		$handle->current = readdir($handle->resource);
 	}
 	
	/**
	 * Interator; Rewind to the first item.
	 */
 	public function rewind()
 	{
 		$handle = $this->getHandle();
 		$handle->current = rewinddir($handle->resource);
 	}
 	
	/**
	 * Interator; Check if there is a current item after calls to rewind() or next(). 
	 */
 	public function valid()
 	{
 		return $this->getHandle()->current !== false;
 	}
 	
 	/**
 	 * Countable; Count files in directory
 	 * @return int
 	 */
 	public function count()
 	{
 		$files = scandir($this->_path);
 		return count($files) - (array_search('..', $files, true) !== false ? 2 : 1);
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
 		if ($pattern[0] != '/') $pattern = "{$this->_path}/$pattern";
 		return Fs::glob($pattern, $flags);
 	}

 	
	/**
	 * Tells whether the dir is writable.
	 * 
	 * @param int $flags  FS::% options
	 * @return boolean
	 */
	public function isWritable($flags=0)
	{
		return (($this instanceof Fs_Symlink && $flags && Fs::NO_DEREFERENCE) ?
		  (bool)(($this->getAttribute('mode', Fs::NO_DEREFERENCE) >> $this->modeBitShift()) & 4) :
		  is_writable($this->_path)) ||
		 ($flags & Fs::RECURSIVE && !$this->exists() && $this->up()->isWritable($flags & ~Fs::NO_DEREFERENCE));		
	}
 	
 	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function get($name)
 	{
 		return Fs::get($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Check if file in directory exists.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function has($name)
 	{
 		return file_exists($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function file($name)
 	{
 		return Fs::file($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get subdirectory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function dir($name)
 	{
 		return Fs::dir($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get block device in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Block
 	 */
 	public function block($name)
 	{
 		return Fs::block($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get char device in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Char
 	 */
 	public function char($name)
 	{
 		return Fs::char($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get fifo in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Fifo
 	 */
 	public function fifo($name)
 	{
 		return Fs::fifo($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get socket in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Socket
 	 */
 	public function socket($name)
 	{
 		return Fs::socket($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	
 	/**
 	 * Create this directory.
 	 * Use Fs::PRESERVE to simply return if file already exists
 	 * 
 	 * @param int $mode   File permissions, umask applies
 	 * @param int $flags  Fs::% options
 	 * @throws Fs_Exception if mkdir fails
 	 */
 	public function create($mode=0777, $flags=0)
 	{
 		if ($this->exists()) {
 			if ($flags & Fs::PRESERVE) return;
 			throw new Fs_Exception("Unable to create '{$this->_path}': Directory already exists");
 		}
 		
 		if (!@mkdir($this->_path, $mode, $flags & Fs::RECURSIVE)) throw new Fs_Exception("Failed to create directory '{$this->_path}'", error_get_last());
 		$this->clearStatCache();
 	}
 	
 	/**
	 * Copy or rename/move this file.
	 * 
	 * @param callback $fn     Function name; copy or rename
	 * @param Fs_Dir   $dir
	 * @param string   $name
	 * @param int      $flags  Fs::% options as binary set
	 * @return Fs_Node
	 */
	protected function doCopyRename($fn, $dir, $name, $flags)
	{
		return $flags & Fs::MERGE ? $this->doMerge($fn, $dir, $name, $flags) : parent::doCopyRename($fn, $dir, $name, $flags);  
	}
	
	/**
	 * Copy or rename/move this file, merging where possible.
	 * 
	 * @param callback $fn     Function name; copy or rename
	 * @param Fs_Dir   $dir
	 * @param string   $name
	 * @param int      $flags  Fs::% options as binary set
	 * @return Fs_Node
	 */
 	protected function doMerge($fn, $dir, $name, $flags)
 	{
		if (empty($name) || $name == '.' || $name == '..' || strpos('/', $name) !== false) throw new SecurityException("Unable to $fn '{$this->_path}' to '$dir/$name'; Invalid filename '$name'.");
		
		if (!($dir instanceof Fs_Dir)) $dir = Fs::dir($dir);
		
		if (!$dir->exists()) {
			if (~$flags & Fs::RECURSIVE) throw new Fs_Exception("Unable to $fn '{$this->_path}' to '$dir/$name'; Directory does not exist.");
			$dir->create();
		}
		
		if ($dir->has($name)) $dest = $dir->$name;
			
		if (isset($dest) && $dest instanceof Fs_Dir) {
			$destpath = (string)$dest;
			$files = scandir($this->_path);
			
			foreach ($files as $file) {
				if ($file == '.' || $file == '..') continue;
				
				try {
					if (is_dir("{$this->_path}/$file)") && is_dir("$destpath/$file")) {
						$this->$file->doMerge($fn, $dest, $file, $flags);
					} else {
						if (file_exists("$destpath/$file")) {
							if ($flags & Fs::OVERWRITE);
							  elseif ($flags & Fs::UPDATE && filectime("$destpath/$file") >= filectime("{$this->_path}/$file")) continue;
							  else { trigger_error("Unable to $fn '{$this->_path}/$file' to '$destpath/$file'; File already exists.", E_USER_WARNING); continue; }
							
							if (is_dir("$destpath/$file")) $dest->dir($file)->delete(Fs::RECURSIVE);
						}
						
						$fn("{$this->_path}/$file)", "$destpath/$file");
					}
				} catch (Fs_Exception $e) {
					trigger_error($e->getMessage(), E_USER_WARNING);
				}
			}
			
		} else {
			if (isset($dest)) {
				if (~$flags & Fs::OVERWRITE) throw new Fs_Exception("Unable to $fn '{$this->_path}' to '$dir/$name'; Target exists and is not a directory");
				$dest->delete();
			}
			
			if (!@$fn($this->_path, "$dir/$name")) throw new Fs_Exception("Failed to $fn '{$this->_path}' to '$dir/$name'", error_get_last());
		}
		
		return "$dir/$name";
	}
 	
	/**
	 * Delete the directory (and possibly the contents).
	 * 
	 * @param int $flags  Fs::% options as binary set
	 */
	public function delete($flags=0)
	{
		if (!$this->exists()) return;
		
		$exceptions = null;
		
		if ($flags & Fs::RECURSIVE) {
			$files = scandir($this->_path);
			
			foreach ($files as $file) {
				if ($file == '.' || $file == '..') continue;
				
				try {
					if (!is_dir("{$this->_path}/$file)")) {
						if (!@unlink("{$this->_path}/$file")) throw new Fs_Exception("Failed to delete '{$this->_path}/$file'", error_get_last());
					} else {
						$this->$file->delete($flags);
					}
				} catch (Fs_Exception $e) {
					$exceptions[] = $e;
				}
			}
		}
		
		if (!@rmdir($this->_path)) throw new Fs_Exception("Failed to delete '{$this->_path}'", error_get_last(), $exceptions);
	}
}
