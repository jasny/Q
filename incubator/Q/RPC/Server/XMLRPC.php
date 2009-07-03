<?php
namespace Q;

require_once "Q/RPC/Server.php";
require_once "Q/HTTP.php";

/**
 * Base class for any kind of RPC server.
 * 
 * @package RPC
 * @subpackage RPC_Server
 *  
 * @todo Do something with expected exceptions an allow an error handler to handle the rest
 * @todo Allow doing callbacks trough a callback method, instead of alway call public methods of an object
 */
class RPC_Server_XMLRPC extends RPC_Server
{
	/**
	 * Output stream
	 * @var resource
	 */
	public $stream;
	
	/**
	 * Alternative channel.
	 * Defaults to using HTTP header (if available) or STDERR.
	 * 
	 * @var resource
	 */
	public $altStream;


	/**
	 * Class constructor.
	 *
	 * @param array $options
	 */
	public function __construct($options=array())
	{
		$this->stream = !isset($options['stream']) ? STDOUT : (is_resource($options['stream']) ? $options['stream'] : fopen($options['stream']));
		if (isset($options['altstream'])) $this->altStream = is_resource($options['altstream']) ? $options['altstream'] : fopen($options['altstream']);
		 elseif (!HTTP::headers_sendable()) $this->altStream = STDERR; 
	}

	
	/**
	 * Handle RPC request(s).
	 * Fluent interface.
	 *
	 * @param boolean|string $request  RPC request (string), FALSE read from php://input once or TRUE to keep listing on php://input for multiple requests
	 * @return RPC_Server_XMLRPC
	 * 
	 * @todo implement keep alive
	 */
	public function handle($request=false)
	{
		$keep_alive = (int)$request === 1;
		
		if ($keep_alive || empty($request)) {
			$request = "";
			while (($line = fread(STDIN, 1024))) {
				$request .= $line;
				if (substr($request, -1) == "\0") break;
			}
			$request = substr($request, 0, strlen($request)-1);
		}

		try {
			$method = null;
			$args = xmlrpc_decode_request($request, $method);
			
			if (empty($method)) throw new Exception("Invalid XMLRPC request.");
			if (!is_callable(array($this->object, $method))) throw new Exception("RPC call failed: Unable to call method '{$method}'."); 
			
			$result = call_user_func_array(array($this->object, $method), $args);
			$output = xmlrpc_encode_request(null, $result);
			
		} catch (ExpectedException $e) {
			$output = xmlrpc_encode(array("faultCode"=>$e->getCode(), "faultString"=>$e->getMessage()));
			
		} catch (\Exception $e) {
			$output = xmlrpc_encode(array("faultCode"=>-1, "faultString"=>"Unexpected exception."));
			$this->putExtraInfo('X-Exception', $e->getMessage());
			if (class_exists(__NAMESPACE__ . '::ErrorHandler', false) && ErrorHandler::isInit()) ErrorHandler::i()->handleException($e);
		}

		$content_type = HTTP::header_getValue('Content-Type');
		
		if (empty($content_type) || $content_type == 'text/xml') {
			fwrite($this->stream, $output);
		} else {
			if (HTTP::headers_sendable()) {
				if ($keep_alive) {
					trigger_error("Using keep-alive together with sending non-XMLRPC is not allowed. Found header 'Content-Type: $content_type'.", E_USER_WARNING);
					fwrite($this->stream, xmlrpc_encode(array("faultCode"=>0, "faultString"=>"Could not send non-XMLRPC output because of keep-alive.")));
				}
			} else {
				$this->putExtraInfo('Content-Type', $content_type);
			}
		}

		return $this;
	}
	
	
	/**
	 * Put additional information on an alternative channel (eg: http headers or stderr).
	 * Fluent interface.
	 *
	 * @param string $type
	 * @param string $value
	 * @return RPC_Server_XMLRPC
	 */
	public function putExtraInfo($type, $value)
	{
		if (!isset($this->altStream)) {
			HTTP::header("$type: $value", false);
		} else {
			$sxml = new SimpleXMLElement(xmlrpc_encode($value));
			fwrite($this->altStream, "<extraInfo><type>" . htmlentities($type) . "</type>" . $sxml->param->value->asXML() . "</extraInfo>");  			
		}
		
		return $this;
	}
}

?>
