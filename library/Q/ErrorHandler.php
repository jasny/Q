<?php
namespace Q;

require_once 'Q/HTTP.php';
require_once 'Q/Log.php';
require_once 'Q/ErrorHandler/Logs.php';

define('E_FATAL', E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR);
define('E_SERIOUS', E_WARNING | E_USER_WARNING | E_COMPILE_WARNING | E_CORE_WARNING | E_RECOVERABLE_ERROR);

/**
 * Error handler. Handle errors properly, instead of just printing them to screen.
 * 
 * By default the error handler acts almost the same way as the PHP would do without it.  
 * Regardless of the settings, this class will still uphold the error_reporting setting.
 * 
 * @package ErrorHandler
 */
class ErrorHandler
{
	/**
	 * Singleton instance
	 * @var ErrorHandler
	 */
	private static $instance;
	
	/**
	 * Descriptions for error codes
	 * @var array
	 */
	static protected $errorDescriptions = array(
		0=>"Unknown error type",
		E_ERROR => 'Fatal error',
		E_WARNING => 'Warning',
		E_PARSE => 'Parse error',
		E_NOTICE => 'Notice',
		E_RECOVERABLE_ERROR => 'Recoverable error',
		E_CORE_ERROR => 'Core error',
		E_CORE_WARNING => 'Core warning',
		E_COMPILE_ERROR => 'Compile error',
		E_COMPILE_WARNING => 'Compile warning',
		E_USER_ERROR => 'Fatal error',
		E_USER_WARNING => 'Warning',
		E_USER_NOTICE => 'Notice',
		E_STRICT => 'Strict'
	);

	/**
	 * Log types for error codes
	 * @var array
	 */
	static protected $errorLogTypes = array(
		0=>'log',
		E_ERROR => 'err',
		E_WARNING => 'warn',
		E_PARSE => 'err',
		E_NOTICE => 'notice',
		E_RECOVERABLE_ERROR => 'err',
		E_CORE_ERROR => 'crit',
		E_CORE_WARNING => 'warn',
		E_COMPILE_ERROR => 'crit',
		E_COMPILE_WARNING => 'warn',
		E_USER_ERROR => 'err',
		E_USER_WARNING => 'warn',
		E_USER_NOTICE => 'notice',
		E_STRICT => 'notice'
	);
	
	
	/**
	 * Flags (binary set) to state the error handler is started
	 * @var int
	 */
	protected $started = 0;

	/**
	 * Disable php config setting 'display_errors'.
	 * @var boolean
	 */
	protected $disableDisplayErrors = true;

	/**
	 * Disable php config setting 'log_errors'.
	 * This does not affect logging through Q/Log interfaces.
	 * @var boolean
	 */
	protected $disableLogErrors = false;
	
	/**
	 * Stack of errors/exceptions which are currently being handled.
	 * Used to detect dead-loops by errors in error handling.
	 * @var array
	 */
	protected $currentErrors = array();
	
	/**
	 * The log handlers that should be called when an error occurs. 
	 * @var Q\ErrorHandler_Logs  
	 */
	protected $_logs;
	
	
	/**
	 * A page to redirect to upon an error (message will not be displayed).
	 * @var string
	 */
	public $errorPage;

	/**
	 * Tags that will be parsed into message.
	 * @var array
	 */
	public $messageVars = array(
	    'system'=>null,
		'admin'=>"the administrator",
		'email'=>null
	);
	
	/**
	 * General message when an error occured.
	 * Tags from ErrorHandler::i()->messageVars will parsed into message.
	 * 
	 * @var string
	 */
	public $errorMessage = "A system error has occured.\nYou may contact %{admin} by sending an e-mail to <a href=\"mailto:%{email}>%{email}</a>.";
	
	/**
	 * Set the HTTP status when a fatal error occurs.
	 * @var int
	 */
	public $httpError = 500;

	/**
	 * Set the HTTP status when an expected exception is thrown.
	 * @var int
	 */
	public $httpExecpted = 400;
	
	/**
	 * Do not log repeated messages. Repeated errors must occur in the same file on the same line.
	 * @var boolean
	 */
	public $ignoreRepeatedErrors; 
	
	
	/**
	 * Get equivilent E_USER_% for a system error
	 *
	 * @param int $errno  Error number. E_%
	 * @return int
	 */
	static public function userErrorEquivilent($errno)
	{
		switch (true) {
			case $errno & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR): $errno = E_USER_ERROR; break;
			case $errno & (E_WARNING | E_CORE_WARNING | E_COMPILE_WARNING): $errno = E_USER_WARNING; break;
			case $errno & E_NOTICE: $errno = E_USER_NOTICE; break;
		}
		
