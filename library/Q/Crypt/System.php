<?php
namespace Q;

require_once 'Q/Crypt.php';

/**
 * Encryption class for crypt method.
 * 
 * @package Crypt
 */
class Crypt_System extends Crypt
{
	/**
	 * Type of encryption
	 * @var string
	 */
	public $method;
		
	
	/**
	 * Class constructor.
	 * 
	 * Methods:
	 *   null      Default encrypt method for OS
	 *   std_des   Standerd DES-based
	 *   ext_des   Extended DES-based
	 *   md5       MD5
	 *   blowfish  Blowfish
	 * 
	 * @param string $method
	 */
	public function __construct($method=null)
	{
		$this->method = $method;
	}
	
	/**
	 * Encrypt value
	 *
	 * @param string $value
	 * @param string $salt   Salt or crypted hash
	 * @return string
	 */
	public function encrypt($value, $salt=null)
	{
	    $value .= $this->secret;
	    
		switch (strtolower($this->method)) {
			case null:			
			case 'crypt':		$value = crypt($value, $salt); break;
			
			case 'std_des':		if (!CRYPT_STD_DES) trigger_error("Unable to encrypt value: Standard DES-based encryption with crypt() not available.", E_USER_WARNING);
								  else $value = crypt($value, isset($salt) ? substr($salt, 0, 2) : $this->makesalt(2)); 
								break;
							
			case 'ext_des':		if (!CRYPT_EXT_DES) trigger_error("Unable to encrypt value: Extended DES-based encryption with crypt() not available.", E_USER_WARNING);
								  else $value = crypt($value, isset($salt) ? substr($salt, 0, 9) : $this->makesalt(9));
								break;
							
			case 'md5':			if (!CRYPT_MD5) trigger_error("Unable to encrypt value: MD5 encryption with crypt() not available.", E_USER_WARNING);
								  else $value = crypt($value, isset($salt) ? substr($salt, 0, 12) : $this->makesalt(12));
								break;
							
			case 'blowfish':	if (!CRYPT_BLOWFISH) trigger_error("Unable to encrypt value: Blowfish encryption with crypt() not available.", E_USER_WARNING);
								  else $value = crypt($value, isset($salt) ? substr($salt, 0, 29) : $this->makesalt(29));
								break;
			
			default:			throw new Exception("Unable to encrypt value: Unknown crypt method '{$this->method}'");
		}
		
		return $value;
	}
	
	/**
	 * Create a random salt.
	 *
	 * @param int $length
	 * @return string
	 */
	static public function makesalt($length=CRYPT_SALT_LENGTH)
	{
		$prefix='';
		$suffix='';
	    
		switch($length) {
			case 29:
				$prefix='$2a$0' . rand(4, 9) . '$';
				$suffix='$';
				break;
				
			case 12:
				$prefix='$1$';
				$suffix='$';
				break;
				
			case 9:
			case 2:
				break;

			default: 
			    throw new Exception("Invalid length for salt '$length'.");
		}

		$salt='';
		$length -= strlen($prefix) + strlen($suffix);
		while (strlen($salt) < $length) $salt .= chr(rand(64,126));
		
		return $prefix . $salt . $suffix;
	}
}

?>