<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Serialize/XML.php';

/**
 * Transform a xml into an array
 * 
 * XML doesn't map well to the PHP data structure. This makes it relatively slow and a bit quirky  I recommend using
 *  Yaml or Json instead.
 *  
 * Options:
 *   map           Map values as array(tagname=>mapping) using '@att', 'node', '"string"' or combine as '"string".@node'
 *   mapkey        Map keys (tagname '*' means all nodes, but xpath is *not* supported)
 *   
 * @package Transform
 */
class Transform_Unserialize_XML extends Transform
{	
    /**
     * Default extension for file with unserialized data.
     * @var string
     */
    public $ext = 'xml';
    
    /**
     * Object options
     * @var array
     */
    protected $_options;
	
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Serialize_XML($this);
        if ($chain) $ob->chainInput($chain);
        if (isset($this->mapkey)) throw new Transform_Exception("Unable to get the reverse transformer: mapkey is not supported by Transform_Serialize_XML");
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
	
    /**
     * Start the transformation and return the result.
     *
     * @param string $data  The xml that will be transformed into an array
     * @return array
     * @todo: take care of duplicate keys
     */
    public function process($data = null)
    {   
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
        if (!is_string($data) && !($data instanceof Fs_Node)) throw new Transform_Exception('Unable to transform XML into Array: Incorect data type');

        
        if ($data instanceof Fs_Node) $sxml = simplexml_load_file((string)$data); 
		  else $sxml = simplexml_load_string($data);
        //keep the root node name of the xml document; needed when reverse action to keep the same structure of the document    
        $this->rootNodeName = dom_import_simplexml($sxml)->tagName;
		  
		 if (isset($this->map)) $this->_options['map'] = $this->map;
         if (isset($this->mapkey)) $this->_options['mapkey'] = $this->mapkey;
		 
		return $this->sxmlExtract($sxml);
		        
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
        	$items = split_set('.', $item, false);
        	
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
                trigger_error("Mapping of config node '" . $node->getName() . "' failed. Attribute '$item' was not found. (1)", E_USER_WARNING);
                return null;
            }
            return (string)$node[$item];
        }

        if (!isset($node->$item)) {
            trigger_error("Mapping of config node '" . $node->getName() . "' failed. Child node '$item' was not found.(2)", E_USER_WARNING);
            return null;
        }
        return $node->$item;
    }
}
