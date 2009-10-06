<?php
namespace Q;

require_once 'Q/Fs/Node.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink with a target that doesn't exist.
 * 
 * @package Fs
 */
class Fs_Symlink_Broken extends Fs_Node implements Fs_Symlink
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (!is_link($path)) throw new Fs_Exception("File '$path' is not a symlink");
		if (file_exists($path)) throw new Fs_Exception("File '$path' is not a broken link, the target is a " . filetype(realpath($path)));
		parent::__construct($path);
	}
	
 	/**
 	 * Create this file.
 	 * Use Fs::PRESERVE to simply return if file already exists
 	 * 
 	 * @param int $mode
 	 * @param int $flags  Fs::% options
 	 * @throws Fs_Exception
 	 */
	public function create($mode=0666, $flags=0)
 	{
 		if ($this->exists() && $flags & Fs::PRESERVE) return;
 		throw new Fs_Exception("Unable to create file: Unable to dereference link '{$this->_path}'");
 	}
 	
 	/**
     * Sets access and modification time of file.
     * @see http://www.php.net/touch
     * 
     * @param int|string|\DateTime $time   Defaults to time()
     * @param int|string|\DateTime $atime  Defaults to $time
     * @param int                  $flags  Fs::% options as binary set
     * @throws Fs_Exception 
     * 
     */
    public function touch($time=null, $atime=null, $flags=0)
    {
        throw new Fs_Exception("Unable to touch file: Unable to dereference link '{$this->_path}'");
    } 	

    /**
     * Returns Fs_Node of canonicalized absolute pathname, resolving symlinks.
     * Unlike the realpath() PHP function, this returns a best-effort for non-existent files.
     * 
     * Use Fs::NO_DEREFERENCE to not dereference.
     * 
     * @param int $flags  Fs::% options
     * @return Fs_Node
     * @throws Fs_Exception if Fs::ALWAYS_FOLLOW is not set
     */
    public function realpath($flags=0)
    {
        if ($flags & Fs::NO_DEREFERENCE) {
            $target = Fs::canonicalize($this->target(), dirname($this->_path));
            if (is_link($target)) return new static($target);

            if (~$flags & Fs::ALWAYS_FOLLOW) throw new Fs_Exception("Unable to resolve realpath of '{$this->_path}': File is a broken symlink.");
            return Fs::unknown($target);
        
        } else {
        	$file = $this->realpathBestEffort($flags);
            if (!$file) throw new Fs_Exception("Unable to resolve realpath of '{$this->_path}': Too many levels of symbolic links.");
            return $file;
        }
    }
}
