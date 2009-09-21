<?php
namespace Q;

require_once 'Q/Config.php';
require_once 'Q/Fs.php';

/**
 * Load and parse config files from a directory.
 * 
 * {@example 
 * 
 * 1) 
 * $conf = Config::with('yaml:/etc/myapp');     
 * $conf['abc']['10'] = "hello";
 * $conf['abc']['12'] = "Test";
 * $conf->save();
 * }
 *
 * @package Config
 */
abstract class Config_Files extends Config implements \ArrayAccess
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
     * Configuration variable
     *
     * @var array
     */
    protected $config = array();
	
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

		try {
            $options['file_object'] = Fs::get($options['path']);
        }catch(Fs_Exception $e) {
            throw new Exception("Unable to load files for config: File/directory '" . $options['path'] . "' does not exist or is not accessable (check permissions)");
        }
        $options['path_is_file'] = ($options['file_object'] instanceof Fs_File ? true : false);

		if (isset($options['parameters'])) $this->preparseParams = is_array($options['parameters']) ? $options['parameters'] : split_set(';', $options['parameters']);

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
			$this->_settings = $this->loadFile($this->_options['file_object']);
			$this->_loadedAll = true;
		} elseif (empty($group)) {
			$this->loadDir($this->_options['file_object'], $this->_settings);
			$this->_loadedAll = true;
        } else {
			if (!isset($this->_settings[$group])) $this->_settings[$group] = $this->loadFile($this->_options['file_object'], $group);
		}
	}

	/**
	 * Load a config file or dir (no caching)
	 *
	 * @param Fs_Item $file_object
	 * @param string $key   May be group or filename
	 * @return array
	 */
	abstract protected function loadFile($file_object, $group=null);

	/**
	 * Load all config files in a dir (no caching)
	 *
	 * @param Fs_Dir $dir_object
	 * @param array  $settings  Settings to append to
	 * @return array
	 */
	protected function loadDir($dir_object, &$settings=null)
	{
		$files = array();
		$dirs = array();

        if (!($dir_object instanceof Fs_Dir)) throw new Exception("Unable to load dir. Parameter is not a Fs_Dir");

		if (empty($this->_options['recursive'])) {
            $files = $dir_object->glob("*.{$this->_ext}");
		} else {
            while ($dir_object->valid()) {
                $current = $dir_object->current();
				if ($current instanceof Fs_Dir) $dirs[] = $current;
				if ($current->extension() === $this->_ext) $files[] = $current;
                $dir_object->next();
            }
		}

		foreach ($files as $file) {
            $group = $this->groupName($file->basename());
			if (!isset($settings[$group])) $settings[$group] = $this->loadFile($file, $group);
		}

		foreach ($dirs as $dir) {
            $group = $dir->basename();
			if (!isset($settings[$group])) $settings[$group] = $this->loadDir($dir);
		}

		return $settings;
	}

	/**
	 * Checks if there is a value for the key specified by the offset
	 * @param $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
	{	
    	return array_key_exists($this->config, $offset);
	}
	
	/**
	 * Sets the offset 
	 * @param $offset
	 * @param $value
	 * 
	 * @todo : check if overwrite = true | false ??????
	 */
	public function offsetSet($offset, $value)
	{
    	$this->config[$offset] = $value;
	}
	
	/**
	 * Get an offset
	 * @param $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
    	return isset($this->config[$offset]) ? $this->config[$offset] : null;
	}
	
	/**
	 * Unset an offset
	 * @param $offset
	 * @return unknown_type
	 */
	public function offsetUnset($offset)
	{
    	unset($this->config[$offset]);
	}	

}

