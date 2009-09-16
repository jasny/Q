<?php
namespace Q;

require_once 'Q/Crypt.php';

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
	 * Block cipher mode
	 * @var string
	 */
	public $mode = 'ecb';
	
	
	/**
	 * Class constructor.
	 * 
	 * @param array $options
	 */
	public function __construct($options)
	{
	    $options = (array)$options;
	    if (isset($options[0])) $options['method'] = $options[0];
		unset($options[0]);
	    
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
		if (empty($this->method)) throw new Exception("Unable to encrypt; Algoritm not specified.");
		if (!in_array($this->method, mcrypt_list_algorithms())) throw new Exception("Unable to encrypt; Algoritm '$this->method' is not supported.");
		
		if ($value instanceof Fs_File) $value = $value->getContents();
		return mcrypt_encrypt($this->method, $this->secret, $value, $this->mode);
	}
	
	/**
	 * Decrypt encrypted value.
	 *
	 * @param string $value
	 * @return string
	 * 
	 * @throws Decrypt_Exception
	 */
    public function decrypt($value)
    {
    	if (empty($this->method)) throw new Exception("Unable to decrypt; Algoritm not specified.");
		if (!in_array($this->method, mcrypt_list_algorithms())) throw new Exception("Unable to decrypt; Algoritm '$this->method' is not supported.");
    	
    	if (class_exists('Q\Fs_File', false) && $value instanceof Fs_File) $value = $value->getContents();
    	
        $ret = mcrypt_decrypt($this->method, $this->secret, $value, $this->mode);
        if (empty($ret)) throw new Decrypt_Exception("Failed to decrypt value with $this->method using mycrypt.");
        return $ret;
    }
}
