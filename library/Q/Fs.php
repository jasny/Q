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
 * An Fs_File can be used as a function to execute as shell command.
 * 
 * @package Fs
 * 
 * @todo Only tested on Linux. Fs might work correctly on other systems.
 */
class Fs
{
	/**
	 * Option; Don't dereference symlinks. 
	 * {@internal Should to have the same value as XATTR_NODEREFERENCE}}
	 */
	const NO_DEREFERENCE = 0x0001;

	/**
	 * Option; Overwrite if item exists.
	 * {@internal Should to have the same value as XATTR_REPLACE}}
	 */
	const OVERWRITE = 0x0020;

	/** Option; Traverse every symbolic link to a directory encountered. */
	const ALWAYS_FOLLOW = 0x0100;
	
	/** Option; Do action recursively / Auto-create parent directories. */
	const RECURSIVE = 0x0200;

	/** Option; Preserve mode, ownership and timestamps. */
	const PRESERVE = 0x0400;

	/** Option; Merge directories. */
	const MERGE = 0x0800;
	
	/** Option; Overwrite if item is newer. */
	const UPDATE = 0x1000;
	

	/**
	 * Class for each type.
	 * Always extends the default classes of the types (for non link classes).
	 * 
	 * @var array
	 */
	public static $types = array(
		'block' => 'Q\Fs_Block',
		'char' => 'Q\Fs_Char',
		'dir' => 'Q\Fs_Dir',
		'fifo' => 'Q\Fs_Fifo',
		'file' => 'Q\Fs_File',
		'socket' => 'Q\Fs_Socket',
		'unknown' => 'Q\Fs_Unknown',

		'link/' => 'Q\Fs_Symlink_Broken',
		'link/block' => 'Q\Fs_Symlink_Block',
		'link/char' => 'Q\Fs_Symlink_Char',
		'link/dir' => 'Q\Fs_Symlink_Dir',
		'link/fifo' => 'Q\Fs_Symlink_Fifo',
		'link/file' => 'Q\Fs_Symlink_File',
		'link/socket' => 'Q\Fs_Symlink_Socket',
		'link/unknown' => 'Q\Fs_Symlink_Unknown'		
	);
	
	/**
	 * Bits in mode for each type.
	 * @var array
	 */
	public static $modetypes = array(
	  0140000 => 's socket',
	  0120000 => 'l link',
	  0100000 => '- file',
	  0060000 => 'b block',
	  0040000 => 'd dir',
	  0020000 => 'c char',
	  0010000 => 'p fifo',
	  0000000 => '  unknown'
	);
	
	/**
	 * Registered instances
	 * @var Fs_Item[]
	 */
	protected static $instances;
	
	
    /**
     * Get named path.
     * 
     * Predefined paths:
     *   root            /
     *   home            ~ (User home)
     *   cwd             getcwd() (Current working directory) 
     *   document_root   $_SERVER['DOCUMENT_ROOT']
     *   script          $_SERVER['SCRIPT_NAME']
     * 
     * @param string $name
     * @return Fs_Item
     */
    protected static function getPath($name)
    {
    	$name = strtolower($name);
    	if (isset(self::$instances[$name])) return self::$instances[$name];
    	
    	switch ($name) {
    		case 'root':             self::$instances['root'] = self::get('/'); break;
    		case 'home':             return self::get(getenv('HOME'));
    		case 'cwd':              return self::get(getcwd());
    		
    		case 'document_root':    self::$instances['document_root'] = self::get($_SERVER['DOCUMENT_ROOT']); break;
    		case 'script':           self::$instances['script'] = self::get($_SERVER['SCRIPT_NAME']); break;
    		
    		default:                 if (load_class('Q\Config') && !(Config::i() instanceof Mock) && Config::i()->fs[$name]) self::$instances[$name] = self::get(Config::i()->fs[$name]); 
    	}
    	
    	if (!isset(self::$instances[$name])) throw new Exception("Fs instance '$name' is not registered.");
    	return self::$instances[$name];
    }
    
	/**
	 * Magic method to return named path.
	 *
	 * @param string $name
	 * @param array  $args  Not used
	 * @return Fs_Item
	 */
	public static function __callstatic($name, $args)
	{
		$name = strtolower($name);
		return isset(self::$instances[$name]) ? self::$instances[$name] : self::getItem($name);
	}
    
