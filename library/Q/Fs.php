<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/Fs/Exception.php';

/**
 * Interface to the filesystem.
 * 
 * @package Fs
 * 
 * @todo Only tested on Linux. Fs might work correctly on other systems.
 */
class Fs
{
	/**
	 * Option; Don't dereference symlinks. 
	 * {@internal Should to have the same value as XATTR_DONTFOLLOW}}
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
	/** Option; Alias of Fs::RECURSIVE. */
	const MKDIR = 0x0200;
	
	/** Option; Preserve mode, ownership and timestamps. */
	const PRESERVE = 0x0400;

	/** Option; Merge directories. */
	const MERGE = 0x0800;
	
	/** Option; Overwrite if item is newer. */
	const UPDATE = 0x1020;

	/** Option; Get description. */
	const DESCRIPTION = 0x8000;
	

	/**
	 * Class for each type.
	 * Make sure the correct Fs_% interfaces are implemented.
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
	
	public static $typedescs = array(
		'block' => 'block device',
		'char' => 'char device',
		'dir' => 'directory',
		'fifo' => 'named pipe',
		'file' => 'file',
		'socket' => 'socket',
		'unknown' => 'unknown filetype',

		'link/' => 'broken symlink',
		'link/block' => 'symlink to a block device',
		'link/char' => 'symlink to a char device',
		'link/dir' => 'symlink to a directory',
		'link/fifo' => 'symlink to a named pipe',
		'link/file' => 'symlink to a file',
		'link/socket' => 'symlink to a socket',
		'link/unknown' => 'symlink to an unknown filetype'		
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
	 * Add functionality by using mixings.
	 * Methods of these classes are callable for an Fs_Node object.
	 * 
	 * @var array
	 */
	public static $mixins = array();
	
	/**
	 * Registered instances
	 * @var string
	 */
	protected static $paths = array(
		'root' => '/'
	);
	
	
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
     * @return Fs_Node
     */
    public static function getPath($name)
    {
    	$name = strtolower($name);
    	if (isset(self::$paths[$name])) return self::get(self::$paths[$name]);
    	
    	switch ($name) {
    		case 'home':           return self::get(getenv('HOME'));
    		case 'cwd':            return self::get(getcwd());
    		
    		case 'document_root':  return self::get($_SERVER['DOCUMENT_ROOT']); break;
    		case 'script':         return self::get($_SERVER['SCRIPT_NAME']); break;
    		
    		default:
    			if (load_class('Q\Config') && !(Config::i() instanceof Mock) && isset(Config::i()->fs[$name])) self::$paths += Config::i()->fs;
    	}
    	
    	if (!isset(self::$paths[$name])) throw new Exception("Fs path '$name' is not registered.");
    	return self::get(self::$paths[$name]);
    }
    
	/**
	 * Register Fs_Node as named path.
	 * 
	 * Changing predefined paths:
     *   root   chroot()
     *   home   setenv(HOME)
     *   cwd    chdir() 
	 * 
	 * @param string         $name
	 * @param string|Fs_Node $file
	 */
	public static function setPath($name, $file)
	{
		if (!($file instanceof Fs_Node)) $file = self::get($file);
		$name = strtolower($name);
		
		switch ($name) {
			case 'root': if (!chroot($file)) throw new Exception("Failed to change root to '$file'."); return;
			case 'home': if (!putenv("HOME=$file")) throw new Exception("Failed to change home dir to '$file'."); break;
    		case 'cwd':  if (!chdir($file)) throw new Exception("Failed to change dir to '$file'."); return;
    		
    		case 'document_root':
    		case 'script':        throw new Exception("Unable to set $name to '$file'; Property is read only.");
		}
		
		self::$paths[$name] = $file; 
	}
	
