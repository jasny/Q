<?php
namespace Q;

require_once 'Q/Fs/Item.php';

/**
 * Interface of a regular file.
 * 
 * @package Fs
 */
class Fs_File extends Fs_Item
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (is_link($path) || (file_exists($path) && !is_file($path))) throw new Fs_Exception("File '$path' is not a regular file, but a " . filetype($path) . "."); 
		parent::__construct($path);
	}
	
	
	/**
	 * Tells whether the file was uploaded via HTTP POST.
	 * 
	 * @return boolean
	 */
	public function isUploadedFile()
	{
		return is_uploaded_file($this->path);
	}

	
	/**
	 * Reads entire file into a string.
	 * 
	 * @param int $flags     FILE_% flags as binary set.
	 * @param int $offset  The offset where the reading starts.
	 * @param int $maxlen  Maximum length of data read.
	 * @return string
	 */
	public function getContents($flags=0, $offset=-1, $maxlen=-1)
	{
		return file_get_contents($flags, $offset, $maxlen);
	}

	/**
	 * Write a string to a file.
	 * 
	 * @param mixed $data   The data to write; Can be either a string, an array or a stream resource. 
	 * @param int   $flags  FILE_% flags as binary set.
	 * @return int
	 */
	public function putContents($data, $flags=0)
	{
		return file_put_contents($data, $flags);
	}
	
	/**
	 * Output contents of the file.
	 * 
	 * @return int
	 */
	public function output()
	{
		readfile($this->name);
	}
	
	
	/**
	 * Transformation; Reads entire file into a string.
	 * 
	 * @return string
	 */
	public function asString()
	{
		return $this->getContents();
	}
	
	/**
	 * Transformation; Reads entire file into an array.
	 * Each line will be one entry in the array
	 * 
	 * @return array
	 */
	public function asArray()
	{
		return file($this->path);
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
		$resource = @fopen($this->path, $mode);
		
		if (!$resource) {
			$err = error_get_last();
			throw new Fs_Exception("Failed to open file; ". $err['message']);
		}
		
		return $resource;
	}
}
