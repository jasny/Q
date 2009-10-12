<?php
namespace Q;

require_once 'Q/Config.php';
require_once 'Q/Transform.php';

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
class Config_File extends \ArrayObject
{
	/**
	 * Parameters voor preparsing (might be PHP)
	 * @var array
	 */
	public $preparseParams;

    /**
     * Driver in use
     */
    protected $_driver;

    /**
	 * File extension.
	 * Change this value in child classes.
	 *
	 * @var string
	 */
	protected $_ext;

    /**
     * All cached values
     *
     * @var array
     */
    protected $_settings = array();

    /**
     * Flag to specify that everything is loaded
     * @var boolean
     */
    protected $_loadedAll=false;

    /**
     * Object options
     * @var array
     */
    protected $_options;
    
    /**
     * File path
     * @Fs_Node
     */
    protected $_path;
    
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
            unset($options[0]);
        }
        $this->_path = $options['path'] instanceof Fs_File ? $options['path'] : Fs::get($options['path']);
        if (!($this->_path instanceof Fs_File)) throw new Exception("Unable to load files for config: File '" . $options['path'] . "' does not exist or is not accessable (check permissions)");
        
        $driver = array_key_exists('driver', $options) ? $options['driver'] : null;
        if (!isset($driver) && !in_array($driver, Config::$drivers) && in_array(pathinfo($this->_path, PATHINFO_EXTENSION), Config::$drivers)) {
            $driver = pathinfo((string)$this->_path, PATHINFO_EXTENSION);
            
        }
        if (!in_array($driver, Config::$drivers)) throw new Exception("Unable to create Config object: Unknown driver '$driver'");
        $this->_driver = $driver;
        
        if (empty($this->_settings)) $this->_settings = array();
        // Save options
        $this->_options = $options;
        
        $this->loadToCache();
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
		$slashpos = strrchr((string)$this->_path, '/');
		$rootNode = $slashpos ? substr((string)$slashpos, 1) : (string)$this->_path;
        $extpos = strrchr((string)$rootNode, '.');
		$rootNode = substr($rootNode, 0, $extpos ? -strlen($extpos) : strlen($rootNode));
		$this->_settings = array($rootNode => Transform::from($this->_driver, $this->_options)->process($this->_path));
        $this->_loadedAll = true;
	}

	/**
	 * Load a config file or dir (no caching)
	 *
	 * @param Fs_Item $file_object
	 * @param string $key   May be group or filename
	 * @return array
	 */
	protected function loadFile($file_object, $group=null){}

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
    	return array_key_exists($this->_settings, $offset);
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
    	$this->_settings[$offset] = $value;
	}
	
	/**
	 * Get an offset
	 * @param $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
    	return isset($this->_settings[$offset]) ? $this->_settings[$offset] : null;
	}
	
	/**
	 * Unset an offset
	 * @param $offset
	 * @return unknown_type
	 */
	public function offsetUnset($offset)
	{
    	unset($this->_settings[$offset]);
	}	

}

