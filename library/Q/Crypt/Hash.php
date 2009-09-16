<?php
namespace Q;

require_once 'Q/Crypt.php';
require_once 'Q/Decrypt.php';

/**
 * Create hash.
 * 
 * @package Crypt
 */
class Crypt_Hash extends Crypt 
{
    /**
     * Use a salt.
     * @var boolean
     */
    public $useSalt=false;
    	
	/**
	 * Hashing algoritm.
	 * @var string
	 */
	public $method;
	
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
	    if (empty($this->method)) throw new Exception("Unable to encrypt; Hashing algoritm not specified.");
	    
		if ($value instanceof Fs_File) {
			if (empty($this->secret) && !$this->useSalt) return hash_file($this->method, $value);
			$value = $value->getContents();
		}
		
		$value .= $this->secret;
		if (!$this->useSalt) return hash($this->method, $value);
		
		$salt = (empty($salt) ? $this->makeSalt() : preg_replace('/\$[\dabcdef]{32}$/', '', $salt));
		return $salt . '$' . hash($this->method, $salt . $value);
	}
}
