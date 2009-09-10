<?php
namespace Q;

/**
 * Interface to indicate that class can be used for persistant storing of data
 */
interface Cacher
{
	/**
	 * Set the next cache handler in the chain.
	 *
	 * @param Cacher $cache  Cache object, DNS string or options
	 */
	public function chain($cache);
	
	
	/**
	 * Test if cache is available.
	 * 
	 * @param string $id   Cache id
	 * @param int    $opt  Cache::% options
	 * @return boolean 
	 */
	public function has($id, $opt=0);
	
	/**
	 * Test if a cache is available and (if yes) return it.
	 * 
	 * @param string $id  Cache id
	 * @return mixed
	 */
	public function get($id, $opt=0);
	
	/**
	 * Save data into cache.
	 * 
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 * @param int    $opt   Cache::% options
	 */
	public function set($id, $data, $opt=0);

	/**
	 * Alias of Q/Cache->set().
	 *
	 * @param string $id    Cache id
	 * @param mixed  $data  Data to put in the cache
	 */
	public function save($id, $data, $opt=0);
	
	/**
	 * Remove data from cache.
	 * 
	 * @param string  $id   Cache id
	 * @param int     $opt  Cache::% options
	 */
	public function remove($id, $opt=0);
	
	/**
	 * Remove old/all data from cache.
	 * 
	 * @param int $opt  Cache::% options
	 */
	public function clean($opt=0);
}
