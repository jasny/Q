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
		
		self::$handles[$id] = (object)array('resource'=>$resource, 'current'=>readfile($resource));
		return self::$handles[$id];
	}
	
	/**
	 * Interator; Returns the current file object.
	 * 
	 * @return Fs_Item
	 */
	public function current()
	{
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
 		if ($pattern[0] != '/') $pattern = $this->path . $pattern;
 		return Fs::glob($pattern, $flags);
 	}

 	
 	/**
 	 * Return the number of bytes on the corresponding filesystem or disk partition.s
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
}
