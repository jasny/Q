<?php
namespace Q;

require_once 'Q/Config/Files.php';
require_once 'Q/Transform.php';

/**
 * Load and parse .ini config files from a directory.
 *
 * Options:
 *   caching       Enable caching: 'off', 'on' (Cache), 'mem' (memory only), Cache object or Cache_Lite object. Default is 'mem'.
 *   caching_id    Required if caching == 'on'
 *   path | 0      Filename or directory to configuration files
 *   recursive     Use subdirectory as group, with files as subgroups
 *
 * @package Config
 */
class Config_Ini extends Config_Files
{
	/**
	 * File extension.
	 * Change this value in child classes.
	 *
	 * @var string
	 */
	protected $_ext="ini";


	/**
	 * Load a config file or dir
	 *
	 * @param Fs_Item $file_object
	 * @param string $group
	 * @return array
	 */
	protected function loadFile($file_object, $group=null)
	{
		if ($file_object instanceof Fs_File) return Transform::with('unserialize-ini')->process($file_object->path());
		  elseif (!empty($this->_options['recursive']) && $file_object instanceof Fs_Dir) return $this->loadDir($file_object);

		return array();
	}
    
}