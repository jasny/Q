<?php
namespace Q;

require_once 'Q/Config/Files.php';

/**
 * Load and parse XML config files.
 *
 * XML doesn't map well to the PHP data structure. This makes it relatively slow and a bit quirky  I recommend using
 *  Yaml or Json instead.
 * 
 * Options:
 *   caching       Enable caching: 'off', 'on' (Cache) or 'mem' (memory only). Default is 'mem'.
 *   caching_id    Required if caching == 'on'
 *   path | 0      Filename or directory to configuration files
 *   recursive     Use subdirectory as group, with files as subgroups
 *   map           Map values as array(tagname=>mapping) using '@att', 'node', '"string"' or combine as '"string".@node'
 *   mapkey        Map keys (tagname '*' means all nodes, but xpath is *not* supported)
 *
 * @package Config
 */
class Config_XML extends Config_Files
{
	/**
	 * File extension.
	 * @var string
	 */
	protected $_ext="xml";


	/**
	 * Load a config file or dir (no caching).
	 *
	 * @param  Fs_Item  $file_object
	 * @param  string  $group
	 * @return array
	 */
	protected function loadFile($file_object, $group=null)
	{
        if ($file_object instanceof Fs_File) {
            return Transform::with('unserialize-xml')->process($file_object->path());
        }elseif (!empty($this->_options['recursive']) && $file_object instanceof Fs_Dir) {
            return $this->loadDir($file_object);
        }

		return array();
	}
}
