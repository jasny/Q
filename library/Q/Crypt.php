<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/SecurityException.php';
require_once 'Q/misc.php';

/**
 * Encryption class (as Command pattern).
 * If you don't need the abstraction, don't use this class. Just call the function. It will perform much better.
 *
 * @package Crypt
 */
abstract class Crypt
{
    /**
     * Secret phrase which is appended to the value to create a checksum hash.
     * @var string
     */
    public $secret;
    
	/**
	 * Driver classes for methods
	 * @var array
	 */
	static public $drivers = array (
	  null=>'Q\Crypt_None',
	  'none'=>'Q\Crypt_None',
	  'crypt'=>'Q\Crypt_System',
	  'md5'=>'Q\Crypt_MD5',
	  '2md5'=>'Q\Crypt_2MD5',
	  'mcrypt'=>'Q\Crypt_MCrypt'
	);
	
	/**
	 * Factory method.
	 *
	 * @param string|array $method  DSN/method (string) or array(method[, arg1, ...])
	 * @param array        $props   Values for public properties
	 * @return Crypt
	 */
	static public function with($method, $props=array())
	{
	    $args = func_get_args();
	    array_shift($args);
	    
	    $args = array_merge(extract_dsn($method), $args);
	    $method = array_shift($args);
	    
		foreach ($args as $key=>$value) {
		    if (!is_int($key)) {
		        $props[$key] = $value;
		        unset($args[$key]);
		    }
		}
	    
		if (!isset(self::$drivers[$method])) throw new Exception("Unable to encrypt: No driver found for method '$method'.");
		
		$class = self::$drivers[$method];
		if (!load_class($class)) throw new Exception("Unable to encrypt: Could not load class '$class' specified for method '$method'. Check your include paths.");
		
		$reflection = new \ReflectionClass($class);
		$object = $reflection->newInstanceArgs($args);
		
	    foreach ($props as $key=>$value) {
	        if (!$reflection->hasProperty($key) || !$reflection->getProperty($key)->isPublic()) continue;
	        if (is_array($value) && is_array($object->$key)) $object->$key = array_merge($object->$key, $value);
    		  else $object->$key = $value; 
	    }
				
		return $object;
	}
	

	/**
	 * Class constructor.
	 */
	public function __construct() { }
		
	/**
	 * Encrypt value.
	 *
	 * @param string $value
	 * @param string $salt   Salt or crypted hash
	 * @return string
	 */
	abstract public function encrypt($value, $salt=null);
	
	
	/**
	 * Create a random salt
	 *
	 * @param int $lenght
	 * @return string
	 */
	static public function makesalt($length=6)
	{
		$salt='';
		while (strlen($salt) < $length) $salt .= sprintf('%x', rand(0, 15));
		return $salt;
	}	
}

?>