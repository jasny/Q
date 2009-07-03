<?php
namespace Q;

require_once 'Q/Crypt/MD5.php';

/**
 * Encryption class for double md5 method.
 * 
 * @package Crypt
 */
class Crypt_DoubleMD5 extends Crypt_MD5
{
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
	    
		if (!$this->use_salt) return md5(md5($value));
		
		$salt = (empty($salt) ? $this->makesalt() : preg_replace('/\$[\dabcdef]{32}$/', '', $salt));
		return $salt . '$' . md5(md5($salt . $value));
	}
}

?>