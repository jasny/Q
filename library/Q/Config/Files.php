<?php
namespace Q;

require_once 'Q/Config.php';

/**
 * Load and parse config files from a directory.
 * 
 * @package Config
 */
abstract class Config_Files extends Config
{
	/**
	 * Parameters voor preparsing (might be PHP)
	 * @var array
	 */
	public $preparseParams;

	/**
	 * File extension.
	 * Change this value in child classes.
	 * 
	 * @var string
	 */
	protected $_ext;
	
	
	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	public function __construct($options=array())
	{
		if (!isset($options['path'])) {
		    if (!isset($options[0])) throw new Exception("Unable to load files for config: No option 'path' supplied.");
		    $options['path'] = $options[0];
		}
		
		$options['path'] = preg_replace('/\{\$(.*?)\}/e', "isset(\$_SERVER['\$1']) ? \$_SERVER['\$1'] : \$_ENV['\$1']", $options['path']);
		if (!file_exists($options['path'])) throw new Exception("Unable to load files for config: File/directory '" . $options['path'] . "' does not exist or is not accessable (check permissions)");
		$options['path_is_file'] = !is_dir($options['path']);

		if (isset($options['parameters'])) $this->preparseParams = is_array($options['parameters']) ? $options['parameters'] : split_set_assoc($options['parameters']);
		
		parent::__construct($options);
		if ($options['path_is_file']) $this->loadToCache();
	}

	/**
	 * Return a valid group name
	 * 
	 * @param string $group
	 * @return string
	 */
	function groupName($group)
	{
		if (!isset($group)) return null;
		return preg_replace('/\.' . preg_quote($this->_ext, '/') . '$/i', '', $group);
	}
	
	/**
	 * Load a config file or dir and save it to cache
	 * 
	 * @param string $group
	 * @return array
	 */
	protected function loadToCache($group=null)
	{
		if ($this->_loadedAll) return;
		
		if ($this->_options['path_is_file']) {
			$this->_settings = $this->loadFile($this->_options['path']);
			$this->_loadedAll = true;
		} elseif (empty($group)) {
			$this->loadDir($this->_options['path'], $this->_settings);
			$this->_loadedAll = true;
        } else {
			if (!isset($this->_settings[$group])) $this->_settings[$group] = $this->loadFile($this->_options['path'], $group);
		}
	}
	
	/**
	 * Load a config file or dir (no caching)
	 * 
	 * @param string $path
	 * @param string $key   May be group or filename
	 * @return array
	 */
	abstract protected function loadFile($path, $group=null);
	
	/**
	 * Load all config files in a dir (no caching)
	 * 
	 * @param string $path
	 * @param array  $settings  Settings to append to
	 * @return array
	 */
	protected function loadDir($path, &$settings=null)
	{
		$files = array();
		$dirs = array();
		
		if (empty($this->_options['recursive'])) {
			$files = glob("{$path}/*.{$this->_ext}");
		} else {
			foreach (scandir($path) as $file) {
				if (is_dir($file) && $file !== '.' && $file !== '..') $dirs[] = "$path/$file";
				if (pathinfo($file, PATHINFO_EXTENSION) === $this->_ext) $files[] = "$path/$file"; 
			}
		}
		
		foreach ($files as $file) {
			$group = $this->groupName(basename($file));
			if (!isset($settings[$group])) $settings[$group] = $this->loadFile($path, $group);
		}

		foreach ($dirs as $dir) {
			$group = basename($dir);
			if (!isset($settings[$group])) $settings[$group] = $this->loadDir($dir);
		}
		
		return $settings;
	}
}

?>