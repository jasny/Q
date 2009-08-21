<?php
namespace Q;

require_once 'Q/Cache.php';

/**
 * Light weight cache system to save variables on file.
 * 
 * Options:
 *   app             Key to differentiate applications
 *   path | 0        Path of cache files
 *   lifetime        Time until cache expires (seconds).
 *	 gc_probability  Probability the garbage collector will clean up old files
 *	 serialize       Serialize function to use, set to NULL not to serialize.
 *	 unserialize     Reverse function of serialize. 
 *   ext             File extension 
 *  
 * @package Cache
 */
class Cache_File extends Cache
{
	/**
	 * Class constructor
	 * 
	 * @param $options  Configuration options
	 */	
	public function __construct($options=array())
	{
	    if (empty($options['path'])) {
	        if (!empty($options['cachedir'])) $options['path'] = $options['cachedir'];
	          elseif (!empty($options[0])) $options['path'] = $options[0];
	          else $options['path'] = (function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : '/tmp') . '/cache.' . $this->options['key'];
	    }
	    $options['path'] = preg_replace('/\{\$(.*?)\}/e', "isset(\$_SERVER['\$1']) ? \$_SERVER['\$1'] : \$_ENV['\$1']", $options['path']);
		if (!is_dir($options['path']) && (file_exists($options['path']) || !mkdir($options['path'], 0770, true))) throw new Exception("Unable to create Cache of type 'file'. Directory '{$this->options['cachedir']}' does not exists and could not be created.");

		parent::__construct($options);
		
		if ($this->options['gc_probability'] >= 1 || mt_rand(1, 1 / $this->options['gc_probability']) == 1) $this->clean(); 
	}
	
	
	/**
	 * Get a filepath for a cache id
	 *
	 * @param string $id  Cache id
	 * @return string
	 */
	protected function getPath($id)
	{
	    return $this->options['path'] . '/' . preg_replace('~[?<>\\\\:*|"]~', '', $id) . (isset($this->options['ext']) ? '.' . preg_replace('/\W/', $this->options['ext']) : '');
	}
	
	/**
	 * Return data from cache or false if it doens't exist.
	 * 
	 * @param string $id  Cache id
	 * @param int    $opt
	 * @return mixed
	 */
	protected function doHas($id, $opt=0)
	{
		$file = $this->getPath($id);
		return file_exists($file);
	}
		
	/**
	 * Return data from cache or false if it doens't exist.
	 * 
	 * @param string $id  Cache id
	 * @param int    $opt
	 * @return mixed
	 */
	protected function doGet($id, $opt=0)
	{
		$file = $this->getPath($id);
		if (!file_exists($file)) return null;
		
		$fp = fopen($file, "r");
	    if (!flock($fp, LOCK_SH)) return null;
	    
	    $data = fread($fp, filesize($file));
	    flock($fp, LOCK_UN);
		
		if (empty($this->options['unserialize'])) return $data;
		
		$fn = $this->options['unserialize'];
		return $fn($data);
	}
	
	/**
	 * Save data into cache
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 * @param int    $opt
	 */
	protected function doSet($id, $data, $opt=0)
	{
		$file = $this->getPath($id);
		if (!$this->options['overwrite'] && file_exists($file) && filemtime($file) < time()-$this->options['lifetime']) return;

		$dir = dirname($file);
		if (!is_dir($dir)) {
		    if (file_exists($dir)) {
		        trigger_error("Can't save cache to '$file'. Path '$dir' is not a directory.", E_USER_WARNING);
		        return;
		    }
		    mkdir($dir, 0770, true);
		}
		
		if (!empty($this->options['serialize'])) {
		    $fn = $this->options['serialize'];
		    $data = $fn($data);
		} elseif (!is_scalar($data)) {
		    throw new Exception("Can't save " . gettype($data) . " without serializing ('raw' option).");
		}
		
		$fp = fopen($file, "w");
	    if (!flock($fp, LOCK_EX)) return;
	    
	    fwrite($fp, $data);
	    flock($fp, LOCK_UN);
	}
    
	/**
	 * Remove data from cache
	 * 
	 * @param string $id  Cache id
	 * @param int    $opt
	 */
	protected function doRemove($id, $opt=0)
	{
		$file = $this->getPath($id);
		if (file_exists($file)) unlink($file);
	}
	
	/**
	 * Remove old/all data from cache.
	 * 
	 * @param int $opt  Cache::% options
	 */
	protected function doClean($opt=0)
	{
		$files = glob($this->options['path'] . '/*' . (isset($this->options['ext']) ? '.' . $this->options['ext'] : ''));
		
		if ($opt & Cache::ALL) {
		    array_map('unlink', $files);
		} else {
		    $old = time() - $this->options['lifetime'];
    		foreach ($files as $file) {
    			if (filemtime($file) < $old) unlink($file);
    		}
		}
	}
}
