<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/SecurityException.php';
require_once 'Q/misc.php';

require_once 'Q/RPC/Client/Handler.php';
require_once 'Q/RPC/Client/SimpleInterface.php';
require_once 'Q/RPC/FileVar.php';

/**
 * Base class for any kind of RPC client.
 * Note: A connection does not have to extend this class, it does need to implement RPC_Client_Handler.
 * 
 * @package RPC
 * @subpackage RPC_Client
 */
abstract class RPC_Client implements RPC_Client_Handler
{
	/**
	 * Drivers with classname (from global namespace).
	 * @var array
	 */
	static public $drivers = array(
		'xmlrpc'=>'Q\RPC_Client_XMLRPC',
		'ssh'=>'Q\RPC_Client_SSH',
		'exec'=>'Q\RPC_Client_Exec'
	);
	
	
	/**
	 * Interface for RPC connection
	 * @var RPCClient_Interface
	 */
	protected $interface;
	
	/**
	 * Extra information the server has send on an alternative channel.
	 * @var string
	 */
	protected $extrainfo;

	
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
	 * Create a new RPC client.
	 * If DSN is given, information will be extracted, merged with $option and passed to the constructor of the driver,
	 *  otherwise all additional arguments are passed.
	 *
	 * @param string $dsn      DSN or driver
	 * @param array  $options
	 * @return RPC_Client_Handler
	 */
	static function create($dsn, $options=null)
	{
		$matches = null;
		if (preg_match('/^(\w+)\:(.*)$/', $dsn, $matches)) { 
			$driver = $matches[1];
			$options = extract_dsn($dsn) + (array)$options;
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
	 * Create an object which indicated a file should be treated as file, not as file name (string)
	 *
	 * @param string $filename
	 * @return RPC_FileVar
	 */
	static function filevar($filename)
	{
		return new RPC_FileVar($filename);
	}
	
	
	/**
	 * Recursively loop through args, looking for files 
	 *
	 * @param array $args
	 * @return array
	 */
	protected function findFilesInArgs($args, &$files=null)
	{
		if (!class_exists('RPC_Client_FileVar', false)) return null;
		
		foreach ($args as $arg) {
			if (is_array($arg)) {
				$this->findFilesInArgs($args, $files);
			} elseif ($arg instanceof RPC_FileVar) {
				$file = (string)$arg;
				$tmpfile = '/tmp/' . (is_uploaded_file($file) ? '' : 'qrpc-tmp.' . md5($file) . '.') . basename($file);
				$files[$file] = $tmpfile;
			}
		}
		
		return $files;
	}
	
	/**
	 * Get interface for making remote calls.
	 *
	 * @return RPC_Client_Interface
	 */
	public function getInterface()
	{
		if (!isset($this->interface)) $this->interface = new RPC_Client_SimpleInterface($this);
		return $this->interface;
	}
	
	/**
	 * Grab any extra info the server has send on an alternative channel.
	 * These are usually warning and notices.
	 * 
	 * @param string  $type  Find specific extra info
	 * @param boolean $all   Return the extra info of all calls made, instead of only of the last call
	 * @return mixed
	 */
	public function getExtraInfo($type=null, $all=false)
	{
		if (isset($type)) return null;
		return isset($this->extrainfo) && !$all ? end($this->extrainfo) : $this->extrainfo;
	}
}

?>