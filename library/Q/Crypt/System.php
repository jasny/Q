<?php
namespace Q;

require_once 'Q/Crypt.php';

/**
 * Encryption class for crypt method.
 * 
 * Methods:
 *   null      Default encrypt method for OS
 *   std_des   Standerd DES-based
 *   ext_des   Extended DES-based
 *   md5       MD5
 *   blowfish  Blowfish
 * 
 * @package Crypt
 * 
 * @todo ext_des and blowfish encryption doesn't work (see unit test failures)
 */
class Crypt_System extends Crypt
{
	/**
	 * Type of encryption.
	 * @var string
	 */
	public $method;
		
	
	/**
	 * Class constructor.
	 * 
	 * @param array $options  Values for public properties
	 */
	public function __construct($options=array())
	{
	    $options = (array)$options;
	    
	    if (isset($options[0])) {
	        $this->method = $options[0];
	        unset($options[0]);
	    }
	    
	    parent::__construct($options);
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
			case 'crypt':		return crypt($value, $salt);
			
			case 'std_des':		if (!CRYPT_STD_DES) throw new Exception("Unable to encrypt value: Standard DES-based encryption with crypt() not available.");
								return crypt($value, isset($salt) ? substr($salt, 0, 2) : $this->makesalt(2)); 
								break;
							
			case 'ext_des':		if (!CRYPT_EXT_DES) throw new Exception("Unable to encrypt value: Extended DES-based encryption with crypt() not available.");
								return crypt($value, isset($salt) ? substr($salt, 0, 9) : $this->makesalt(9));
							
			case 'md5':			if (!CRYPT_MD5) throw new Exception("Unable to encrypt value: MD5 encryption with crypt() not available.");
								return crypt($value, isset($salt) ? substr($salt, 0, 12) : $this->makesalt(12));
							
			case 'blowfish':	if (!CRYPT_BLOWFISH) throw new Exception("Unable to encrypt value: Blowfish encryption with crypt() not available.");
								return crypt($value, isset($salt) ? substr($salt, 0, 29) : $this->makesalt(29));
			
			default:			throw new Exception("Unable to encrypt value: Unknown crypt method '{$this->method}'");
		}
	}
	
	/**
	 * Create a random salt.
	 *
	 * @param int $length
	 * @return string
	 */
	static public function makeSalt($length=CRYPT_SALT_LENGTH)
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
		while (strlen($salt) < $length) $salt .= chr(rand(64, 126));
		
		return $prefix . $salt . $suffix;
	}
}

