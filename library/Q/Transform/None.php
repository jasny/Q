<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Transform.php';

/**
 * Stub transformer, does nothing except waste time.
 *
 * @package Transform
 */
class Transform_None extends Transform
{
	/**
	 * Get a transformer that does the reverse action.
	 * 
	 * @param Transformer $chain
	 * @return Transformer
	 */
	public function getReverse($chain=null)
	{
		$ob = clone $this;
		$ob->chainInput($chain);
		return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
	}
	
    /**
     * Returns data without any transformation.
     *
     * @param mixed $data
     * @return mixed
     */
    public function process($data)
    {
        return $data;
    }
}
