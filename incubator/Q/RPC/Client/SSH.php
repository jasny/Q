<?php
namespace Q;

require_once 'Q/SSH.php';
require_once 'Q/RPC/Client.php';
require_once 'Q/RPC/Fault.php';

/**
 * RPC Client which sets up an SSH connection.
 * 
 * @package RPC
 * @subpackage RPC_Client
 */
class RPC_Client_SSH extends SSH implements RPC_Client_Handler
{
	/**
	 * Options passed to the constructor.
	 * Will become a value object.
	 * 
	 * serialize      Callback function to serialize arguments.
	 * unserialize    Callback function to unserialize result.
	 * arrayc         How to convert an array to a string; array(glue, prefix, suffix, key-value)
	 * copyfiles      Copy files for FileVar arguments.
	 * checkreturn    Throw an RPC Fault is return code isn't 0
	 * faultonstderr  If stderr isn't empty, throw an RPC Fault
	 *  
	 * @var object
	 */
	public $options = array(
		'arrayc'=>array('glue'=>' ', 'key-value'=>'=', 'key-prefix'=>'--'),
		'serialize'=>null,
		'unserialize'=>null,
		'copyfiles'=>false,
		'checkreturn'=>true,
		'faultonstderr'=>false,
	);
	
	/**
	 * Command that should be executed.
	 * 
 	 * Places function name for {$0}, or append as first argument otherwise. 
	 * Places argument for {$1}, {$2], etc or {$?} or append otherwise. 
	 * 
	 * @var string
	 */
	public $command;

	
	/**
	 * Class constructor.
	 *
	 * @param string $host     [username[:password]@]host[:port]
	 * @param string $command  Command that should be executed, look at command property for details.
	 * @param array  $options  Additional options. See options properties + methods/callbacks of ssh2_connect() + auth ('none', 'password', 'publickey' or 'hostbased') 
	 */
	public function __construct($host, $command=null, $options=array())
	{
		if (!extension_loaded('ssh2')) throw new Exception("Unable to create an SSH RPC client: Extension 'ssh2' is not loaded. (see http://php.net/ssh2)");

		$options = (array)$options;
		
		if (!is_scalar($host)) {
			$options = (array)$host + $options;
			unset($host);
		}
		
		if (!isset($host)) {
			if (!isset($options['host'])) throw new Exception("Could not create SSH connection: No host specified.");
			if (!isset($options['command'])) throw new Exception("Could not create SSH RPC client: No command specified.");
			
			$host = $options['host'];
			if (!isset($command)) $command = $options['command'];
		}

		$this->host = $host;
		$this->command = $command;
		$this->options = (object)($options + $this->options);

		$this->makeConnection();
	}
	
	/**
	 * Run ssh command.
	 *
	 * @param string $function
	 * @param array  $args
	 * @return mixed
	 */
	public function execute($function, $args)
	{
		if (!isset($this->connection)) $this->makeConnection(); # Auto reconnect
		
		if (!preg_match('/^\w[\w-\.]*$/', $function)) throw new SecurityException("Illegal function name '$function': A function name should only contain alphanumeric chars and dashes.");
		
		$command = $this->command;
		$command = strpos($command, '{$0}') !== false ? str_replace('{$0}', $function, $command) : $command . ' ' . $function;

		$eargs = array(); 
		$files = array();
		foreach ($args as $arg) $eargs[] = $this->escapeArg($arg, $files);
		
		if (preg_match('/\{$\d+\}/', $command)) $command = preg_replace('/\{$(\d+)\}/e', 'isset($eargs[$1]) ? $eargs[$1] : null', $command);
		 elseif (preg_match('/\{$\?\}/', $command)) $command = preg_replace('/\{$\?\}/', join(' ', $eargs), $command);
		 else $command .= ' ' . join(' ', $eargs);

		if ($this->options->checkreturn) $command .= '; ERR=$?; [ $ERR -eq 0 ] || echo "Q Error: Exited with return code $ERR" 1>&2';
		 
		foreach ($files as $file=>$tmpfile) $this->sendFile($file, $tmpfile);

		list($stdio, $stderr) = parent::execute($command);
		
		stream_set_blocking($stdio, true);
		stream_set_blocking($stderr, true);
		
		$output = stream_get_contents($stdio);
		$errors = stream_get_contents($stderr);

		if (!empty($this->options->unserialize)) {
			$fn = $this->options->unserialize;
			$output = $fn($output);
		}
		$this->extrainfo[] = $errors;
		
		if (!empty($errors) && ($this->options->faultonstderr || $this->options->checkreturn && preg_match('/Q Error: Exited with return code \d*$/s', $errors))) throw new RPC_Fault($this->about($command), $output, 0, $errors);
		return $output;
	}
	
	/**
	 * Escape / serialize argument.
	 *
	 * @param mixed $arg
	 * @param array $files  Output, files that need to be copied (SplFileInfo or Q\RPC_FileVar)
	 * @return string
	 */
	public function escapeArg($arg, &$files=array())
	{
		if ($this->options->copyfiles && (class_exists(__NAMESPACE__ . '::RPC_FileVar', false) && $arg instanceof RPC_FileVar)) {
			$file = (string)$arg;
			$tmpfile = '/tmp/' . (is_uploaded_file($file) ? '' : 'sshrpc-tmp.' . md5($file) . '.') . basename($file);
			$files[$file] = $tmpfile;
			$arg = $tmpfile;
		}
		
		if (!empty($this->options->serialize)) return call_user_func($this->options->serialize, $arg);
		
		if (!is_scalar($arg)) {
			$parts = array();
			foreach ($arg as $key=>$value) {
				$parts[] = (is_int($key) ? '' : escapeshellarg($this->options->arrayc['key-prefix'] . $key) . $this->options->arrayc['key-value']) . $this->escapeArg($value);
			}
			return join($this->options->arrayc['glue'], $parts);
		}
		
		return escapeshellarg((string)$arg);
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
		
	/**
	 * Get information about the RPC client.
	 *
	 * @param boolean|string $command  Include command 
	 * @return string
	 */
	public function about($command=true)
	{
		if ($command === true) $command = $this->command;
		return parent::about($command);
	}
}

?>