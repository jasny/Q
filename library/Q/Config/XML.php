<?php
namespace Q;

require_once 'Q/Config/Files.php';

/**
 * Load and parse XML config files.
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
	 * @param  string  $path
	 * @param  string  $group
	 * @return array
	 */
	protected function loadFile($path, $group=null)
	{
		$file = isset($group) ? "$path/" . $this->groupName($group) . "." . $this->_ext : $path;
		
		if (is_file($file)) {
			$sxml = simplexml_load_file($file);
			return $this->sxmlExtract($sxml);
		} elseif (!empty($this->_options['recursive']) && is_dir("$path/$group")) {
			return $this->loadDir("$path/$group");
		}
		
		return array();
	}
	
    /**
     * Returns a string or an associative and possibly multidimensional array from a SimpleXMLElement.
     *
     * @param SimpleXMLElement $node  Convert a SimpleXMLElement into an array
     * @return array
     */
    protected function sxmlExtract(\SimpleXMLElement $node)
    {
        // Use mapping if exists
        if (isset($this->_options['map']) && ($value = $this->sxmlGetMapped($node)) !== null) {
            $node = $value;
            if (!($node instanceof \SimpleXMLElement)) return (string)$node;
        }
        
        // Object has no children or attributes: it's a string
        if (count($node->attributes()) == 0 && count($node->children()) == 0) return (string)$node;

        $config = array();
        
        // Search for parent node values
        if (count($node->attributes()) > 0) {
            foreach ($node->attributes() as $key=>$value) {
                $value = (string)$value;

                if (array_key_exists($key, $config)) {
                    if (!is_array($config[$key])) $config[$key] = array($config[$key]);
                    $config[$key][] = $value;
                } else {
                    $config[$key] = $value;
                }
            }
        }

        // Search for children
        if (count($node->children()) > 0) {
            foreach ($node->children() as $key=>$value) {
                if (isset($this->_options['mapkey']) && ($mkey = (string)$this->sxmlGetMapped($value, 'key'))) $key = $mkey;
                $value = $this->sxmlExtract($value);

                if (array_key_exists($key, $config)) {
                    if (!is_array($config[$key]) || !array_key_exists(0, $config[$key])) $config[$key] = array($config[$key]);
                    $config[$key][] = $value;
                } else {
                    $config[$key] = $value;
                }
            }
        }

        return $config;
    }
    
    /**
     * Returns a value for a node.
     *
     * @param SimpleXMLElement $node  Convert a SimpleXMLElement into an array
     * @param string           $map   Uses option "map{$map}"
     * @param string           $item  Don't get the item from the map, but use this one
     * @return mixed
     */
    protected function sxmlGetMapped(\SimpleXMLElement $node, $map='', $item=null)
    {
        if (!isset($item)) { 
            if (isset($this->_options["map{$map}"][$node->getName()])) $item = $this->_options["map{$map}"][$node->getName()];
              elseif (isset($this->_options["map{$map}"]['*'])) $item = $this->_options["map{$map}"]['*'];
              else return null;
        } 
        
        if (strpos($item, '.') !== false) {
            $items = split_set($item, '.', false);
            if (count($items) > 1) {
                $value = '';
                foreach ($items as $item) $value .= $this->sxmlGetMapped($node, null, $item);
                return $value;
            }
        }
        
        if (($item[0] == '"' || $item[0] == "'") && preg_match('/^([\'"]).*\\1$/', $item)) {
            return substr($item, 1, -1); 
        }

        if ($item[0] == '@') {
            $item = substr($item, 1);
            if (!isset($node[$item])) {
                trigger_error("Mapping of config node '" . $node->getName() . "' failed. Attribute '$item' was not found.", E_USER_WARNING);
                return null;
            }
            return (string)$node[$item];
        }
        
        if (!isset($node->$item)) {
            trigger_error("Mapping of config node '" . $node->getName() . "' failed. Child node '$item' was not found.", E_USER_WARNING);
            return null;
        }
        return $node->$item;
    }
}
?>