<?php
namespace Q;

require_once 'Q/Crypt.php';
require_once 'Q/Decrypt.php';

/**
 * Encrypt using mcrypt.
 * 
 * @package Crypt
 */
class Crypt_MCrypt extends Crypt implements Decrypt 
{
	/**
	 * Type of encryption
	 * @var string
	 */
	public $method;
	
	/**
	 * Class constructor.
	 * 
	 * @param string $method
	 */
	public function __construct($options)
	{
		if (!extension_loaded('mcrypt')) throw new Exception("MCrypt extension is not available.");
		
	    $options = (array)$options;
	    
	    if (isset($options[0])) {
	        $this->method = $options[0];
	        unset($options[0]);
	    }
	    
	    parent::__construct($options);
	    if (empty($this->method)) throw new Exception("Encryption algoritm not specified.");
	}
	
	/**
	 * Encrypt value.
	 *
	 * @param string $value
	 * @param string $salt   Not used
	 * @return string
	 */
	public function encrypt($value, $salt=null)
	{
		return mcrypt_encrypt($this->method, $this->secret, $value, MCRYPT_MODE_ECB);
	}
	
	/**
	 * Decrypt encrypted value.
	 *
	 * @param string $hash
	 * @return string|false
	 */
    public function decrypt($hash)
    {
        $value = mcrypt_decrypt($this->method, $this->secret, $value, MCRYPT_MODE_ECB);
        return !empty($value) ? $value : false;
    }
}
