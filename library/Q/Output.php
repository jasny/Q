<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * Interface to use output handling.
 * 
 * @package Output
 * @todo Add If-Match and If-Modified-Since support for useCache
 */
class Output
{
    /** Flush on ob_flush, even if section marking is used */
    const IGNORE_MARKERS=1;
    
    /**
     * Default marker name
     * @var string
     */
    public static $defaultMarker='content';
    
    /**
     * Marker stack for sectioned output.
     * @var string
     */
    protected static $markers;
    
	/**
	 * A list of all rewrite_vars (created through this interface)
	 */
	static protected $rewriteVars = array();
	
    
    /**
     * Try to get page from cache, otherwise set cache as output handler and continue.
     * 
     * @param Cache $cache  Cache interface
     */
    static public function useCache($cache)
    {
        load_class('Q\Output_Cache');
        $key = Output_Cache::makeKey();
        
        $data = $cache->get($key);
        if (!$data) {
            new Output_Cache($cache);
            return;
        }
        
        // If the data isn't a string we need to pull it through the output handlers manually.
        if (!is_string($data)) {
            $handlers = ob_list_handlers();
            self::clear();
            
            foreach ($handlers as $handler) {
                $out = call_user_func($handler, $data, PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_END);
                if ($out !== false) $data = $out;
            }
        }
        
        echo $data;
        exit;
    }
    
    /**
     * Transform output before sending it to the client.
     * 
     * @param Transform $transform
     * @param int       $opt         Output handler options
     */
    static public function transform($transform, $opt=0)
    {
        load_class('Q\Output_Transform');
        new Output_Transform($transform, $opt);
    }
    
    /**
     * Clean output buffer and stop all output handling and output buffering. 
     */
    static public function clear()
    {
        for ($i=0, $n=ob_get_level(); $i<$n; $i++) {
            ob_end_clean();
        }
        self::$markers = null;
    }
    
    /**
     * Flush output buffer, sending content to client.
     */
    static public function flush()
    {
        ob_flush();
        flush();
    }
    
    
    /**
     * Mark the beginning of a section.
     * 
     * @param string $marker
     */
    static public function mark($marker)
    {    
        if (ob_get_level() == 0) ob_start();
         else ob_flush();
        
        self::$markers[] = $marker;
    }
    
    /**
     * Marked the end of a section section.
     */
    static public function endMark()
    {
        if (empty(self::$markers)) throw new Exception("Called Output::endMark() without an Output::mark() call.");
        
        ob_flush();
        array_pop(self::$markers);
    }
    
    /**
     * Get the current marker.
     * 
     * @return string
     */
    static public function curMarker()
    {
        if (!isset(self::$markers)) return null;
        
        $marker = end(self::$markers);
        return $marker !== false ? $marker : self::$defaultMarker;
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
	        foreach ($value as $k=>$v) {
	            self::addUrlRewriteVar($name . '[' . urlencode($k) . ']', $cmd == 'clear' ? null : $v, $cmd);
	        }
	    } else {
	        output_add_rewrite_var($name, $cmd == 'clear' ? null : $value);
	    }
	}

	/**
	 * Reset the URL rewriter and remove all rewrite variables previously set by the output_add_rewrite_var() function or the session mechanism.
	 */
	static public function resetUrlRewriteVars()
	{
	    output_reset_rewrite_vars();
	    self::$rewriteVars = array();
	}
	
	/**
	 * Return a list of rewrite vars.
	 * Only return the vars registered through the Q\Output interface are availale.
	 * 
	 * @return array
	 */
	static public function getUrlRewriteVars()
	{
	    $vars = self::$rewriteVars;
	    if (ini_get('session.use_trans_sid') && session_id()) $vars[session_name()] = session_id();
	    return $vars;
	}
}
