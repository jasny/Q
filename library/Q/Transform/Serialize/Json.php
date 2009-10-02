<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Unserialize/Json.php';

/**
 * Serialize data to a json string.
 *
 * @package Transform
 */
class Transform_Serialize_Json extends Transform
{
	
	/**
	 * Get a transformer that does the reverse action.
	 * 
	 * @param Transformer $chain
	 * @return Transformer
	 */
	public function getReverse($chain=null)
	{
		$ob = new Transform_Unserialize_Json($this);
		if ($chain) $ob->chainInput($chain);
		return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
	}
		
    /**
     * Serialize data and return result.
     *
     * @param mixed $data
     * @return string
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
        $data = json_encode($data);
        if (!isset($data)) throw new Transform_Exception('Failed to serialize ' . gettype($data) . ' to json string.');
        
        return $data;
    }
}
