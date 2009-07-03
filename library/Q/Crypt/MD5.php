<?php
namespace Q;

require_once 'Q/Crypt.php';

/**
 * Encryption class for md5 method.
 * 
 * @package Crypt
 */
class Crypt_MD5 extends Crypt
{
    /**
     * Use a salt.
     * @var boolean
     */
    public $use_salt=false;
    
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
	    
		if (!$this->use_salt) return md5($value);
		
		$salt = (empty($salt) ? $this->makesalt() : preg_replace('/\$[\dabcdef]{32}$/', '', $salt));
		return $salt . '$' . md5($salt . $value);
	}
}

?>