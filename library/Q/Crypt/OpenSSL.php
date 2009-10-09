<?php
namespace Q;

require_once 'Q/Crypt.php';

/**
 * Crypt using password based encryption with OpenSSL.
 * 
 * This class doesn't do seal/open (no public/private keys). The same secret phrase needs to be used for both
 * encryption and decryption.
 * 
 * @package Crypt
 */
class Crypt_OpenSSL extends Crypt implements Decrypt
{
    /**
     * Encryption method.
     * @var string
     */
    public $method = 'AES256';
    
	/**
	 * Class constructor.
	 * 
	 * @param array $options  Values for public properties
	 */
	public function __construct($options=array())
	{
		if (!extension_loaded('openssl')) throw new Exception("OpenSSL extension is not available.");
	    
	    $options = (array)$options;
	    
	    if (isset($options[0])) {
	        $this->method = $options[0];
	        unset($options[0]);
	    }
	    
	    parent::__construct($options);
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
    	if ($value instanceof Fs_File) $value = $value->getContents();
    	
        if (empty($this->secret)) trigger_error("Secret key is not set for OpenSSL password encryption. This is not secure.", E_USER_NOTICE);
        return openssl_encrypt($value, $this->method, $this->secret);
    }
    
    /**
     * Decrypt encrypted value.
     * 
     * @return string
     */
    public function decrypt($value)
    {
    	if ($value instanceof Fs_File) $value = $value->getContents();
    	
        $ret = openssl_decrypt($value, $this->method, $this->secret);
        if ($ret === false) throw new Decrypt_Exception("Failed to decrypt value with $this->method using openssl.");
        return $ret;
    }
}
