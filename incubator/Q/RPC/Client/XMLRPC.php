<?php
namespace Q;

require_once 'Q/RPC/Client.php';
require_once 'Q/RPC/XMLRPCFault.php';

/**
 * XML RPC Client.
 * 
 * @package RPC
 * @subpackage RPC_Client
 */
class RPC_Client_XMLRPC extends RPC_Client
{
	/**
	 * Keep stream open and write multiple requests to it
	 */
	public $keepalive = false;
	
	/**
	 * String to connect to server
	 * @var string
	 */
	protected $url;
	
	/**
	 * Context for stream
	 * @var resource
	 */
	public $context;
	
	/**
	 * External streaming connection
	 * @var Streaming
	 */
	protected $connection;
	
	/**
	 * Copy files for FileVar arguments
	 * @var boolean
	 */
	public $copyfiles = false;

	
	/**
	 * Class constructor
	 *
	 * @param mixed $url      URL (string), stream (resource) or Q\Stream      
	 * @param array $options  Additional options. Properties of this class, methods/callbacks of ssh2_connect() and auth ('none', 'password', 'publickey' or 'hostbased') + props for auth. 
	 */
	public function __construct($url, $options=array())
	{
		if (is_array($url)) {
			$options = $url + $options;
			$url = isset($options['url']) ? $options['url'] : null; 
		}

		if (is_object($url) || isset($options['use'])) {
			$this->connection = is_object($url) ? $url : (is_object($options['use']) ? $options['use'] : Stream::create($options['use'], $options));
			if (!interface_exists(__NAMESPACE__ . '::StreamingConnection', false) || !($this->connection instanceof StreamingConnection)) throw new Exception("You can't use an " . (is_object($this->connection) ? get_class($this->connection) : $this->connection) . " as a stream.");
		} else {
			$this->connection = Connection::create($url, $options);
		}
		
		$this->copyfiles = !empty($options['copyfiles']);
		$this->keepalive = isset($this->stream) || !empty($options['keepalive']);
	}

	/**
	 * Close the connection and stream. 
	 */
	public function close()
	{
		$this->connection->close();
	}
	
	
	/**
	 * Run ssh command
	 *
	 * @param string $function
	 * @param array  $args
	 * @return mixed
	 */
	public function execute($function, $args)
	{
		if (!$this->connection->isOpen()) $this->connection->reconnect(); // Autoreconnect
		
		if ($this->copyfiles && isset($this->connection) && method_exists($this->connection, 'sendFile')) {
			foreach ((array)$this->findFilesInArgs($args) as $local=>$remote) {
				$this->connection->sendFile($local, $remote);
			}
		}

		$request = xmlrpc_encode_request($function, $args);
		
		stream_set_blocking($this->connection->forOutput(), true);
		fwrite($this->connection->forInput(), $request . "\0");

		$output = stream_get_contents($this->connection->forOutput());
		stream_set_blocking($this->connection->forOutput(), false);
		if ($output === false) throw new Exception("Failed to read contents from stream");

		$this->storeExtraInfo();
						
		$content_type = $this->getExtraInfo('Content-Type');
		if (!empty($content_type) && $content_type !== 'text/xml') return $output;

		$output = trim($output);
		$value = xmlrpc_decode($output);
		
		if (!isset($value) && (($sxml = simplexml_load_string($output)) === false || $sxml->getName() != 'methodResponse')) {
			throw new Exception("XMLRPC request for '$function' failed: Invalid string returned.\n$output");
		}
		
		if (is_array($value) && xmlrpc_is_fault($value)) throw new RPC_XMLRPCFault($this, $value['faultString'], $value['faultCode'], $this->getExtraInfo("_raw_"));
		return $value;
	}
	
	/**
	 * Recursively loop through args, looking for files.
	 *
	 * @param array $args   Arguments (will be modified)
	 * @param array $files  Add to this list of files
	 * @return array
	 */
	protected function findFilesInArgs(&$args, &$files=null)
	{
		if (!class_exists(class_exists(__NAMESPACE__ . '::RPC_FileVar', false), false)) return null;
		
		for ($i=0, $n=count($args); $i<$n; $i++) {
			if (is_array($args[$i])) {
				$this->findFilesInArgs($args[$i], $files);
			} elseif ($args[$i] instanceof RPC_FileVar) {
				$file = (string)$args[$i];
				$tmpfile = '/tmp/' . (is_uploaded_file($file) ? '' : 'qrpc-tmp.' . md5($file) . '.') . basename($file);
				$files[$file] = $tmpfile;
				$args[$i] = $tmpfile;
			}
		}
		
		return $files;
	}

	/**
	 * Grab any extra info the server has sent on an alternative channel.
	 * These are usually just HTTP headers, except if server is RPCServer.
	 * 
	 * @param string  $type  Find specific extra info
	 * @param boolean $all   Return the extra info of all calls made, instead of only of the last call
	 * @return mixed
	 */
	public function getExtraInfo($type=null, $all=false)
	{
		if (!isset($this->extrainfo)) return null;
		if (!isset($type)) return $all ? $this->extrainfo : end($this->extrainfo);

		if (!$all) {
			$info = null;
			foreach ((array)end($this->extrainfo) as $item) if (strtolower($item->type) == strtolower($type)) $info[] = $item->value;
			return !isset($info) ? null : count($info) == 1 ? reset($info) : $info;
		}
		
		$current = null;
		foreach ($all ? $this->extrainfo : array(reset($this->extrainfo)) as $items) {
			foreach ($items as $item) if (strtolower($item->type) == strtolower($type)) $current[] = $item->value;
		}
		$info[] = !isset($current) ? null : count($current) == 1 ? reset($current) : $current;

		return $info;
	}

	/**
	 * Parse extra info for connection which has been recieved single string.
	 * This is not part of any standard and will therefor only works when the server uses RPC_Server_XMLRPC.
	 *
	 * @return mixed
	 */
	protected function storeExtraInfo()
	{
		$input = $this->connection->getExtraInfo();
		
		if (!isset($input)) {
			$this->extrainfo[] = null;
			return;
		}
		
		if (!is_string($input)) {
			trigger_error("Metadata in other forms that a string is not yet supported", E_USER_NOTICE);
			return;			
		}
		
		$info[] = (object)array('type'=>'_raw_', 'value'=>$input);
		
		$matches = null;
		if (preg_match_all('%<extraInfo>.*?</extraInfo>%is', $input, $matches, PREG_SET_ORDER)) {
			foreach ($matches[0] as $xml) {
				$sxml = new SimpleXMLElement($xml);
				$info[] = (object)array('type'=>(string)$sxml->type, 'value'=>(string)$sxml->value->string);
			}
		}
		
		return $this->extrainfo[] = $info;
	}
	
	/**
	 * Get information about the RPC connection
	 *
	 * @return string
	 */
	public function about()
	{
		return $this->connection->about() . ' (XMLRPC)'; 
	}	
}

?>