	/**
	 * Get root of the filesystem
	 * 
	 * @return Fs_Node
	 */
	public static function root()
	{
		return self::dir('/');
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
		if ($path instanceof Fs_Node) return (string)$path; // Already canonicalized at construction.
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
     * Get an Fs interface for a regular file.
     * 
     * @param string $path
     * @return Fs_File
     */
    public static function file($path)
    {
    	$class = self::$types[is_link($path) ? 'link/file' : 'file'];
    	if (!load_class($class)) throw new Exception("Unable to create Fs file: Class '$class' can't be loaded.");
    	
        return new $class($path);
    }
    
	/**
     * Get an Fs interface for a directory.
     * 
     * @param string $path
     * @return Fs_Dir
     */
    public static function dir($path)
    {
    	$class = self::$types[is_link($path) ? 'link/dir' : 'dir'];
    	if (!load_class($class)) throw new Exception("Unable to create Fs dir: Class '$class' can't be loaded.");
    	
        return new $class($path);
    }

    /**
     * Get an Fs interface for a directory.
     * 
     * @param string $path
     * @return Fs_Dir
     */
    public static function block($path)
    {
    	$class = self::$types[is_link($path) ? 'link/block' : 'block'];
    	if (!load_class($class)) throw new Exception("Unable to create Fs block: Class '$class' can't be loaded.");
    	
        return new $class($path);
    }

    /**
     * Get an Fs interface for a regular file.
     * 
     * @param string $path
     * @return Fs_File
     */
    public static function char($path)
    {
    	$class = self::$types[is_link($path) ? 'link/char' : 'char'];
    	if (!load_class($class)) throw new Exception("Unable to create Fs char: Class '$class' can't be loaded.");
    	
        return new $class($path);
    }
    
    /**
     * Get an Fs interface for a directory.
     * 
     * @param string $path
     * @return Fs_Dir
     */
    public static function fifo($path)
    {
    	$class = self::$types[is_link($path) ? 'link/fifo' : 'fifo'];
    	if (!load_class($class)) throw new Exception("Unable to create Fs fifo: Class '$class' can't be loaded.");
    	
        return new $class($path);
    }

    /**
     * Get an Fs interface for a socket file.
     * 
     * @param string $path
     * @return Fs_File
     */
    public static function socket($path)
    {
    	$class = self::$types[is_link($path) ? 'link/socket' : 'socket'];
    	if (!load_class($class)) throw new Exception("Unable to create Fs socket: Class '$class' can't be loaded.");
    	
        return new $class($path);
    }

    /**
     * Create a symlink and return the Fs interface.
     * 
     * @param string $target
     * @param string $link
     * @param int    $flags   Fs::% options as binary set
     * @return Fs_Node
     */
    public static function symlink($target, $link, $flags=self::RECURSIVE)
    {
    	if (is_link($link) && $flags & self::OVERWRITE) unlink($link);
    	
    	if (!@symlink($target, $link)) throw new Fs_Exception("Failed to create symlink '$link' to '$target'", error_get_last());
        return Fs::get($link);
    }
	
    
    /**
     * Check if file exists (or a broken link).
     * 
     * @param string $path
     * @return boolean
     */
    public static function has($path)
    {
    	return file_exists($path) || is_link($path);
    }
        
    /**
     * Get an Fs interface for an item of the filesystem.
     * 
     * @param string $path
     * @param string $default  Type if file does not exist.
     * @return Fs_Node
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
    	if (!load_class($class)) throw new Exception("Unable to create Fs $type: Class '$class' can't be loaded.");
    	
    	return new $class($path);
    }
    
    /**
     * Get the type of a node.
     * 
     * @param Fs_Node|string $file
     * @param int            $flags  Use Fs::ALWAYS_FOLLOW to ignore the fact that the file is a symlink
     * @return string
     */
    public static function typeOfNode($file, $flags=0)
    {
    	if (!($file instanceof Fs_Node)) {
    		$type = (~$flags & Fs::ALWAYS_FOLLOW && is_link($file) ? 'link/' : '') . filetype(realpath($file));
    		return $flags & self::DESCRIPTION ? self::$typedescs[$type] : $type;
    	}
    	
    	if ($file instanceof Fs_File) $type = 'file';
    	  elseif ($file instanceof Fs_Dir) $type = 'dir';
    	  elseif ($file instanceof Fs_Block) $type = 'block';
    	  elseif ($file instanceof Fs_Char) $type = 'char';
    	  elseif ($file instanceof Fs_Fifo) $type = 'fifo';
    	  elseif ($file instanceof Fs_Socket) $type = 'socket';
    	  elseif ($file instanceof Fs_Unknown) $type = 'unknown';
    	  elseif ($file instanceof Fs_Symlink_Broken) $type = '';
    	  else throw new Exception("Unable to determine type of '$file': Class '" . get_class($file) . "' is not any of the known types");
    	 
    	if (~$flags & Fs::ALWAYS_FOLLOW && $file instanceof Fs_Symlink) {
    		$type = "link/$type"; 
    	} elseif ($type == '') {
    		throw new Fs_Exception("Unable to determine type of target of '$file': File is a broken link");
    	}
    	
    	return $flags & self::DESCRIPTION ? self::$typedescs[$type] : $type;
    }
    
 	/**
 	 * Find files matching a pattern.
 	 * @see http://www.php.net/glob
 	 * 
 	 * @param string $pattern
 	 * @param int    $flags    GLOB_% options as binary set
 	 * @return Fs_Node[]
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
