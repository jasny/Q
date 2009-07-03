<?php

namespace Q;

/**
 * Baseclass to set up an SSH connection.
 * 
 * You probably want to use Q\RPC_Client_SSH instead.
 * 
 * @package SSH
 */
class SSH
{
	/**
	 * Default authentication settings.
	 * 
	 * pubkeyfile   Public key file; places hostname for %h and username for %r.
	 * privkeyfile  Private key file; places hostname for %h and username for %r.
	 * passphrase   Passphrase for SSH authentication files.
	 * hostbased    Host for hostbased authentication.
	 * 
	 * @var array
	 */
	public static $default_auth = array(
		'pubkeyfile'=>'~/.ssh/id_rsa.pub',
		'privkeyfile'=>'~/.ssh/id_rsa',
		'passphrase'=>'',
		'hostbased'=>'localhost'
	);

	/**
	 * SSH conenction
	 * @var resource
	 */
	protected $connection;

	/**
	 * The hostname
	 * @var string
	 */
	protected $host;

	/**
	 * Connection options
	 * @var array
	 */
	public $options;

	/**
	 * Class constructor
	 *
	 * @param string $host     [username[:password]@]host[:port]
	 * @param array  $options  Additional options: methods/callbacks of ssh2_connect() + auth (see default_auth property) 
	 */
	public function __construct($host, $options=array())
	{
		if (!extension_loaded('ssh2')) throw new Exception("Unable to create an SSH connection: Extension 'ssh2' is not loaded. (see http://php.net/ssh2)");
		
		if (!is_scalar($host)) {
			$options = (array)$host + (array)$options;
			unset($host);
		}
		
		if (!isset($host)) {
			if (!isset($this->options->host)) throw new Exception("Could not create SSH connection: No host specified.");
			$host = $this->options->host;
		}

		$this->host = $host;
		$this->options = (object)$options + $this->default_auth;

		$this->makeConnection();
	}

	/**
	 * Create SSH connection
	 */
	protected function makeConnection()
	{
		$host = $this->host;

		// Extract from hostname
		$matches = null;
		if (!preg_match('/^(?:([^:@]++)(?:\:([^:@]++))?@)?([^:@]++)(?:\:([^:@]++))?$/', $host, $matches)) throw new Exception("Could not create SSH connection: Illegal host string.");
		$matches = $matches + array_fill(0, 5, null);
		list(, $username, $password, $host, $port) = $matches;

		// Get user/password
		if (!empty($this->options->username)) $username = $this->options->username;
		if (empty($username)) {$_tmp_ = posix_getpwuid(posix_getuid()); $username = $_tmp_['name'];} 
		if (!empty($this->options->password)) $password = $this->options->password;

		// Get port
		$port = !empty($this->options->port) ? $this->options->port : 22;
		if (isset($port) && !is_int($port) && !ctype_digit($port)) throw new Exception("Could not create SSH connection for '$host': Given port '$port' is not a numeric value.");
		
		// Get methods and callbacks
		if (!isset($this->options->methods)) $this->options->methods = array_chunk_assoc($this->options, 'methods'); 
		if (!isset($this->options->callbacks)) $this->options->callbacks = array_chunk_assoc($this->options, 'callbacks'); 
		
		// Make the connection
		$this->connection = ssh2_connect($host, $port, $this->options->methods, $this->options->callbacks);
		if (!$this->connection) throw new Exception("Could not create SSH connection for '$host:$port': Failed to connect to server.");
		
		// Autenticate
		$auth_methods = isset($this->options->auth) ? (array)$this->options->auth : ssh2_auth_none($this->connection, $username);
		$authenticated = ($auth_methods === true);

		while (!$authenticated && current($auth_methods)) {
			switch (current($auth_methods)) {
				case 'none':		$authenticated = (@ssh2_auth_none($this->connection, $username) === true); break;
				case 'password':	$authenticated = isset($password) && @ssh2_auth_password($this->connection, $username, $password); break;
				case 'publickey':	$authenticated = @ssh2_auth_pubkey_file($this->connection, $username, $this->options->pubkeyfile, $this->options->privkeyfile, $this->options->passphrase); break;
				case 'hostbased':	$authenticated = @ssh2_auth_hostbased_file($this->connection, $username, $this->options->hostbased, $this->options->pubkeyfile, $this->options->privkeyfile, $this->options->passphrase); break;
			}
			next($auth_methods);
		}

		if (!$authenticated) throw new Exception("Could not create SSH connection for '$host:$port': Authentication for user '$username' failed (" . join(', ', $auth_methods) . ").");
	}

	/**
	 * Close the connection. 
	 * The RPC client can still be used after this, it will simply reconnect.
	 * 
	 * @todo See if the exit command worked or find a way of dropping the connection by closing the socket.
	 */
	public function close()
	{
		if (!isset($this->connection)) return;
		
		ssh2_exec($this->connection, 'exit');
		unset($this->connection);
	}
	
	
	/**
	 * Execute ssh command.
	 * Returns array(stdio, stderr)
	 *
	 * @param string $command  Command that should be executed.
	 * @return array
	 */
	public function execute($command)
	{
		if (!isset($this->connection)) $this->makeConnection(); # Auto reconnect
		
		$stdio = ssh2_exec($this->connection, $command);
		if (empty($stdio)) throw new Exception($this->about($command) . ": SSH execution failed.");

		$stderr = ssh2_fetch_stream($stdio, SSH2_STREAM_STDERR);
		
		return array($stdio, $stderr);
	}
	
	/**
	 * Copy a file using SFTP.
	 *
	 * @param string $local   Path to local file
	 * @param string $remote  Path for file on remote system
	 */
	public function sendFile($local, $remote)
	{
		if (!isset($this->connection)) $this->makeConnection(); # Auto reconnect
		
		$sftp = ssh2_sftp($this->connection);
		if (!copy($local, "ssh2.sftp://{$sftp}{$remote}")) trigger_error("Copying file '$local' to '" . $this->about() . "' as '$remote' failed.", E_WARNING);
	}
	
	
	/**
	 * Get information about the RPC client
	 *
	 * @param string $command 
	 * @return string
	 */
	public function about($command=null)
	{
		return preg_replace('/^\:[^:@]++(?=@)/', '', $this->host) . ' (SSH)' . (!empty($command) ? " -> {$command}" : '');
	}
}

?>