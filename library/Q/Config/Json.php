<?php
namespace Q;

require_once 'Q/Config/Files.php';

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
	 * @param Fs_Item $file_object
	 * @param string $group
	 * @return array
	 */
	protected function loadFile($file_object, $group=null)
	{
        $settings = array();
		if ($file_object instanceof Fs_File) {
            $settings = Transform::with('unserialize-json')->process($file_object->path());
			if (!isset($settings)) trigger_error("Failed to parse json file '$file'.", E_USER_WARNING);
		} elseif (!empty($this->_options['recursive']) && $file_object instanceof Fs_Dir) {
			$settings = $this->loadDir($file_object);
		}

		return $settings;
	}
}
