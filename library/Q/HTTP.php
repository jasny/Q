<?php
namespace Q;

/**
 * Yet another static class with HTTP functions.
 * {@internal The header methods are equivilent to the header PHP functions, therefor the names don't follow the normal naming convention.}}
 * 
 * @package HTTP
 */
class HTTP
{
    /** Allow forwared IPs */
    const ORIGINAL_CLIENT = true;
    /** Do not allow forwared IPs */
    const CONNECTED_CLIENT = false;
    
	/**
	 * Known HTTP codes
	 * @var array
	 */
	static public $http_status_codes = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		122 => 'Request-URI too long',

		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',

		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',

		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Unordered Collection',
		426 => 'Upgrade Required',
		449 => 'Retry With',
		450 => 'Blocked',

		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates', 
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended'
	);	

	/**
	 * Default time for cache to expire (in seconds)
	 * @var int
	 */
	static public $default_cache_time = 300;
	
	/**
	 * A list of all headers (created through this interface)
	 * @var array
	 */
	static protected $list_of_headers = array();

	/**
	 * An index for the list of headers
	 * @var array
	 */
	static protected $index_of_headers = array();
	
	/**
	 * A list of all rewrite_vars (created through this interface)
	 */
	static protected $rewrite_vars = array();
	
	
	/**
	 * Check if this script run in shell (and not in webserver).
	 * 
	 * @return bool
	 */	
	static public function inShellMode()
	{
		return !array_key_exists('HTTP_HOST', $_SERVER);
	}

	
	/**
	 * Send a raw HTTP header.
	 * Same as header() function but stores headers in this class, so they can be used even in CLI mode. 
	 *
	 * @param string  $string              The header string
	 * @param boolean $replace             Replace existing header or allow multiple headers of the same type
	 * @param int     $http_response_code  Forces the HTTP response code to the specified value
	 */
	static public function header($string, $replace=true, $http_response_code=null)
	{
		if (!self::inShellMode()) {
			header($string, $replace, $http_response_code);
			return;
		}
		
		$type = preg_replace('/:.*$/', '', $string);
		if ($replace) {
			foreach (array_keys(self::$index_of_headers, $type, true) as $i) {
				unset(self::$list_of_headers[$i], self::$index_of_headers[$i]);
			}
		}
		
		self::$list_of_headers[] = $string;
		end(self::$list_of_headers);
		self::$index_of_headers[key(self::$list_of_headers)] = $type;
		
		if (isset($http_response_code)) self::response($http_response_code);
	}
	
	/**
	 * Returns a list of response headers sent (or ready to send).
	 * Will also work in CLI mode (only with headers set using this interface)
	 * 
	 * @return array
	 */
	static public function headers_list()
	{
		return (!self::inShellMode()) ? headers_list() : self::$list_of_headers;
	}

	/**
	 * Returns a specific response headers sent (or ready to send).
	 * Will return an array if there are multiple headers.
	 * 
	 * @param string $type
	 * @return string|array
	 */
	static public function header_get($type)
	{
		$headers = array();
		
		if (self::inShellMode()) {
			foreach (array_keys(self::$index_of_headers, $type, true) as $i) {
				$headers[] = self::$list_of_headers[$i];
			}
		} else {
			foreach (self::headers_list() as $header) {
				if (preg_match('/^' . preg_quote($type) . '\s*:\s*/i', $header)) $headers[] = $header;
			}
		}
		
		return empty($headers) ? null : (count($headers) == 1 ? reset($headers) : $headers);
	}

	/**
	 * Returns the value of a specific response headers sent (or ready to send).
	 * Will return an array if there are multiple headers.
	 *  
	 * @param string $type
	 * @return string|array
	 */
	static public function header_getValue($type)
	{
		$headers = (array)self::header_get($type);
		if (empty($headers)) return null;
		
		$values = null;
		foreach ($headers as $header) $values[] = preg_replace('/^[^\:]++\:\s*/', '', $header);

		return count($values) == 1 ? reset($values) : $values;
	}
	
	/**
	 * Checks if or where headers have been sent.
	 * If the optional file and line parameters are set, headers_sent() will put the PHP source file name and line number where output started in the file and line variables.
	 *
	 * @param string $file  The file where the output started
	 * @param string $line  The line where the output started
	 * @return boolean
	 */
	static public function headers_sent(&$file, &$line)
	{
		return !self::inShellMode() && headers_sent($file, $line);
	}
	
	/**
	 * Check if it is possible to send headers in general, becasically check that we aren't running in shell mode.
	 *
	 * @return boolean
	 */
	static public function headers_sendable()
	{
		return !self::inShellMode();
	}

	/**
	 * Clear the cached headers, only works in CLI mode
	 *
	 * @return boolean
	 */
	static public function clearHeaders()
	{
		if (!self::inShellMode()) return false;
		
		self::$list_of_headers = null;
		self::$index_of_headers = null;
		return true;
	}
	
	
	/**
	 * Send an HTTP header with a responce code.
	 * 
	 * @param int    $code
	 * @param string $msg   Alternative HTTP responce message (use not not recommended)
	 */
	static public function response($code, $msg=null)
	{
		if (!isset($msg)) $msg = isset(self::$http_status_codes[$code]) ? self::$http_status_codes[$code] : "Unknown";
		self::header("HTTP/1.1 $code $msg");
	}
	
	/**
	 * Redirect client to other URL.
	 * This is done by issuing a "Location" header and exiting if wanted.
	 * 
	 * Returns true on succes (or exits) or false if headers have already been sent.
	 * Will append output rewrite vars.
	 * 
	 * @param string  $url            URL where the redirect should go to.
	 * @param int     $http_response  HTTP status code to use for redirect, valid values are 301, 302, 303 and 307.
	 * @param boolean $exit           Whether to exit immediately after redirection.
	 * @param mixed   $output         Wheter to output a hypertext note where we're redirecting to, boolean or string for custom message.
	 * @return boolean 
	 */
	function redirect($url, $http_response=302, $exit=true, $output=true)
	{
	    if (headers_sent()) return false;
	
	    $vars = self::output_get_rewrite_vars();
	    $location = $url . (!empty($vars) ? (strpos($url, '?') === false ? '?' : '&') . http_build_query($vars) : '');
	     
	    self::header("Location: $location", true, $http_response);
	    
	    if ($output && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'HEAD') {
	        if (!is_string($output)) $output = 'Redirecting to <a href="%s" title="Page redirected">%s</a>';
	    	printf($output, $url, $location);
	    }
	    
	    if ($exit) exit(1);
	    return true;
	}
	
	/**
	 * Output headers to force the client to download the file
	 *
	 * @param string     $filename
	 * @param int        $filesize  Leave NULL to use filesize(), set to FALSE not to include content-length info
	 * @param string|int $modified  Last-modified header as ISO date(string) or timestamp(int)
	 */
	static public function headersDownload($filename, $filesize=null, $modified=null)
	{
		self::header("Pragma: public");
		self::header("Expires: 0");
		self::header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		self::header("Cache-Control: public");
		self::header("Content-Description: File Transfer");
		self::header("Content-Type: application/force-download");
		self::header("Content-Disposition: attachment; filename=\"" . basename($filename) . '"');
		self::header("Content-Transfer-Encoding: binary");
		
		if ($filesize !== false && (isset($filesize) || file_exists($filename))) self::header("Content-Length: ". isset($filesize) ? $filesize : filesize($filename));
		if ($modified !== false && (isset($modified) || file_exists($filename))) {
			if (!isset($modified) || is_int($modified)) $modified = gmdate('D, d M Y H:i:s', isset($modified) ? $modified : filemtime($filename)) . 'GMT';
			self::header("Last-Modified: $modified");
		}
	}
	
	
	/**
	 * Output headers to make sure the request isn't cached by browser
	 */
	static public function noCache()
	{
  		self::header('Cache-Control: no-cache');
  		self::header('Pragma: no-cache');	    
	}
	
	/**
	 * Output headers to make sure the request is cached by browser.
	 * Be careful with using this, since users can get stuck with an old version of the page.  
	 * 
	 * @param string|int $expires   Expires header as ISO date(string) or timestamp 
	 * @param string|int $modified  Last-modified header as ISO date(string) or timestamp
	 */
	static public function forceCache($expires=null, $modified=null)
	{
	    if (defined('DISABLE_HTTP_CACHE') && DISABLE_HTTP_CACHE) return;
	    
	    if (!isset($expires)) $expires = gmdate('D, d M Y H:i:s', time() + self::$default_cache_time) . ' GMT';
	      elseif (is_int($expires)) $expires = gmdate('D, d M Y H:i:s', $expires) . ' GMT';
	    if (is_int($modified)) $modified = gmdate('D, d M Y H:i:s', $modified) . ' GMT';
	    
	    self::header('Cache-Control: public');
  		self::header('Pragma: public');
	    self::header("Expires: $expires");
  		if (isset($modified)) self::header("Last-Modified: $modified");
	}
	
	
	/**
	 * This function adds another name/value pair to the URL rewrite mechanism.
	 * 
	 * The name and value will be added to URLs (as GET parameter) and forms (as hidden input fields) the same way as
	 *  the session ID when transparent URL rewriting is enabled with session.use_trans_sid.
	 *
	 * @param string $name
	 * @param mixed  $value  Also allows array
	 */
	static public function output_add_rewrite_var($name, $value)
	{
	    if (func_num_args() < 3) {
	        self::$rewrite_vars[$name] = $value;
	        $name = urlencode($name);
	        
	        if (isset(self::$rewrite_vars[$name]) && !is_scalar(self::$rewrite_vars[$name])) {
	            $value = self::$rewrite_vars[$name];
	            $cmd = 'clear';
	        } else {
	            $cmd = 'set';
	        }
	    } else {
	        $cmd = func_get_arg(2);
	    }
	    
	    if (is_array($value)) {
	        foreach ($value as $k=>$v) self::output_add_rewrite_var($name . '[' . urlencode($k) . ']', $cmd == 'clear' ? null : $v, $cmd);
	    } else {
	        output_add_rewrite_var($name, $cmd == 'clear' ? null : $value);
	    }
	}

	/**
	 * This function resets the URL rewriter and removes all rewrite variables previously set by the output_add_rewrite_var() function or the session mechanism.
	 */
	static public function output_reset_rewrite_vars()
	{
	    output_reset_rewrite_vars();
	    self::$rewrite_vars = array();
	}
	
	/**
	 * Return a list of rewrite vars.
	 * 
	 * @return array
	 */
	static public function output_get_rewrite_vars()
	{
	    $vars = self::$rewrite_vars;
	    if (ini_get('session.use_trans_sid') && session_id()) $vars[session_name()] = session_id();
	    return $vars;
	}
		
	
    /**
     * Return the client IP.
     *
     * @param boolean $forwarded  Use X-Forwarded-For
     * @return string
     */
    static public function clientIP($forwarded=self::ORIGINAL_CLIENT)
    {
    	$addr = !$forwarded || empty($_SERVER['X-Forwarded-For']) ? $_SERVER['REMOTE_ADDR'] : trim(preg_replace('/,.*$/', '', $_SERVER['X-Forwarded-For']));
    	if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $addr)) $addr = gethostbyname($addr);
    	return $addr;
    }

    /**
     * Return the client hostname.
     *
     * @param boolean $forwarded  Use X-Forwarded-For
     * @return string
     */
    static public function clientHostname($forwarded=self::ORIGINAL_CLIENT)
    {
    	$addr = !$forwarded || empty($_SERVER['X-Forwarded-For']) ? $_SERVER['REMOTE_ADDR'] : trim(preg_replace('/,.*$/', '', $_SERVER['X-Forwarded-For']));
    	if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $addr)) $addr = gethostbyaddr($addr);
    	return $addr;
    }
    
    /**
     * Return the client and proxy IPs.
     * 
     * @return string
     */
    static public function clientRoute()
    {
    	return (!empty($_SERVER['X-Forwarded-For']) ? $_SERVER['X-Forwarded-For'] . ',' : '') . $_SERVER['REMOTE_ADDR'];
    }

    
    /**
     * Get arguments passed to the script as script.php/arg0/arg1/arg2/...
     *
     * @return array
     */
    static public function scriptArgs()
    {
        return explode('/', substr($_SERVER['PHP_SELF'], strlen($_SERVER['SCRIPT_NAME'])+1));
    }
}

