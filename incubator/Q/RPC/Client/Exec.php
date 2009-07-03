<?php
namespace Q;

require_once 'Q/RPC/Client.php';
require_once 'Q/RPC/Fault.php';

/**
 * RPC Client which executes a command.
 * 
 * @package RPC
 * @subpackage RPC_Client
 */
class RPC_Client_Exec extends RPC_Client
{
	/**
	 * Options passed to the constructor.
	 * 
	 * arrayc         How to convert an array arguments to a string (for more freedom use the serialize option).
	 * serialize      Callback function to serialize/escape arguments.
	 * unserialize    Callback function to unserialize result.
	 * checkreturn    Throw an RPC Fault is return code isn't 0
	 * faultonstderr  If stderr isn't empty, throw an RPC Fault
	 * 
	 * @var array
	 */
	public $options = array(
		'arrayc'=>array('glue'=>' ', 'key-value'=>'=', 'key-prefix'=>'--'),
		'serialize'=>null,
		'unserialize'=>null,
		'checkreturn'=>true,
		'faultonstderr'=>false,
	);
	
	/**
	 * Command that should be executed.
	 * 
 	 * Places function name for {$0}, or append as first argument otherwise. 
	 * Places argument for {$1}, {$2}, etc or {$*} or append otherwise. 
	 * 
	 * @var string
	 */
	public $command;

	
	/**
	 * Class constructor
	 *
	 * @param string $command  Command that should be executed, look at command property for details.
	 * @param array  $options  Additional options. Properties of this class, methods/callbacks of ssh2_connect() and auth ('none', 'password', 'publickey' or 'hostbased') + props for auth. 
	 */
	public function __construct($command=null, $options=array())
	{
		$options = (array)$options;
		
		if (!is_scalar($command)) {
			$options = (array)$command + $options;
			unset($command);
		}
		
		if (!isset($command)) {
			if (!isset($options['command'])) throw new Exception("Could not create Exec RPC client: No command specified.");
			$command = $options['command'];
		}

		$this->command = $command;
		$this->options = (object)($options + $this->options);
	}

	/**
	 * Close the connection. Does nothing.
	 */
	public function close()
	{
	}
	

	/**
	 * Get a command which should be executed.
	 *
	 * @param string $function
	 * @param string $args
	 * @return string
	 */
	protected function getCommand($function, $args=array())
	{
		if (!preg_match('/^\w[\w-\.]*$/', $function)) throw new SecurityException("Illegal function name '$function': A function name should only contain alphanumeric chars, dashes and dots.");
		
		$command = (!empty($this->options->sudo) ? "sudo -u " . escapeshellarg($this->options->sudo) . " " : "") . $this->command;
		if (isset($function)) $command = strpos($command, '{$0}') !== false ? str_replace('{$0}', $function, $command) : $command . ' ' . $function;

		if (!empty($args)) {
			$eargs = array_map(array($this, 'escapeArg'), $args);
			
			if (preg_match('/\{\$\d+\}/', $command)) $command = preg_replace('/\{\$(\d+)\}/e', 'isset($eargs[$1]) ? $eargs[$1] : null', $command);
			 elseif (preg_match('/\{\$\*\}/', $command)) $command = preg_replace('/\{\$\*\}/', join(' ', $eargs), $command);
			 else $command .= ' ' . join(' ', $eargs);
		}
		
		return $command;
	}
	
	/**
	 * Run command.
	 *
	 * @param string $function
	 * @param array  $args
	 * @return mixed
	 */
	public function execute($function, $args=array())
	{
		$command = $this->getCommand($function, $args);

		$pipes = array();
		$process = proc_open($command, array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')), $pipes);
		if (!is_resource($process)) throw new Exception("Execution of '$function' failed.");		
		list($stdin, $stdout, $stderr) = $pipes;
		
		fclose($stdin);
		
		stream_set_blocking($stdout, true);
		stream_set_blocking($stderr, true);
		
		$output = stream_get_contents($stdout);
		$errors = stream_get_contents($stderr);
		fclose($stdout);
		fclose($stderr);
		
		$return_value = proc_close($process);		
		
		if (!empty($this->options->unserialize)) $output = $this->options->unserialize($output);
		$this->extrainfo[] = $errors;
		
		if (($this->options->checkreturn && $return_value != 0) || (!empty($errors) && $this->options->faultonstderr)) throw new RPC_Fault($this->about($command), "Execution of command failed" , 0, $errors);
		return $output;
	}
	
	/**
	 * Escape / serialize argument.
	 *
	 * @param mixed $arg
	 * @return string
	 */
	public function escapeArg($arg)
	{
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
	 * Get information about the RPC client.
	 *
	 * @param boolean|string $command  Include command 
	 * @return string
	 */
	public function about($command=true)
	{
		if ($command === true) $command = $this->command;
		return '(Exec)' . (!empty($command) ? " -> {$command}" : '');
	}
}

?>