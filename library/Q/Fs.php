<?php
namespace Q;

require_once 'Q/Fs/Exception.php';

require_once 'Q/Fs/Block.php';
require_once 'Q/Fs/Char.php';
require_once 'Q/Fs/Dir.php';
require_once 'Q/Fs/Fifo.php';
require_once 'Q/Fs/File.php';
require_once 'Q/Fs/Socket.php';
require_once 'Q/Fs/Unknown.php';

require_once 'Q/Fs/Symlink/Block.php';
require_once 'Q/Fs/Symlink/Broken.php';
require_once 'Q/Fs/Symlink/Char.php';
require_once 'Q/Fs/Symlink/Dir.php';
require_once 'Q/Fs/Symlink/Fifo.php';
require_once 'Q/Fs/Symlink/File.php';
require_once 'Q/Fs/Symlink/Socket.php';
require_once 'Q/Fs/Symlink/Unknown.php';


/**
 * Interface to the filesystem.
 * 
 * The interface to a regular file can be invoked, if the file is executable.
 *  
 * 
 * @package Fs
 */
class Fs
{
	/**
	 * Option; Don't dereference symlinks. 
	 * {@internal Should to have the same value as XATTR_DONTFOLLOW}}
	 */
	const DONTFOLLOW = 0x0001;

	/**
	 * Option; Overwrite if item exists.
	 * {@internal Should to have the same value as XATTR_REPLACE}}
	 */
	const OVERWRITE = 0x0020;

	/** Option; Traverse every symbolic link to a directory encountered. */
	const ALWAYSFOLLOW = 0x0100;
	
	/** Option; Do action recursively / Auto-create parent directories. */
	const RECURSIVE = 0x0200;

	/** Option; Preserve mode, ownership and timestamps. */
	const PRESERVE = 0x0400;
	
	/** Option; Overwrite if item is newer. */
	const UPDATE = 0x1000;
	
	
	/**
	 * Resolves references to '/./', '/../' and extra '/' characters in the input path.
	 * Symlinks are not resolved and the file doesn't need to exist.
	 *
	 * @param string $path
	 * @return string
	 * 
	 * @todo Fs::canonicalize() will give unexpected result for windows paths.
	 */
	public static function canonicalize($path)
	{
		$path = (string)$path;
		if (empty($path)) return getcwd();
		if ($path[0] == '/' && !preg_match('%(?:/|^)(?:\.\.?|~)(?:/|$)%', $path)) return preg_replace(array('%(?<!^)/+$%', '%/{2,}%'), array('', '/'), $path);

		$canpath = "";
		if ($path == '~' || strncmp('~/', $path, 2)) {
			$path = realpath('~') . substr($path, 1);
		} elseif ($path[0] != '/') {
			$path = getcwd() . $path;
		}
        
		$canpath = "";
		foreach (preg_split('|(?<!\\\\)/+|', rtrim($path, '/')) as $part) {
			switch ($part) {
				case '':
				case '.':	break;
            	case '..':	$canpath = dirname($canpath); break;
				default:	$canpath .= "/$part";	
			}
		}

		return $canpath;
	}
	
	
    /**
     * Get an Fs interface for a directory.
     * 
     * @param string $path
     * @return Fs_Dir
     */
    public static function dir($path)
    {
        return new Fs_Dir($path);
    }

    /**
     * Get an Fs interface for a regular file.
     * 
     * @param string $path
     * @return Fs_Dir
     */
    public static function file($path)
    {
        return new Fs_File($path);
    }

    /**
     * Create a symlink and return the Fs interface.
     * 
     * @param string $target
     * @param string $link
     * @param int    $flags   Fs::% options as binary set
     * @return Fs_Dir
     */
    public static function symlink($target, $link, $flags=self::RECURSIVE)
    {
    	if (is_link($link) && $flags & self::OVERWRITE) unlink($link);
    	
    	if (!@symlink($target, $link)) {
    		$err = error_get_last();
    		throw new Fs_Exception("Failed to create symlink '$link' to '$target'; " . $err['message']);
    	}
    	
        return Fs::get($link);
    }

    /**
     * Get an Fs interface for an item of the filesystem.
     * 
     * @param string $path
     * @return Fs_Item
     * @throws Fs_Exception is file doesn't exits
     */
    public static function get($path)
    {
    	if (is_link($path)) {
    		if (!file_exists($path)) return new Fs_Symlink_Broken($path);
	    	if (is_file($path)) return new Fs_Symlink_File($path);
	    	if (is_dir($path)) return new Fs_Symlink_Dir($path);
	    	
	    	switch (filetype(realpath($path))) {
	    		case 'fifo'   : return new Fs_Symlink_Fifo($path);
	    		case 'char'   : return new Fs_Symlink_Char($path);
	    		case 'block'  : return new Fs_Symlink_Block($path);
	    		case 'socket' : return new Fs_Symlink_Socket($path);
	    		default:        return new Fs_Symlink_Unknown($path);
	    	}
        }

    	if (!file_exists($path)) throw new Fs_Exception("File '$path' does not exist");
    	if (is_file($path)) return new Fs_File($path);
    	if (is_dir($path)) return new Fs_Dir($path);
    	
    	switch (filetype($path)) {
    		case 'fifo'   : return new Fs_Fifo($path);
    		case 'char'   : return new Fs_Char($path);
    		case 'block'  : return new Fs_Block($path);
    		case 'socket' : return new Fs_Socket($path);
    		default:        return new Fs_Unknown($path);
    	}
    }
    
 	/**
 	 * Find files matching a pattern.
 	 * @see http://www.php.net/glob
 	 * 
 	 * @param string $pattern
 	 * @param int    $flags    GLOB_% options as binary set
 	 * @return Fs_Item[]
 	 */
 	public function glob($pattern, $flags=0)
 	{
 		$files = array();
 		foreach (glob($pattern, $flags) as $filename) $files[] = Fs::get($filename);
 		return $files;
 	}
 	
 	/**
 	 * Find executable file in enviroment path (as `which` command)
 	 * 
 	 * @param string $file
 	 * @return Fs_File
 	 */
 	public function bin($file)
 	{
 		$paths = getenv('PATH');
 		foreach (explode(PATH_SEPARATOR, $paths) as $path) {
 			if (is_file("$path/$file") && is_executable("$path/$file")) {
 				$exec = "$path/$file";
 				break;
 			}
 		}
 		
 		if (!isset($exec)) throw new Fs_Exception("Cound not find executable '$file' (PATH=$paths).");
 		return self::file($exec);
 	} 	
    
 	
    /**
     * Clears file status cache.
     * 
     * @param boolean $clear_realpath_cache  Whenever to clear realpath cache or not.
     */
    public static function clearStatCache($clear_realpath_cache=false)
    {
    	clearstatcache($clear_realpath_cache);
    }
}
