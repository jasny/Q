<?php
namespace Q;

<<<<<<< HEAD:library/Q/Transform/Serialize/PHP.php
require_once 'Q/Transform/Exception.php';
=======
require_once 'Q/Exception.php';
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:library/Q/Transform/Serialize/PHP.php
require_once 'Q/Transform.php';
require_once 'Q/Transform/Unserialize/PHP.php';
require_once 'Q/Fs.php';

/**
 * Execute PHP file and return output as string.
 * 
 * @package Transform
 */
class Transform_Serialize_PHP extends Transform 
{

    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Unserialize_PHP($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
	
	/**
	 * Execute a PHP file and return the output
	 *
	 * @param array  $data Data to transform
	 * @return string
	 */
	public function process($data) 
	{
        if ($this->chainInput) $data = $this->chainInput->process($data);
		        
        return var_give($data, true);
	}
		
}