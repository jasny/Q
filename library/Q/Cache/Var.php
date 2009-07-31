<?php
namespace Q;

require_once 'Q/Cache.php';

/**
 * Cache to local (non-shared, non-persistant) memory.
 *
 * @package Cache
 */
class Cache_Var extends Cache
{
    /**
	 * Get data from cache (if exists)
	 * 
	 * @param string $id  Cache id
	 * @return mixed
	 */
	public function doGet($id)
	{
		return isset($this->cache[$id]) ? $this->cache[$id] : null;
	}
	
	/**
	 * Save data into cache
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 */
	public function doSave($id, $data)
	{
		if ($this->options['overwrite'] || !isset($this->cache[$id])) $this->cache[$id] = $data;
	}

	/**
	 * Remove data from cache
	 * 
	 * @param string $id  Cache id
	 */
	public function doRemove($id)
	{
		unset($this->cache[$id]);
	}
	
	/**
	 * Remove old/all data from cache
	 * 
	 * @param boolean $all  Remove all data, don't check age
	 */
	public function doClean($all=false)
	{
		if ($all) $this->cache = array();
	}
}
