<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Serialize/Json.php';

/**
 * Unserialize a json string.
 *
 * @package Transform
 */
class Transform_Unserialize_Json extends Transform
{
	/**
	 * Return associated array instead of value object
	 * @var boolean
	 */
	public $assoc = true;
		
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Serialize_Json($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
	
    /**
     * Transform data and return the result.
     *
     * @param string $data  Json string
     * @return array
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
    	if ($data instanceof Fs_Node) $data = $data->getContents();
          else $data = (string)$data;
        
        $data = json_decode($data, $this->assoc);
        if (!isset($data)) {
			switch (json_last_error()) {
				case JSON_ERROR_DEPTH: throw new Transform_Exception('Failed to unserialize json; The maximum stack depth has been exceeded.');
				case JSON_ERROR_CTRL_CHAR: throw new Transform_Exception('Failed to unserialize json; Control character error, possibly incorrectly encoded.');
				case JSON_ERROR_SYNTAX: throw new Transform_Exception('Failed to unserialize json; Invalid json syntax.');
			}
        }
        
        return $data;
    }
}
