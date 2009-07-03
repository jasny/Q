<?php
namespace Q;

require_once 'Q/Config/Files.php';

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
	 * @param string $path
	 * @param string $group
	 * @return array
	 */
	protected function loadFile($path, $group=null)
	{
		$file = isset($group) ? "$path/" . $this->groupName($group) . "." . $this->_ext : $path;
		
		if (is_file($file)) return parse_ini_file($file, true);
		  elseif (!empty($this->_options['recursive']) && is_dir("$path/$group")) return $this->loadDir("$path/$group");
		
		return array();
	}	
}