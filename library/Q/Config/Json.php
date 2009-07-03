<?php
namespace Q;

require_once 'Q/Config/Files.php';
require_once 'Q/PHPParser.php';

/**
 * Load and parse .json config files from a directory.
 *
 * Options:
 *   caching       Enable caching: 'off', 'on' (Cache), 'mem' (memory only), Cache object or Cache_Lite object. Default is 'mem'.
 *   caching_id    Required if caching == 'on'
 *   path | 0      Filename or directory to configuration files
 *   recursive     Use subdirectory as group, with files as subgroups
 *   preparse      Preparse the config file (possible value 'php')
 *   parameters    Variables when preparsing config file, string as "key=value, ..." or array(key=>value, ...)
 * 
 * @package Config
 */
class Config_Json extends Config_Files
{
	/**
	 * File extension.
	 * Change this value in child classes.
	 * 
	 * @var string
	 */
	protected $_ext="json";

	/**
	 * Load a config file or dir (no caching)
	 * 
	 * @param string $path
	 * @param string $group
	 * @return array
	 */
	protected function loadFile($path, $group=null)
	{
		$file = isset($group) ? "$path/" . $this->groupName($group) . "." . $this->_ext : $path;
        $settings = array();
		
		if (is_file($file)) {
			if (!empty($this->_options['php'])) $json = PHPParser::load($file, $this->preparseParams);
		      else $json = file_get_contents($file);
			
			if (!empty($json)) $settings = json_decode($json, true);
			if (!isset($settings)) trigger_error("Failed to parse json file '$file'.", E_USER_WARNING);
			
		} elseif (!empty($this->_options['recursive']) && is_dir("$path/$group")) {
			$settings = $this->loadDir("$path/$group");
		}
		
		return $settings;
	}
}
?>