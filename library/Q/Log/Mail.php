<?php
namespace Q;

require_once 'Q/Log.php';

/**
 * Log by sending an e-mail.
 * 
 * Other properties are set as additional header (eg. $log->from, $log->bcc, $log->reply_to). 
 * 
 * @package Log
 */
class Log_Mail extends Log
{
    /**
     * E-mail recipient
     * @var string
     */
    public $to;

    /**
     * E-mail subject.
     * Will insert event variables and type (not event values). 
     *  
     * @var string
     */
    public $subject = 'Log message from {$application}';

    /**
     * Additional headers.
     * Will insert event variables and type (not event values).
     * 
     * @var array
     */
    public $headers = array();
        
    /**
     * E-mail recipient
     * @var string
     */
    public $combine = false;
    

	/**
	 * The template of the message or a delimiter.
	 * '{$KEY}' will be replaced to a event value or variable.
	 * 
	 * @var string
	 */
	public $format="\n";

	/**
	 * Make sure each log event is on a single line.
	 * @var string
	 */
	public $singleline = false;	

	/**
	 * The delimiter between messages for combine mode.
	 * @var string
	 */
	public $messageDelimiter = "\n---\n\n";	
    
    /**
     * Header of each message.
     * @var string
     */
    public $msgHeader;

    /**
     * Footer of each message.
     * @var string
     */
    public $msgFooter;
    

    /**
     * Messages to be send, when combine is enbaled.
     * @var array
     */
    protected $messages = array();
    
    
    /**
     * Class constructor
     *
     * @param string $to  E-mail recipient
     */
    public function __construct($to)
    {
        $this->to = $to;
        
        $this->headers['From'] = ini_get('sendmail_from');
        if (empty($this->headers['From'])) $this->headers['From'] = 'system@' . (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : php_uname('n'));
        
	    parent::__construct();
    }
    
    /**
     * Class destructor
     */
    public function __destruct()
    {
        $this->flush();
    }

	/**
	 * Generate an email message (which is stored or send).
	 *
	 * @param string $message
	 * @param string $type
	 */
	protected function writeLine($message, $type)
	{
	    if ($this->combine) {
	        $this->messages[] = $message;
	        return;
	    }
	    
	    $this->send($message, $type);
	}
	
	/**
	 * Send out waiting messages (only in combine mode)
	 */
	public function flush()
	{
        if (!empty($this->messages)) $this->send(join($this->messageDelimiter, $this->messages));
	}
	
	/**
	 * Send the message.
	 *
	 * @param string $message
	 * @param string $type
	 */
	protected function send($message=null, $type=null)
	{
	    try {
	        $quote = $this->quote; $this->quote = false; // Don't quote
	        $formatValue = $this->formatValue; $this->formatValue = null; // Don't format values
	        $singleline = $this->singleline; $this->singleline = true; // Force single line
	    
	        $message = $this->msgHeader . $message . $this->msgFooter;
	        $subject = $this->getLine_Parse(array('type'=>$type), $this->subject);

	        $headers = "";
	        foreach ($this->headers as $key=>$value) {
	            $headers .= "$key: " . $this->getLine_Parse(array('type'=>$type), $value) . "\r\n";
	        }
	        
	    } catch (Exception $e) {
	        if (!isset($subject)) $subject = $this->subject;
	        trigger_error('Exception while preparing logging email: ' . (string)$e, E_USER_WARNING);
	    }

	    $this->quote = $quote;
	    $this->formatValue = $formatValue;
	    $this->singleline = $singleline;
	    
	    mail($this->to, $subject, $message, $headers);
	}
	
	/**
	 * Magic function to set additional headers.
	 * 
     * @param string $var
     * @param mixed  $value
     */
    public function __set($var, $value)
    {
	    if (strtolower($var) == 'eventvalues') {
	        parent::__set($var, $value);
	        return;
	    }
	    
	    $var = preg_replace('/\b[a-z]/e', "strtoupper('$0')", strtolower(str_replace('_', '-', $var)));
	    $this->headers[$var] = $value;
    }
	
	/**
	 * Magic function to get additional headers.
	 * 
     * @param string $var
     * @return mixed
     */
    public function __get($var)
    {
	    if (strtolower($var) == 'eventvalues') return $this->_eventValues;
	    
	    $var = preg_replace('/\b[a..z]/e', "strtoupper('$0')", strtolower(str_replace('_', '-', $var)));
	    return isset($this->headers[$var]) ? $this->headers[$var] : null;
	}
}

?>