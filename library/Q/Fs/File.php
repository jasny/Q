<?php
namespace Q;

require_once 'Q/Fs/Node.php';

/**
 * Interface of a regular file.
 * 
 * @package Fs
 */
class Fs_File extends Fs_Node
{
	/**
	 * Class constructor.
	 * 
	 * @param string $path
	 */
	public function __construct($path)
	{
		parent::__construct($path);
		
		if (file_exists($path) && !is_file($path)) throw new Fs_Exception("File '$path' is not a regular file, but a " . Fs::typeOfNode($path, Fs::DESCRIPTION)); 
		if (is_link($path) xor $this instanceof Fs_Symlink) throw new Fs_Exception("File '$path' is " . ($this instanceof Fs_Symlink ? 'not ' : '') . "a symlink");
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
		return isset($maxlen) ?
		 @file_get_contents($this->_path, $flags, null, $offset, $maxlen) :
		 @file_get_contents($this->_path, $flags, null, $offset);
		
		if ($ret === false) throw new Fs_Exception("Failed to read file '{$this->_path}'", error_get_last());
		return $ret;
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
		if ($flags & Fs::RECURSIVE) $this->up()->create(0770, Fs::RECURSIVE | Fs::PRESERVE);
		
		$ret = @file_put_contents($this->_path, $data, $flags);
		if ($ret === false) throw new Fs_Exception("Failed to write to file '{$this->_path}'", error_get_last());
		return $ret;
	}
	
	/**
	 * Output contents of the file.
	 * 
	 * @return int
	 */
	public function output()
	{
		readfile($this->_path);
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
		$resource = @fopen($this->_path, $mode);
		if (!$resource) throw new Fs_Exception("Failed to open file '{$this->_path}'", error_get_last());
		return $resource;
	}
	
	
 	/**
 	 * Create this file.
 	 * Use Fs::PRESERVE to simply return if file already exists
 	 * 
 	 * @param int $mode   umask applies
 	 * @param int $flags  Fs::% options
 	 * @throws Fs_Exception if creating the file fails
 	 */
	public function create($mode=0666, $flags=0)
 	{
 		if ($this->exists()) {
 			if ($flags & Fs::PRESERVE) return;
 			throw new Fs_Exception("Unable to create '{$this->_path}': File already exists");
 		}
 		
 		$file = $this->realpath();
		$dir = $file->up();
		if (!$dir->exists()) {
			if (~$flags & Fs::RECURSIVE) throw new Fs_Exception("Unable to touch '{$file->_path}': Directory '{$dir->_path}' does not exist");
			$dir->create($mode | (($mode & 0444) >> 2), $flags);
		}

		if (!@touch($this->_path)) throw new Fs_Exception("Creating '{$file->_path}' failed", error_get_last());		
 		$this->chmod($mode & ~umask());
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
		if (!$this->exists()) throw new Fs_Exception("Unable to execute '{$this->_path}': File does not exist");
		if (!$this->isExecutable()) throw new Fs_Exception("Unable to execute '{$this->_path}': No permission to execute file");
		
		$args = func_get_args();
		foreach ($args as $i=>&$arg){
			if (!isset($arg)) unset($args[$i]);
			  else $arg = escapeshellarg((string)$arg);
		}
		$arglist = join(' ', $args);

		$pipes = array();
		$p = @proc_open($this->_path . (!empty($arglist) ? ' ' . $arglist : ''), array(array('file', '/dev/null', 'r'), array('pipe', 'w'), array('pipe', 'w')), $pipes);
		if (!$p) throw new ExecException("Failed to execute '{$this->_path}'", error_get_last());
		
		$out = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$err = stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		foreach (explode("\n", $err) as $line) {
			if (trim($line) != '') trigger_error("Exec '{$this->_path}': " . preg_replace('~^\s*([\'"]?' . preg_quote($this->_path, '~') . '[\'"]?\s*(\:\s*)?)?~', '', $line), E_USER_NOTICE);
		}
		
		$return_var = proc_close($p);
		if ($return_var != 0) throw new ExecException("Execution of '{$this->_path}' exited with return code $return_var", $return_var, $out, $err);
		
		return $out;
	}
}
