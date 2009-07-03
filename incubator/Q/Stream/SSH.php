<?php
require_once 'Q/StreamingConnection.php';

/**
 * Wrapper for SSH stream.
 * 
 * @package Stream
 */
class Stream_SSH extends SSH implements StreamingConnection
{
	protected $stdio;
	protected $stderr; 

	protected $errors = "";
	
	/**
	 * Class constructor
	 *
	 * @param resource $conn
	 * @param resource $stdio
	 */
	public function __construct($conn, $stdio)
	{
		$this->conn = $conn;
		
		$this->stdio = $stdio;
		$this->stderr = ssh2_fetch_stream($stdio, SSH2_STREAM_STDERR);
	}
	
	/**
	 * Get the stream.
	 * 
	 * @return resource
	 */
	public function forInput()
	{
		return $this->stdio;
	}
	
	/**
	 * Get the stream.
	 * 
	 * @return resource
	 */
	public function forOutput()
	{
		return $this->stdio;
	}
	
	/**
	 * Close the stream
	 */
	public function close()
	{
		fclose($this->stdio);
		$this->stdio = null;
		
		$this->getExtraInfo();
		fclose($this->stderr);
		$this->stderr = null;
	}
	
	/**
	 * Read from stderr.
	 * 
	 * @return string
	 */
	public function getExtraInfo()
	{
		if (isset($this->stderr)) $this->errors .= stream_get_contents($this->stderr);
		
		if (!empty($this->errors) && ($this->faultOnStderr || $this->checkReturn && preg_match('/Exited with return code \d*$/s', $this->errors))) throw new RPC_Fault::General($this->conn->about(), "SSH command failed", 0, $this->errors);
		return $this->errors;
	}
}

?>