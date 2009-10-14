<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Serialize/PHP.php';

/**
 * Execute PHP file and return output as string.
 * 
 * @package Transform
 */
class Transform_Unserialize_PHP extends Transform
{
    /**
     * Default extension for file with unserialized data.
     * @var string
     */
    public $ext = 'php';
    
    /**
     * Descriptions for error codes
     * @var array
     */
    public static $errorDescription = array(
       E_ERROR => 'Error', 
       E_WARNING => 'Warning', 
       E_PARSE => 'Parse', 
       E_NOTICE => 'Notice', 
       E_CORE_ERROR => 'Core error', 
       E_CORE_WARNING => 'Core warning', 
       E_COMPILE_ERROR => 'Compile error', 
       E_COMPILE_WARNING => 'Compile warning', 
       E_USER_ERROR => 'User error', 
       E_USER_WARNING => 'User warning', 
       E_USER_NOTICE => 'User notice', 
       E_STRICT => 'Strict' 
    );
    
    /**
     * Non fatal errors
     * @var array
     */
    protected $warnings = array ();
    
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Serialize_PHP($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
    
    /**
	 * Execute a PHP file and return the output
	 *
	 * @param mixed  $data Data to transform (string or Fs_Node)
	 * @return string
	 */
	public function process($data) 
	{
        if ($this->chainInput) $data = $this->chainInput->process($data);
		
        if (!is_string($data) && !($data instanceof Fs_Node)) throw new Transform_Exception("Wrong parameter type : " . gettype($data) . " given when string should be pass");
            	
		$this->startErrorHandler();

		try {
            ob_start ();
	        if ($data instanceof Fs_Node) {
	            $return = include (string)$data;
	            if ($return === true || $return === 1) {
	            	$data = ob_get_contents();
	            	$return = eval('return ' . $data . ';');
	            }
	        } else {
	        	$return = eval('return ' . $data . ';');
	        }
        } catch (Exception $exception) {
            ob_end_clean ();
            $this->stopErrorHandler ();
            
            throw new Transform_Exception ( "Could not unserialize data", $exception->getMessage());
        }
        
        ob_end_clean ();
        $this->stopErrorHandler ();

        return $return;
	}

    /**
     * Start error handler
     */
    protected function startErrorHandler() 
    {
        set_error_handler ( array ($this, 'onError' ) );
    }
    
    /**
     * Stop error handler
     */
    protected function stopErrorHandler() 
    {
        restore_error_handler ();
        
        $this->retriggerWarnings ();
        $this->warnings = array ();
    }
    
    /**
     * Error handler callback
     */
    protected function onError($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) return;
        
        if ($errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
            throw new \ErrorException($errno, 0, $errstr, $errfile, $errline);
        }
        
        $this->warnings[] = array($errno, $errstr, $errfile, $errline);
    }
    
    
    /**
     * Retrigger warnings
     */
    protected function retriggerWarnings() {
        $warning = null;
        foreach ( $this->warnings as &$warning ) {
            trigger_error(self::makeErrorMessage($warning[0], $warning[1], $warning[2], $warning[3]), $warning[0] & (E_NOTICE | E_USER_NOTICE | E_STRICT) ? E_USER_NOTICE : E_USER_WARNING);
        }
    }
    
    /**
     * Make message for an error
     */
    static protected function makeErrorMessage($errno, $errstr, $errfile, $errline) 
    {
        $errdesc = self::$errorDescription [$errno];
        
        if (array_key_exists ( 'SHELL', $_SERVER ))
            $msg = "$errdesc: $errstr in $errfile on line $errline";
        else
            $msg = "<b>$errdesc</b>: " . nl2br ( $errstr ) . " in <b>$errfile</b> on line <b>$errline</b>";
        
        $msg = ini_get ( 'error_prepend_string' ) . $msg . ini_get ( 'error_append_string' ) . "\n";
        
        return $msg;
    }
	
}