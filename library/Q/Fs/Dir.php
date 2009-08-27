<?php
namespace Q;

require_once 'Q/Fs/Item.php';

/**
 * Interface of a directory.
 * 
 * @package Fs
 */
class Fs_Dir extends Fs_Item implements Iterator
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
		if (is_link($path) || (file_exists($path) && !is_dir($path))) throw new Fs_Exception("File '$path' is not a directory, but a " . filetype($path) . "."); 
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
			
		$resource = opendir($this->path);
		if (!$resource) throw new Fs_Exception("Unable to traverse through directory '{$this->path}'; Failed to read directory.");
		
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
 	 * Find files matching a pattern, relative to this directory.
 	 * @see http://www.php.net/glob
 	 * 
 	 * @param string $pattern
 	 * @param int    $flags    GLOB_% options as binary set
 	 * @return Fs_Item[]
 	 */
 	public function glob($pattern, $flags=0)
 	{
 		if ($pattern[0] != '/') $pattern = "{$this->path}/$pattern";
 		return Fs::glob($pattern, $flags);
 	}

 	
 	/**
 	 * Return the number of bytes on the corresponding filesystem or disk partition.
 	 * 
 	 * @return float
 	 */
 	public function diskTotalSpace()
 	{
 		return disk_total_space($this->path);
 	}
 	
 	/**
 	 * Return the number of bytes available on the corresponding filesystem or disk partition.
 	 * 
 	 * @return float
 	 */
 	public function diskFreeSpace()
 	{
 		return disk_free_space($this->path);
 	}
 	
 	
 	/**
 	 * Magic get method; Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function __get($name)
 	{
 		return Fs::get("{$this->path}/$name");
 	}

 	/**
 	 * Get file in directory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function file($name)
 	{
 		return Fs::file("{$this->path}/$name");
 	}
 	
 	/**
 	 * Get subdirectory.
 	 * 
 	 * @param string $name
 	 * @return Fs_Item
 	 */
 	public function dir($name)
 	{
 		return Fs::dir("{$this->path}/$name");
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
 		$success = @mkdir($this->path, $mode, $flags & Fs::RECURSIVE);
 		
 		if (!$success) {
 			$err = error_get_last();
 			throw new Fs_Exception("Failed to create directory '{$this->path}'; " . $err['message']);
 		}
 	}
 	
	/**
	 * Delete the directory (and possibly the contents).
	 * 
	 * @param int $flags  Fs::% options as binary set
	 */
	public function delete($flags=0)
	{
		if ($flags & Fs::RECURSIVE) parent::delete($flags);
		  else rmdir($this->path);
	}
}
