<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Transform.php';

/**
 * Execute PHP file and return output as string.
 * 
 * @package Transform
 */
class Transform_PHP extends Transform 
{
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
	 * Class constructor
	 * 
	 * @param array $options Specific options: array('file'=>file_path) or array(file_path)
	 */
	public function __construct($options = array()) 
	{
		if (! isset ( $options ['file'] ) && isset ( $options [0] )) $options ['file'] = $options [0];
		
		parent::__construct ( $options );	
	}
	
	/**
	 * Execute a PHP file and return the output
	 *
	 * @param array  $data Data to transform
	 * @return string
	 */
	public function process($data = null) 
	{
        if (!isset($this->file) || !file_exists ( $this->file ) || !is_file($this->file)) throw new Exception ( "Unable to start the PHP file transformation : File '" . $this->file . "' does not exist, is not accessable (check permissions) or is not a regular file." );
		
        if ($this->chainNext) $data = $this->chainNext->process($data);
        
        if (! is_array ( $data )) throw new Exception ( "Unable to start the PHP file transformation : The param specified with process is not an array." );
		
		${chr(7) . 'variables'} = $data;
		unset ( $data );
		if (!empty(${chr(7) . 'variables'})) extract(${chr(7) . 'variables'});
		unset(${chr(7) . 'variables'});
		$this->startErrorHandler ();
		
		try {
			ob_start ();
			include ($this->file);
			$contents = ob_get_contents ();
		} catch ( Exception $exception ) {
			ob_end_clean ();
			$this->stopErrorHandler ();
			
			throw new Exception ( "Could not parse file '{$this->file}'.", $exception);
		}
		
		ob_end_clean ();
		$this->stopErrorHandler();
		
		return $contents;
	}
	
	/**
	 * Execute a PHP file and output the result
	 *
	 * @param array  $array
	 * @return string
	 */
	public function output($data) 
	{
		echo $this->process($data);
	}
	
    /**
     * Do the transformation and save the result into a file.
     *
     * @param sting $filename File name
     * @param mixed $data     Data to tranform
     */
    public function save($filename, $data) 
    {
        if(!file_put_contents($filename, $this->process($data))) throw new Exception("Unable to create file {$filename}");        
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

