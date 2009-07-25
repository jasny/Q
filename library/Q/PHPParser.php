<?php
namespace Q;

/**
 * Execute PHP file and return output as string.
 * 
 * @package PHPParser
 * 
 * @todo Change to general process class, that supports syntax Process::with('php')->load($file) 
 */
class PHPParser
{
	/**
	 * Descriptions for error codes
	 * @var array
	 */
	static public $errorDescription = array(
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
	protected $warnings = array();
	
	/**
	 * Execute a PHP file and return the output
	 *
	 * @param string $filename
	 * @param array  $variables
	 * @return string
	 */
	static public function load($filename, $variables=null)
	{
		$ob = new self();
		return $ob->_load($filename, $variables);
	}

	
	/**
	 * Class constructor
	 */	
	protected function __construct()
	{
	}
	
	/**
	 * Execute a PHP file and return the output
	 *
	 * @param string $filename
	 * @param array $variables
	 * @return string
	 */
	protected function _load($filename, $variables=null)
	{
		$_filename = $filename;
		$_variables = $variables;
		unset($filename, $variables);
		if (isset($_variables)) extract($_variables);
	
		$this->startErrorHandler();
	
		try {
			ob_start();
			include($_filename);
			$contents = ob_get_contents();
		} catch (Exception $_exception) {
		}
		
		ob_end_clean();
		$this->stopErrorHandler();
	
		if (isset($_exception)) {
			trigger_error("Could not parse file '$_filename'. Uncaught " . get_class($_exception) . ": " . $_exception->getMessage(), E_USER_WARNING);
			return null;
		}
		
		return $contents;
	}
	
	
	/**
	 * Start error handler
	 */
	protected function startErrorHandler()
	{
		set_error_handler(array($this, 'onError'));
	}

	/**
	 * Stop error handler
	 */
	protected function stopErrorHandler()
	{
		restore_error_handler();
		
		$this->retriggerWarnings();
		$this->warnings = array();
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
	protected function retriggerWarnings()
	{
	    $warning = null;
		foreach ($this->warnings as &$warning) {
			trigger_error(self::makeErrorMessage($warning), $warning[0] & (E_NOTICE | E_USER_NOTICE | E_STRICT) ? E_USER_NOTICE : E_USER_WARNING);
		}
	}

	/**
	 * Make message for an error
	 */
	static protected function makeErrorMessage($errno, $errstr, $errfile, $errline)
	{
		$errdesc = self::$errorDescription[$errno];
		
		if (!array_key_exists('SHELL', $_SERVER)) $msg = "$errdesc: $errstr in $errfile on line $errline";
		  else $msg = "<b>$errdesc</b>: " . nl2br($errstr) . " in <b>$errfile</b> on line <b>$errline</b>";
		
		$msg = ini_get('error_prepend_string') . $msg . ini_get('error_append_string') . "\n";		
		
		return $msg;
	}	
}

