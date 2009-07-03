<?php
namespace Q;

require_once 'Q/Crypt.php';
require_once 'Q/Decrypt.php';

/**
 * Encryption class wrapping mcrypt.
 * 
 * @package Crypt
 */
class Crypt_MCrypt extends Crypt implements Decrypt 
{
	/**
	 * Type of encryption
	 * @var string
	 */
	public $cipher;

	/**
	 * Salt, encryption key or passphrase
	 * @var string
	 */
	public $key=null;
	
	
	/**
	 * Class constructor.
	 * 
	 * @param string $cipher
	 * @param string $key    Salt, encryption key or passphrase
	 */
	public function __construct($cipher, $key=null)
	{
		if (!extension_loaded('mcrypt')) throw new Exception("MCrypt extension is not loaded");
		
	    if (strtolower($cipher) == 'mcrypt') list(, $cipher, $key) = func_get_args();
	    if (empty($cipher)) throw new Exception("Encryption algoritm not specified.");
	    
		$this->cipher = $cipher;
		$this->key = $key;
		
		if (!in_array($this->cipher, mcrypt_list_algorithms())) throw new Exception("Unknown MCrypt algorithm '{$this->cipher}'");
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
		return mcrypt_encrypt($this->cipher, $this->key, $value . $this->secret, MCRYPT_MODE_ECB);
	}
	
	/**
	 * Decrypt encrypted value.
	 * Returns NULL if hash is invalid.
	 *
	 * @param string $hash
	 * @return string
	 */
    public function decrypt($hash)
    {
        $value = mcrypt_decrypt($this->cipher, $this->key, $value, MCRYPT_MODE_ECB);
        if (empty($value) || (!empty($this->secret) && (strlen($this->secret) > strlen($value) || substr($this->secret, -1 * strlen($value)) != $value))) return null;
        
        return $value;
    }
}

?>