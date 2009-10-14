<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Serialize/Yaml.php';

/**
 * Load a yaml file into an array
 *
 * @package Transform
 */
class Transform_Unserialize_Yaml extends Transform
{
    /**
     * Default extension for file with unserialized data.
     * @var string
     */
    public $ext = 'yaml';
    
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Serialize_Yaml($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
	
	/**
     * Transform data and return the result.
     *
     * @param string $data  Yaml string or file
     * @return array
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
    	if ($data instanceof Fs_Node) $data = $data->getContents();
          else $data = (string)$data;
        
        $data = syck_load($data);
          
        return $data;
    }
}
