<?php
namespace Q;

require_once 'Q/Config/Files.php';
require_once 'Q/Transform.php';
/**
 * Load and parse YAML config files.
 * Uses the syck extension.
 * 
 * Options:
 *   caching       Enable caching: 'off', 'on' (Cache) or 'mem' (memory only). Default is 'mem'.
 *   caching_id    Required if caching == 'on'
 *   path | 0      Filename or directory to configuration files
 *   recursive     Use subdirectory as group, with files as subgroups
 *   preparse      Preparse the config file (possible value 'php')
 *   parameters    Variables when preparsing config file, string as "key=value, ..." or array(key=>value, ...)
 * 
 * @package Config
 */
class Config_Yaml extends Config_Files
{
	/**
	 * File extension.
	 * @var string
	 */
	protected $_ext="yaml";
	
	
	/**
	 * Class constructor
	 * 
	 * @param  array  $options
	 */
	function __construct($options=array())
	{
		parent::__construct($options);
	}

	/**
	 * Load a config file or dir (no caching)
	 * 
	 * @param Fs_Item $file_object
	 * @param string $group
	 * @return array
	 */
	protected function loadFile($file_object, $group=null)
	{

		if ($file_object instanceof Fs_File) {
			return Transform::with('unserialize-yaml')->process($file_object->path());
		}elseif($file_object instanceof Fs_Dir){
            return $this->loadDir($file_object);
		}

		return array();
	}
}
