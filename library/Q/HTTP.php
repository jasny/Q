<?php
namespace Q;

/**
 * Yet another static class with HTTP functions.
 * 
 * @package HTTP
 */
class HTTP extends \HttpResponse
{
    /** Allow forwared IPs */
    const ORIGINAL_CLIENT = true;
    /** Do not allow forwared IPs */
    const CONNECTED_CLIENT = false;
    
    
	/**
	 * Default time for cache to expire (in seconds)
	 * @var int
	 */
	static public $defaultCacheTime = 300;
	
	/**
	 * A list of all rewrite_vars (created through this interface)
	 */
	static protected $rewriteVars = array();
	
	/**
     * Path arguments.
     * @var array
     */
    static protected $pathArgs;
	
    /**
     * Parsed request body.
     * @var mixed
     */
    static protected $data;
	
	
	/**
	 * Check if the script is running in shell (and not in webserver).
	 * 
	 * @return boolean
	 */	
	static public function inShellMode()
	{
		return !array_key_exists('HTTP_HOST', $_SERVER);
	}

	/**
	 * Alias of headers_sent() function.
	 * 
	 * @param string $file  Output: The file name where the output started.
	 * @param int    $line  Output: The line number where the output started.
	 */
	static public function isSent(&$file=null, &$line=null)
	{
	    return headers_sent($file, $line);
	}
	
	/**
	 * Redirect client to other URL.
	 * This is done by issuing a "Location" header and exiting if wanted.
	 * 
	 * Returns true on succes (or exits) or false if headers have already been sent.
	 * Will append output rewrite vars.
	 * 
	 * @param string  $url      URL where the redirect should go to.
	 * @param array   $params   Associative array of query parameters
	 * @param boolean $rewrite  Add URL rewrite variables (null is auto-detect) 
	 * @param int     $status   HTTP status code to use for redirect, valid values are 301, 302, 303 and 307.
	 * @return boolean 
	 */
	static public function redirect($url, $params=array(), $rewrite=null, $status=302)
	{
	    if ($rewrite || ($rewrite === null && str_pos(':', $url) === false)) {
	        if (!is_array($params)) $params = (array)$params;
	        $params += self::getUrlRewriteVars();
	    }
	    
	    return parent::redirect($url, $params, false, $status);
	}

	
	/**
	 * This function adds another name/value pair to the URL rewrite mechanism.
	 * 
	 * The name and value will be added to URLs (as GET parameter) and forms (as hidden input fields) the same way as
	 *  the session ID when transparent URL rewriting is enabled with session.use_trans_sid.
	 *
	 * @param string $name
	 * @param mixed  $value  Any scalar value or array.
	 */
	static public function addUrlRewriteVar($name, $value)
	{
	    if (func_num_args() < 3) {
	        self::$rewriteVars[$name] = $value;
	        $name = urlencode($name);
	        
	        if (isset(self::$rewriteVars[$name]) && !is_scalar(self::$rewriteVars[$name])) {
	            $value = self::$rewriteVars[$name];
	            $cmd = 'clear';
	        } else {
	            $cmd = 'set';
	        }
	    } else {
	        $cmd = func_get_arg(2);
	    }
	    
	    if (is_array($value)) {
	        foreach ($value as $k=>$v) self::addUrlRewriteVar($name . '[' . urlencode($k) . ']', $cmd == 'clear' ? null : $v, $cmd);
	    } else {
	        output_add_rewrite_var($name, $cmd == 'clear' ? null : $value);
	    }
	}

	/**
	 * This function resets the URL rewriter and removes all rewrite variables previously set by the output_add_rewrite_var() function or the session mechanism.
	 */
	static public function resetUrlRewriteVars()
	{
	    output_reset_rewrite_vars();
	    self::$rewriteVars = array();
	}
	
	/**
	 * Return a list of rewrite vars.
	 * Only return the vars registered through the Q\HTTP interface are availale.
	 * 
	 * @return array
	 */
	static public function getUrlRewriteVars()
	{
	    $vars = self::$rewriteVars;
	    if (ini_get('session.use_trans_sid') && session_id()) $vars[session_name()] = session_id();
	    return $vars;
	}
		
	
    /**
     * Return the client IP.
     *
     * @param boolean $forwarded  Use X-Forwarded-For
     * @return string
     */
    static public function getClientIp($forwarded=self::ORIGINAL_CLIENT)
    {
        if (empty($_SERVER['REMOTE_ADDR'])) return null;
        
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
    static public function getClientHostname($forwarded=self::ORIGINAL_CLIENT)
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) return null;
        
    	$addr = !$forwarded || empty($_SERVER['X-Forwarded-For']) ? $_SERVER['REMOTE_ADDR'] : trim(preg_replace('/,.*$/', '', $_SERVER['X-Forwarded-For']));
    	if (preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $addr)) $addr = gethostbyaddr($addr);
    	return $addr;
    }
    
    /**
     * Return the client and proxy IPs.
     * 
     * @return string
     */
    static public function getClientRoute()
    {
    	if (!isset($_SERVER['REMOTE_ADDR'])) return null;
        return (!empty($_SERVER['X-Forwarded-For']) ? $_SERVER['X-Forwarded-For'] . ',' : '') . $_SERVER['REMOTE_ADDR'];
    }

    
    /**
     * Get 'Almost pretty' arguments, passed to the script as 'script.php/arg0/arg1/arg2/...'.
     *
     * @return array
     */
    static public function getPathArgs()
    {
        if (isset(self::$pathArgs)) return self::$pathArgs;
        
        self::$pathArgs = explode('/', substr($_SERVER['PHP_SELF'], strlen($_SERVER['SCRIPT_NAME'])+1));
        return self::$pathArgs;
    }
    
    /**
     * Get the raw request body (e.g. POST or PUT data). 
     * 
     * {@internal HttpResponse::getRequestBody() hangs the scripts with fcgi and keepalive. We need to
     *  find out the cause and report a bug. Until that time, use the overloaded funcion.}}
     *  
     * @return string
     */
    static public function getRequestBody()
    {
        return @file_get_contents('php://input');
    }
    
    /**
     * Get parsed PUT/POST data.
     * To get raw data, just use HttpResponse::getRequestBody().
     * 
     * @return mixed
     */
    static public function getRequestData()
    {
        if (isset(self::$data)) return self::$data;
        
        if (!empty($_POST)) {
            self::$data =& $_POST;
            return self::$data;
        }

        $input = self::getRequestBody();
        $contenttype = trim(preg_replace('/;.*$/', '', $_SERVER['CONTENT_TYPE']));

        switch ($contenttype) {
            case 'application/json':
                if (empty($input)) return null;
                self::$data = json_decode($input);
                if (!isset(self::$data)) throw new InputException("Request body doesn't appear to be valid JSON.");
                break;
            default: throw new InputException("Unsupported Content-Type '$contenttype'.");
        }
        
        return self::$data;
    }
}

