<?php
namespace Q;

require_once 'Q/Crypt/MD5.php';

/**
 * Encryption class using md5 twice.
 * 
 * By using MD5 twice, it is more difficult to use a rainbow table. However using a salt or secret phrase also
 * protects against this and works better.
 * 
 * @package Crypt
 */
class Crypt_DoubleMD5 extends Crypt
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
	    
		if (!$this->useSalt) return md5(md5($value));
		
		$salt = (empty($salt) ? $this->makeSalt() : preg_replace('/\$[\dabcdef]{32}$/', '', $salt));
		return $salt . '$' . md5(md5($salt . $value));
	}
}
