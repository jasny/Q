<?php
namespace Q;

require_once 'Q/Encrypt.php';
require_once 'Q/Decrypt.php';

require_once 'Q/misc.php';
require_once 'Q/Exception.php';
require_once 'Q/Decrypt/Exception.php';

/**
 * Encryption/decryption interface.
 * 
 * If a Crypt class implements the Decrypt interface, it can do encryption and decryption. Otherwise it can only do encryption. 
 *
 * @package Crypt
 */
abstract class Crypt implements Encrypt
{
	/**
	 * Driver classes for methods
	 * @var array
	 */
	static public $drivers = array (
	  'none' => 'Q\Crypt_None',
	  'crypt' => 'Q\Crypt_System',
	  'md5' => 'Q\Crypt_MD5',
	  '2md5' => 'Q\Crypt_2MD5',
	  'hash' => 'Q\Crypt_Hash',
	  'mcrypt' => 'Q\Crypt_MCrypt',
	  'openssl' => 'Q\Crypt_OpenSSL'
	);
	

    /**
     * Secret phrase.
     * This phrase is used as password to encrypt/decrypt or to create secure hash.
     * 
     * @var string
     */
    public $secret;
    
    
	/**
	 * Factory method.
	 *
	 * @param string|array $dsn    DSN/method (string) or array(method, prop=>value, ...])
	 * @param array        $props  Values for public properties
	 * @return Crypt
	 */
	static public function with($dsn, $props=array())
	{
	    $dsn_props = extract_dsn($dsn);
	    $method = array_shift($dsn_props);
	    $props = $dsn_props + $props;
	    
		if (!isset(self::$drivers[$method])) throw new Exception("Unable to encrypt: No driver found for method '$method'.");
		$class = self::$drivers[$method];
		if (!load_class($class)) throw new Exception("Unable to encrypt: Could not load class '$class' specified for method '$method'. Check your include paths.");
		
		return new $class($props);
	}
	
	/**
	 * Class constructor.
	 * 
	 * @param array $options  Values for public properties
	 */
	public function __construct($options=array())
	{
	    foreach ($options as $key=>$value) {
            $this->$key = $value; 
	    }
	}
	
	
	/**
	 * Create a random salt
	 *
	 * @param int $lenght
	 * @return string
	 */
	static public function makeSalt($length=6)
	{
		$salt='';
		while (strlen($salt) < $length) $salt .= sprintf('%x', rand(0, 15));
		return $salt;
	}
}
