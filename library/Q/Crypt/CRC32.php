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
    public $useSalt=false;
	    
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
	    
		if (!$this->useSalt) return sprintf('%08x', crc32($value));
		
		$salt = (empty($salt) ? $this->makeSalt() : preg_replace('/\$[\dabcdef]{8}$/', '', $salt));
		return $salt . '$' . sprintf('%08x', crc32($salt . $value));
	}
}
