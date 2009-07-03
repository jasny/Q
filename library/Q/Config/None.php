<?php
namespace Q;

require_once 'Q/Config.php';

/**
 * Set configuration settings, no loading.
 *
 * @package Config
 */
class Config_None extends Config
{
	/**
	 * Load a config file or dir and save it to cache
	 * 
	 * @param string $key
	 * @return array
	 */
	protected function loadToCache($key=null)
	{
		return;
	}
}

?>
