<?php
namespace Q;

require_once 'Q/StreamingConnection.php';

/**
 * Object with stream resources for Exec RPC Client.
 * 
 * @package RPC
 * @subpackage RPC_Client
 */
class Stream_Exec implements StreamingConnection
{
	/**
	 * Shell command
	 * @var string
	 */
	protected $command;
	
	/**
	 * Additional options
	 * @var array
	 */
	protected $options;

	/**
	 * Running process
	 * @var resource
	 */
	protected $process;
	
	/**
	 * Input stream
	 * @var stream
	 */
	protected $stdin;
	
	/**
	 * Output stream
	 * @var stream
	 */
	protected $stdout;
	
	/**
	 * Error stream
	 * @var stream
	 */
	protected $stderr; 

	/**
	 * Data from the error stream.
	 * @var string
	 */
	protected $errors = "";
	
	
	/**
	 * Class constructor
	 *
	 * @param string $command  Command that should be executed, look at command property for details.
	 * @param array  $options  Additional options. Properties of this class, methods/callbacks of ssh2_connect() and auth ('none', 'password', 'publickey' or 'hostbased') + props for auth. 
	 */
	public function __construct($command=null, $options=array())
	{
		if (is_array($command)) {
			$options = $command + $options;
			unset($command);
		}
		
		if (!isset($command)) {
			if (!isset($options['command'])) throw new Exception("Could not create Exec stream: No command specified.");
			$command = $options['command'];
		}

		$this->command = $command;
		$this->options = $options;
	}

	/**
	 * Reconnect.
	 */
	public function reconnect()
	{
		if (isset($this->process)) $this->close();
		$this->execCommand();
	}
	
	/**
	 * Execute the command.
	 *
	 * @param string $function
	 * @param array  $args
	 */
	protected function execCommand()
	{
		$command = (!empty($this->options['sudo']) ? "sudo -u " . escapeshellarg($this->options['sudo']) . " " : "") . $this->command;
		
		$pipes = array();
		$process = proc_open($command, array(array('pipe', 'r'), array('pipe', 'w'), array('pipe', 'w')), $pipes);
		if (!is_resource($process)) throw new Exception("Execution of '$command' failed.");

		$this->process = $process;
		list($this->stdin, $this->stdout, $this->stderr) = $pipes;
	}
	
	/**
	 * Get the input stream.
	 * 
	 * @return resource
	 */
	public function forInput()
	{
		return $this->stdin;
	}
	
	/**
	 * Get the output stream.
	 * 
	 * @return resource
	 */
	public function forOutput()
	{
		return $this->stdout;
	}
	
	/**
	 * Close the stream
	 */
	public function close()
	{
		fclose($this->stdin);
		$this->stdin = null;
		
		fclose($this->stdout);
		$this->stdout = null;
		
		$this->getExtraInfo();
		fclose($this->stderr);
		$this->stderr = null;
		
		$return_value = proc_close($this->process);		
		$this->process = null;
		
		if (($this->options['checkReturn'] && $return_value != 0) || (!empty($this->errors) && $this->options['faultOnStderr'])) throw new Exception("Exec command '$exec' failed.\n" . join("\n", $this->errors));
	}
	
	/**
	 * Return extra info/errors.
	 * 
	 * @return string
	 */
	public function getExtraInfo()
	{
		if (isset($this->stderr)) $this->errors .= stream_get_contents($this->stderr);
		return $this->errors; 
	}
	
	/**
	 * Get information about the stream.
	 *
	 * @return string
	 */
	public function about($command=true)
	{
		return "(Exec): {$command}";
	}	
}

?>