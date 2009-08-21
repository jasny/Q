<?php
namespace Q;

require_once 'Q/Cache.php';

/**
 * Light weight cache system to save variables using APC.
 *
 * Options:
 *   app             Key to differentiate applications
 *	 lifetime        Time until cache expires (seconds).
 *	 memorycaching   Don't read cache source each time.
 *   
 * @package Cache
 */
class Cache_APC extends Cache
{
	/**
	 * Test if a cache is available and (if yes) return it
	 * 
	 * @param string $id  Cache id
	 * @param int    $opt
	 * @return mixed
	 */
	protected function doHas($id, $opt=0)
	{
	    $success = null;
		apc_fetch("cache:{$this->options['app']}.$id}", $success);
		return $success;
	}
	    
	/**
	 * Test if a cache is available and (if yes) return it
	 * 
	 * @param string $id  Cache id
	 * @param int    $opt
	 * @return mixed
	 */
	protected function doGet($id, $opt=0)
	{
	    $success = null;
		$data = apc_fetch("cache:{$this->options['app']}.$id}", $success);
		return $success ? $data : null;
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
	    $fn = $this->options['overwrite'] ? 'apc_store' : 'apc_add';
		$fn("cache:{$this->options['app']}.{$id}", $data, $this->options['lifetime']);
	}

	/**
	 * Remove data from cache
	 * 
	 * @param string $id  Cache id
	 * @param int    $opt
	 */
	protected function doRemove($id, $opt=0)
	{
		apc_delete("cache:{$this->options['app']}.{$id}");
	}
	
	/**
	 * Remove old/all data from cache
	 * 
	 * @param int $opt  Cache::% options
	 */
	protected function doClean($opt=0)
	{
		if (~$opt & Cache::ALL) return;
		
		$key = "cache:{$this->options['app']}.";
		$keylen = strlen($key);
		
		$cache_info = apc_cache_info("user");
		foreach ($cache_info['cache_list'] as $info) {
			if (strncmp($info['info'], $key, $keylen) == 0) apc_delete($info['info']);
		}
	}
}