	/**
	 * Register Fs_Item as named path.
	 * 
	 * Changing predefined paths:
     *   root   chroot()
     *   home   setenv(HOME)
     *   cwd    chdir() 
	 * 
	 * @param string  $name
	 * @param Fs_Item $file
	 */
	public static function setPath($name, $file)
	{
		if (!($file instanceof Fs_Item)) $file = self::get($file);
		$name = strtolower($name);
		
		switch ($name) {
			case 'root': if (!chroot($file)) throw new Exception("Failed to change root to '$file'."); break;
			case 'home': if (!putenv("HOME=$file")) throw new Exception("Failed to change home dir to '$file'."); break;
    		case 'cwd':  if (!chdir($file)) throw new Exception("Failed to change dir to '$file'."); return;
    		
    		case 'document_root':
    		case 'script':        throw new Exception("Unable to set $name to '$file'; Property is read only.");
		}
		
		self::$instances[$name] = $file; 
	}
	
	
	/**
	 * Resolves references to '/./', '/../' and extra '/' characters in the input path.
	 * Symlinks are not resolved and the file doesn't need to exist.
	 *
	 * @param string $path
	 * @param string $basepath  Basepath for relative paths, defaults to CWD.
	 * @return string
	 */
	public static function canonicalize($path, $basepath=null)
	{
		if ($path instanceof Fs_Item) return (string)$path; // Already canonicalized at construction.
		if (!isset($basepath)) $basepath = getcwd();
		
		if (!preg_match('%(?:/|^)(?:\.\.?|~)(?:/|$)%', $path)) {
			// Most cases, so fast solution
			return preg_replace(array('%(?<!^)/+$%', '%/{2,}%'), array('', '/'), ($path[0] == '/' ? '' : "$basepath/") . $path);
		}

		if ($path == '~' || strncmp('~/', $path, 2) == 0) {
			$path = getenv('HOME') . substr($path, 1);
		} elseif ($path[0] != '/') {
			$path = "$basepath/$path";
		}
        
		$canpath = "";
		foreach (preg_split('~(?<!\\\\)/+~', rtrim($path, '/')) as $part) {
			switch ($part) {
				case '':
				case '.':	break;
            	case '..':	$canpath = dirname($canpath); break;
				default:	$canpath .= $canpath == '/' ? $part : "/$part";	
			}
		}

		return $canpath;
	}
	
	/**
	 * Get the type from the octal mode.
	 * 
	 * @param int $mode
	 * @return string
	 */
	public static function mode2type($mode)
	{
		if (is_string($mode)) $mode = octdec($mode);
		$type = $mode & 0170000; // File encoding Bits
		return isset(self::$modetypes[$type]) ? substr(self::$modetypes[$type], 2) : 'unknown';
	}

	/**
	 * Convert octal mode to permissions in human readable format.
	 * 
	 * @param int $mode
	 * @return string
	 */
	public static function mode2perms($mode)
	{
		if (is_string($mode)) $mode = octdec($mode);
		
		$perms = (isset(self::$modetypes[$mode & 0170000]) ? self::$modetypes[$mode & 0170000][0] : 'u') .
		 (($mode & 0400) ? 'r' : '-') . (($mode & 0200) ? 'w' : '-') . (($mode & 0100) ? (($mode & 04000) ? 's' : 'x') : (($mode & 04000) ? 'S' : '-')) .
		 (($mode & 0040) ? 'r' : '-') . (($mode & 0020) ? 'w' : '-') . (($mode & 0010) ? (($mode & 02000) ? 's' : 'x') : (($mode & 02000) ? 'S' : '-')) .
		 (($mode & 0004) ? 'r' : '-') . (($mode & 0002) ? 'w' : '-') . (($mode & 0001) ? (($mode & 01000) ? 't' : 'x') : (($mode & 01000) ? 'T' : '-'));
 		
		return $perms;
	}	
	
	/**
	 * Convert octal mode to umask.
	 * 
	 * @param int $mode
	 * @return int
	 */
	public static function mode2umask($mode)
	{
		return ~$mode & 0777;
	}
	
	
    /**
     * Get an Fs interface for a directory.
     * 
     * @param string $path
     * @return Fs_Dir
     */
    public static function dir($path)
    {
    	$class = self::$types['dir'];
        return new $class($path);
    }

    /**
     * Get an Fs interface for a regular file.
     * 
     * @param string $path
     * @return Fs_File
     */
    public static function file($path)
    {
    	$class = self::$types['file'];
        return new $class($path);
    }

    /**
     * Create a symlink and return the Fs interface.
     * 
     * @param string $target
     * @param string $link
     * @param int    $flags   Fs::% options as binary set
     * @return Fs_Item
     */
    public static function symlink($target, $link, $flags=self::RECURSIVE)
    {
    	if (is_link($link) && $flags & self::OVERWRITE) unlink($link);
    	
    	if (!@symlink($target, $link)) {
    		$err = error_get_last();
    		throw new Fs_Exception("Failed to create symlink '$link' to '$target'; {$err['message']}");
    	}
    	
        return Fs::get($link);
    }

    /**
     * Get an Fs interface for an item of the filesystem.
     * 
     * @param string $path
     * @param string $default  Type if file does not exist.
     * @return Fs_Item
     * @throws Fs_Exception is file doesn't exits and $default is not set.
     */
    public static function get($path, $default=null)
    {
    	if (is_link($path)) {
    		$type = 'link/' . (file_exists($path) ? filetype(realpath($path)) : '');
    	} elseif (file_exists($path)) {
    		$type = filetype($path);
    	} else {
    		if (!isset($default)) throw new Fs_Exception("File '$path' does not exist");
    		$type = $default;
    	}
    	
    	$class = self::$types[$type];
    	return new $class($path);
    }
    
 	/**
 	 * Find files matching a pattern.
 	 * @see http://www.php.net/glob
 	 * 
 	 * @param string $pattern
 	 * @param int    $flags    GLOB_% options as binary set
 	 * @return Fs_Item[]
 	 */
 	public static function glob($pattern, $flags=0)
 	{
 		$files = array();
 		foreach (glob($pattern, $flags) as $filename) $files[] = self::get($filename);
 		return $files;
 	}
 	
 	/**
 	 * Find executable file in enviroment path (as `which` command)
 	 * 
 	 * @param string $file
 	 * @return Fs_File
 	 */
 	public static function bin($file)
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
