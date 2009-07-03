<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/RPC/Server/Handler.php';

/**
 * Base class for any kind of RPC server.
 * 
 * @package RPC
 * @subpackage RPC_Server
 */
abstract class RPC_Server implements RPC_Server_Handler
{
	/**
	 * Drivers with classname
	 * @var array
	 */
	static public $drivers = array(
		'xmlrpc'=>'Q\RPC_Server_XMLRPC',
	);
	
	
	/**
	 * Object that handles RPC requests.
	 * @var object
	 */
	protected $object;
	

	/**
	 * Get the classname for a driver.
	 * 
	 * @param string  $name
	 * @param boolean $load  Load class
	 * @return string
	 */
	static function getDriverClass($name, $load=true)
	{
		if (!isset(self::$drivers[$name])) return null;

		$class = self::$drivers[$name];
		if($load) load_class($class);
		
		return $class;
	}
	
	/**
	 * Create a new RPC server.
	 * If DSN is given, information will be extracted, merged with $option and passed to the constructor of the driver,
	 *  otherwise all additional arguments are passed.
	 *
	 * @param string $dsn      DSN or driver
	 * @param array  $options
	 * @return RPC_Server_Handler
	 */
	static function create($dsn, $options=null)
	{
		$matches = null;
		if (preg_match('/^(\w+)\:(.*)$/', $dsn, $matches)) { 
			$driver = $matches[1];
			$options = self::extractDSN($dsn) + (array)$options;
		} else {
			$driver = $dsn;
			$args = func_get_args();
			array_shift($args);
		}
		
		$class = self::getDriverClass($driver);
		if (!$class) throw new Exception("Unable to create RPC client: Unknown driver '$driver'");

		if (isset($args)) {
			$reflection = new ReflectionClass($class);
			return $reflection->newInstanceArgs($args);
		}
		
		return new $class($options);
	}
	
	
	/**
	 * Sets class which will handle RPC requests.
	 * Fluent interface.
	 *
	 * @param string $classname
	 * @param                    Additional arguments will be passed to the contructor
	 * @return RPC_Server_Handler
	 */
	public function setClass($classname)
	{
		$args = func_get_args();
		array_shift($args);
		
		$reflection = new ReflectionClass($classname);
		$this->setObject($reflection->newInstanceArgs($args));
		
		return $this;
	}

	/**
	 * Sets object which will handle RPC requests
	 * Fluent interface.
	 * 
	 * @param object $object
	 * @return RPC_Server_Handler
	 */
	public function setObject($object)
	{
		$this->object = $object;
		return $this;
	}

	/**
	 * Gets object which handles RPC requests
	 * 
	 * @return object
	 */
	public function getObject()
	{
		return $this->object;
	}
}

?>