		return $errno;
	}
	
	/**
	 * Get description for an error type.
	 *
	 * @param int $errno  Error number. E_%
	 * @return string
	 */
	static public function errorDescription($errno)
	{
		return isset(self::$errorDescriptions[$errno]) ? self::$errorDescriptions[$errno] : self::$errorDescriptions[0];
	}
	
	
	/**
	 * Singleton method
	 * 
	 * @return ErrorHandler
	 */
	static public function i()
	{
		if (!isset(self::$instance)) self::$instance = new self();
		return self::$instance;
	}

	
	/**
	 * Class constructor
	 */
	protected function __construct()
	{
	    $this->messageVars['system'] = $_SERVER['SERVER_NAME'];
	    $this->messageVars['email'] = 'info@' . $_SERVER['SERVER_NAME'];
	    
		$this->_logs = new ErrorHandler_Logs();
	    $this->ignoreRepeatedErrors = ini_get('ignore_repeated_errors');
		
		ClassConfig::extractBin($this);
	}
	
	/**
	 * Set error and exception handler.
	 */
	public function start()
	{
		if (!($this->started & 1)) {
		    set_error_handler(array($this, "onError"));
		    if ($this->disableDisplayErrors) ini_set('display_errors', 0);
		    if ($this->disableLogErrors) ini_set('log_errors', 0);
		}
		
		if (!($this->started & 2)) set_exception_handler(array($this, "onException"));

		$this->started = 3;
	}

	/**
	 * Set error and exception handler
	 */
	public function stop()
	{
		if ($this->started & 1) restore_error_handler();
		if ($this->started & 2) restore_exception_handler();

		$this->started = 0;
	}
	
	/**
	 * Check if error handler is started.
	 * If first bit (1) is enabled it means it performs error handling
	 * If second bit (2) is enabled it means it performs exception handling
	 *
	 * @return int  
	 */
	public function isStarted()
	{
		return $this->started;
	}
	
	/**
	 * Log a message for an error or exception.
	 *
	 * @param mixed $type     Error number (int) or Exception object
	 * @param mixed $message
	 */
	protected function log($type, $message=null)
	{
	    if (is_object($type)) {
	        $logtype = 'err';
	        $message = $type;
	    } else {
            $logtype = isset(self::$errorLogTypes[$type]) ? self::$errorLogTypes[$type] : self::$errorLogTypes[0];
	    }
	    
	    foreach ($this->_logs->getLogs($type) as $log) {
	        $log->log($message, $logtype);
	    }
	}
	
	
	/**
	 * Error handler for triggered errors.
	 *
	 * @param int    $errno    Error number. E_%
	 * @param string $errstr   Error message
	 * @param string $errfile  File where error occured
	 * @param int    $errline  Line where error occured
	 */
	protected function onError($errno, $errstr, $errfile, $errline)
	{
		self::$lastError = array('type'=>$errno, 'message'=>$errstr, 'file'=>$errfile, 'line'=>$errline);
		if (!(error_reporting() & $errno)) return;
	    
		if (count($this->currentErrors) > 100 || array_search("$errfile#$errline/$errno:$errstr", $this->currentErrors) !== false) {
		    // There is an error in error handling or recurring errors should be ignored. The error already logged once, so just ignore.
		    return;
		}
		$this->currentErrors[] = "$errfile#$errline/$errno:$errstr";
		
		$this->log($errno, array('error'=>self::errorDescription($errno), 'message'=>$errstr, 'file'=>$errfile, 'line'=>$errline, 'trace'=>debug_backtrace()));
		if ($errno & E_FATAL) $this->exitOnError();
		
		if (!$this->ignoreRepeatedErrors) array_pop($this->currentErrors);
	}
	
	/**
	 * Error handler for uncaught exceptions.
	 *
	 * @param Exception $exception
	 */
	protected function onException(\Exception $exception)
	{
	    $curerr = $exception->getFile()  . '#' . $exception->getLine() . '/' . get_class($exception) . '(' . $exception->getCode() . ')';
		if (array_search($curerr, $this->currentErrors) !== false) {
		    // There is an error in error handling or recurring errors should be ignored. The error already logged once, so just ignore.
		    return;    
		}
		$this->currentErrors[] = $curerr;
		
		$this->handleException($exception);
		$this->exitOnError();
	}


	/**
	 * Display/log an exception (does not exit).
	 * Use to log caught exceptions. 
	 *
	 * @param Exception $exception
	 * @param string    $message
	 */
	public function handleException(\Exception $exception, $message=null)
	{
	    if (isset($message)) setExceptionMessage($exception, $message . "\n", MESSAGE_PREPEND);
        $this->log($exception);
	}
	
	/**
	 * Redirect to error page OR show error message and exit.
	 *
	 * @param boolean $logged  The error has succesfully been logged
	 */	
	public function exitOnError($expected)
	{
		$msg = "";
		if (!empty($this->errorPage)) {
		    if ($_SERVER['PHP_SELF'] != $this->errorPage) {
		        HTTP::redirect($this->errorPage, 307);
		        // Will only continue if redirect is not possible, eg if headers are already sent.
		    } else {
	            $msg = "\n\nAdditionally an error occured while processing the error page.";
		    }
		}
		
		
		echo $this->parseMessage($this->errorMessage . $msg);
		exit(1);
	}
	
	/**
	 * Parse error message.
	 *
	 * @param string $message
	 * @return string
	 */	
	protected function parseMessage($message)
	{
		$replace = array();
		foreach (array_keys($args) as $key) $replace[] = '{$' . $key . '}';
		if (!HTTP::inShellMode()) $msg = nl2br($msg);
		return str_ireplace($replace, array_values($args), $message);
	}


    /**
     * Magic method to get a property.
     * Used to protect the logs property.
     *
     * @param string $var
     * @return mixed
     */
    public function __get($var)
    {
        if (strtolower($var) == 'logs') return $this->_logs;
        trigger_error('Undefined property: ' . __CLASS__ . "::$var", E_USER_NOTICE); 
    }
    
    /**
     * Magic method to set a property.
     * Used to protect the logs property.
     * 
     * @param string $var
     * @param mixed  $value
     * @return mixed
     */
    public function __set($var, $value)
    {
        if (strtolower($var) == 'logs') {
            if ($var instanceof ErrorHandler_Logs) {
                $this->_logs = $var;
            } elseif (!is_array($value) && !($value instanceof \ArrayAccess)) {
                trigger_error("Property logs can only be an array, not a " . gettype($value) . ".", E_USER_WARNING);
                return;
            }
            
            $this->_logs = new ErrorHandler_Logs($value);
        }
        
        $this->$var = $value; 
    }    
}

?>