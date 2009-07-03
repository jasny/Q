<?
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
	 * @return mixed
	 */
	protected function doGet($id)
	{
		$data = apc_fetch("cache:{$this->options['app']}.$id}");
		return $data !== false ? $data : null;
	}
	
	/**
	 * Save data into cache
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 */
	protected function doSave($id, $data)
	{
	    $fn = $this->options['overwrite'] ? 'apc_store' : 'apc_add';
		$fn("cache:{$this->options['app']}.{$id}", $data, $this->options['lifetime']);
	}

	/**
	 * Remove data from cache
	 * 
	 * @param string $id  Cache id
	 */
	protected function doRemove($id)
	{
		apc_delete("cache:{$this->options['app']}.{$id}");
	}
	
	/**
	 * Remove old/all data from cache
	 * 
	 * @param boolean $all  Remove all data, don't check age
	 */
	protected function doClean($all=false)
	{
		if (!$all) return;
		
		$key = "cache:{$this->options['app']}";
		$keylen = strlen($key);
		
		$cache_info = apc_cache_info("user");
		foreach ($cache_info['cache_list'] as $info) {
			if (substr_compare($info['info'], $key, 0, $keylen) == 0) apc_delete($info['info']);
		}
	}
}
?>