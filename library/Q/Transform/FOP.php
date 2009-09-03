<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Transform.php';

/**
 * Call Apache FOP from PHP
 * 
 * @package Transform
 */
class Transform_FOP extends Transform 
{
	
	/**
	 * Descriptions for error codes
	 * @var array
	 */
	public static $errorDescription = array (E_ERROR => 'Error', E_WARNING => 'Warning', E_PARSE => 'Parse', E_NOTICE => 'Notice', E_CORE_ERROR => 'Core error', E_CORE_WARNING => 'Core warning', E_COMPILE_ERROR => 'Compile error', E_COMPILE_WARNING => 'Compile warning', E_USER_ERROR => 'User error', E_USER_WARNING => 'User warning', E_USER_NOTICE => 'User notice', E_STRICT => 'Strict' );
	
	/**
	 * Non fatal errors
	 * @var array
	 */
	protected $warnings = array ();
	
	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	public function __construct($options = array()) {
		if (! isset ( $options ['file'] ) && isset ( $options [0] )) $options ['file'] = $options [0];
		
		parent::__construct ( $options );
	}
	
	/**
	 * Call Apache FOP from PHP and return the result
	 *
	 * @param string  $data File path
	 * @return string
	 */
	public function process($data = null) {
        if (! file_exists ( $this->file ) || ! is_file ( $this->file ))
            throw new Exception ( "Unable to start the PHP file transformation : File '" . $this->file . "' does not exist, is not accessable (check permissions) or is not a regular file." );
		if (! is_file ( $data )) throw new Exception ( "Unable to start the file transformation : The param specified with 'process' method is not a file." );

		$options = array($data,$this->file);

		$this->startErrorHandler();
		ob_start();
		$java = new Java("org.apache.fop.apps.CommandLine", $options); // it should be org.apache.fop.cli.CommandLine or org.apache.fop.cli.CommandLineOptions ???
		$java->run();
        $contents = ob_get_contents();
        ob_end_clean();
        $this->stopErrorHandler();
        
        return $contents;
	}
	
	/**
	 * Execute a PHP file and output the result
	 *
	 * @param array  $array
	 * @return string
	 */
	public function output($data) {
		echo $this->process ( $data );
	}
	
	/**
	 * Start error handler
	 */
	protected function startErrorHandler() {
		set_error_handler ( array ($this, 'onError' ) );
	}
	
	/**
	 * Stop error handler
	 */
	protected function stopErrorHandler() {
		restore_error_handler ();
		
		$this->retriggerWarnings ();
		$this->warnings = array ();
	}
	
	/**
	 * Error handler callback
	 */
	protected function onError($errno, $errstr, $errfile, $errline) {
		if (! (error_reporting () & $errno))
			return;
		
		if ($errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
			throw new ErrorException ( $errno, 0, $errstr, $errfile, $errline );
		}
		
		$this->warnings [] = array ($errno, $errstr, $errfile, $errline );
	}
	
	/**
	 * Retrigger warnings
	 */
	protected function retriggerWarnings() {
		$warning = null;
		foreach ( $this->warnings as &$warning ) {
			trigger_error ( self::makeErrorMessage ( $warning ), $warning [0] & (E_NOTICE | E_USER_NOTICE | E_STRICT) ? E_USER_NOTICE : E_USER_WARNING );
		}
	}
	
	/**
	 * Make message for an error
	 */
	static protected function makeErrorMessage($errno, $errstr, $errfile, $errline) {
		$errdesc = self::$errorDescription [$errno];
		
		if (! array_key_exists ( 'SHELL', $_SERVER ))
			$msg = "$errdesc: $errstr in $errfile on line $errline";
		else
			$msg = "<b>$errdesc</b>: " . nl2br ( $errstr ) . " in <b>$errfile</b> on line <b>$errline</b>";
		
		$msg = ini_get ( 'error_prepend_string' ) . $msg . ini_get ( 'error_append_string' ) . "\n";
		
		return $msg;
	}
}

