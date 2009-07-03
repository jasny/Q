<?php
namespace Q;

require_once 'Q/Crypt.php';

/**
 * Encryption class for CRC32 method.
 * 
 * @package Crypt
 */
class Crypt_CRC32 extends Crypt
{
    /**
     * Use a salt.
     * @var boolean
     */
    public $use_salt=false;
    
	/**
	 * Class constructor.
	 * 
	 * @param boolean $use_salt
	 */
	public function __construct($use_salt=false)
	{
		$this->use_salt = (int)$use_salt === 1;
	}
	    
	/**
	 * Encrypt value.
	 *
	 * @param string $value
	 * @param string $salt   Salt or crypted hash
	 * @return string
	 */
	public function encrypt($value, $salt=null)
	{
	    $value .= $this->secret;
	    
		if (!$this->use_salt) return crc32($value);
		
		$salt = (empty($salt) ? $this->makesalt() : preg_replace('/\$[\dabcdef]{32}$/', '', $salt));
		return $salt . '$' . crc32($salt . $value);
	}
}

?>