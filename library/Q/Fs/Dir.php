<?php
namespace Q;

require_once 'Q/Fs/Item.php';

/**
 * Interface of a directory.
 * 
 * @package Fs
 */
class Fs_Dir extends Fs_Item implements \Iterator, \Countable
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
	 * @return Fs_Item
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
 	 * @return Fs_Item[]
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
		return is_writable($this->_path) || $flags & Fs::RECURSIVE && !$this->exists() && $this->up()->isWritable($flags);
	}
 	
 	/**
 	 * Return the number of bytes on the corresponding filesystem or disk partition.
 	 * 
 	 * @return float
 	 */
 	public function diskTotalSpace()
 	{
 		return disk_total_space($this->_path);
 	}
 	
 	/**
 	 * Return the number of bytes available on the corresponding filesystem or disk partition.
 	 * 
 	 * @return float
 	 */
 	public function diskFreeSpace()
 	{
 		return disk_free_space($this->_path);
 	}
 	
 	
 	/**
 	 * Magic get method; Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function __get($name)
 	{
 		return Fs::get($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}

 	/**
 	 * Magic get method; Check if file in directory exists.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function __isset($name)
 	{
 		return file_exists($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function get($name)
 	{
 		return Fs::get($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Check if file in directory exists.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function has($name)
 	{
 		return file_exists($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function file($name)
 	{
 		return Fs::file($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	/**
 	 * Get subdirectory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function dir($name)
 	{
 		return Fs::dir($name[0] == '/' ? $name : "{$this->_path}/$name");
 	}
 	
 	
 	/**
 	 * Create this directory, if is does not exist.
 	 * 
 	 * @param int $mode
 	 * @param int $flags  Optional Fs::RECURSIVE
 	 * @throws Fs_Exception if creating the directoy fails 
 	 */
 	public function create($mode=0770, $flags=0)
 	{
 		if (!@mkdir($this->_path, $mode, $flags & Fs::RECURSIVE)) {
 			$err = error_get_last();
 			throw new Fs_Exception("Failed to create directory '{$this->_path}'; {$err['message']}");
 		}
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
		return $flags & Fs::MERGE ? $this->doMerge($fn, $dir, $name, $flags) : parent::doCopyRename($fn, $dir, $name, $flags);  
	}
	
	/**
	 * Copy or rename/move this file, merging where possible.
	 * 
	 * @param callback $fn     Function name; copy or rename
	 * @param Fs_Dir   $dir
	 * @param string   $name
	 * @param int      $flags  Fs::% options as binary set
	 * @return Fs_Item
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
				if (~$flags & Fs::OVERWRITE) throw new Fs_Exception("Unable to $fn '{$this->_path}' to '$dir/$name'; Target exists and is not a directory but a {$dir['type']}.");
				$dest->delete();
			}
			
			if (!@$fn($this->_path, "$dir/$name")) {
				$err = error_get_last();
				throw new Fs_Exception("Failed to $fn '{$this->_path}' to '$dir/$name'; {$error['message']}");
			}
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
						if (!@unlink("{$this->_path}/$file")) {
							$err = error_get_last();
							throw new Fs_Exception("Failed to delete '{$this->_path}/$file'; {$error['message']}");
						}
					} else {
						$this->$file->delete($flags);
					}
				} catch (Fs_Exception $e) {
					$exceptions[] = $e;
				}
			}
		}
		
		if (!@rmdir($this->_path)) {
			$err = error_get_last();
			throw new Fs_Exception("Failed to delete '{$this->_path}'; {$error['message']}", $exceptions);
		}
	}
}
