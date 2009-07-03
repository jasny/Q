<?php
namespace Q;

require_once 'Q/Config/Files.php';
require_once 'Q/PHPParser.php';

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
		if (!extension_loaded('syck')) throw new Exception("Unable to parse YAML files: The syck extension is not loaded.");
		parent::__construct($options);
	}

	/**
	 * Load a config file or dir (no caching)
	 * 
	 * @param  string  $path
	 * @param  string  $group
	 * @return array
	 */
	protected function loadFile($path, $group=null)
	{
		$file = isset($group) ? "$path/" . $this->groupName($group) . "." . $this->_ext : $path;
		
		if (is_file($file)) {
    		if (!empty($this->_options['php'])) $yaml = PHPParser::load($file, $this->preparseParams);
    		  else $yaml = file_get_contents($file);
    
    		return (array)syck_load($yaml);
		} elseif (!empty($this->_options['recursive']) && is_dir("$path/$group")) {
			return $this->loadDir("$path/$group");
		}
		
		return array();
	}

	
	/**
	 * Searialize setting as YAML config.
	 * The yaml config will be nicely formatted, contrary to syck_dump. 
	 *
	 * @param array $settings
	 * @param int   $level     Indent level
	 * @return string
	 */
    static public function serialize(array $settings, $level=0)
    {
        if (empty($settings)) return '';
        
        $maxlen = 0;
        foreach (array_keys($settings) as $key) {
            $maxlen = max($maxlen, is_int($key) ? 1 : (preg_match('/[^\w-\.]/', $key) ? strlen(addcslashes($key, "'")) + 2 : strlen($key)));
        }
        
        $indent = str_repeat('  ', $level);
        $yaml = "";
        foreach ($settings as $key=>$value) {
            if ($value === null) continue;
            
            if (is_int($key)) $key = '-';
              elseif (preg_match('/[^\w-\.]/', $key)) $key = "'" . addcslashes($key, "'") . "'";
            
            if (is_array($value)) {
                $yaml .=  str_repeat('  ', $level) . "$key:\n" . Config_Yaml::serialize($value, $level+1) . "\n";
            } else {
                if (is_string($value) && ($value === '' || preg_match('/[^\w-\.]/', $value))) $value = '"' . addcslashes($value, '"') . '"';
                $yaml .=  $indent . str_pad($key . ':', $maxlen+1, ' ', STR_PAD_RIGHT) . " $value\n";
            }
        }
        
        return preg_replace(array('/\n{3,}/', '/\n\n$/'), array("\n\n", "\n"), $yaml);
    }	
}
?>