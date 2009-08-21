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
     * Cached data
     * @var array
     */
    protected $cache=array();
    
    
    /**
	 * Test if data exists
	 * 
	 * @param string $id   Cache id
	 * @param int    $opt  Not used
	 * @return boolean
	 */
	public function doHas($id, $opt=0)
	{
		return array_key_exists($id, $this->cache);
	}
	
    /**
	 * Get data from cache (if exists)
	 * 
	 * @param string $id   Cache id
	 * @param int    $opt  Not used
	 * @return mixed
	 */
	public function doGet($id, $opt=0)
	{
		return isset($this->cache[$id]) ? $this->cache[$id] : null;
	}
	
	/**
	 * Save data into cache
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 * @param int    $opt   Not used
	 */
	public function doSet($id, $data, $opt=0)
	{
		if (!empty($this->options['overwrite']) || !isset($this->cache[$id])) $this->cache[$id] = $data;
	}

	/**
	 * Remove data from cache
	 * 
	 * @param string $id   Cache id
	 * @param int    $opt  Not used
	 */
	public function doRemove($id, $opt=0)
	{
		unset($this->cache[$id]);
	}
	
	/**
	 * Remove old/all data from cache
	 * 
	 * @param int $opt  Not used
	 */
	public function doClean($opt=0)
	{
		if ($opt & Cache::ALL) $this->cache = array();
	}
}
