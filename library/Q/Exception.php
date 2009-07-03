<?php
namespace Q;

/**
 * Base exception class for the Q framework.
 */
class Exception extends \Exception 
{
	/**
	 * The exception that caused this exception to be thrown
	 *
	 * @var Exception
	 */
    protected $cause;

    /**
     * Supported signatures:
     * Exception(string $message);
     * Exception(string $message, int $code);
     * Exception(string $message, Exception $cause);
     * Exception(string $message, Exception $cause, int $code);
     * Exception(string $message, array $causes);
     * Exception(string $message, array $causes, int $code);
     * 
     * @param  string  $message
     * @param  mixed   $p2
     * @param  mixed   $p3
     */
    public function __construct($message, $p2 = null, $p3 = null)
    {
        if (is_int($p2)) {
            $code = $p2;
            $this->cause = null;
        } elseif ($p2 instanceof Exception || is_array($p2)) {
            $code = $p3;
            if (is_array($p2) && isset($p2['message'])) {
                // fix potential problem of passing in a single warning
                $p2 = array($p2);
            }
            $this->cause = $p2;
        } else {
            $code = null;
            $this->cause = null;
        }
        parent::__construct($message, $code);
    }

    /**
     * Returns the exception that caused this exception to be thrown
     * 
     * @return Exception|array
     */
    public function getCause()
    {
        return $this->cause;
    }

	/**
     * Function must be public to call on caused exceptions
     * 
     * @param  array
     * @return string
     */
    function getCauseMessage(&$causes)
    {
        $trace = $this->getTrace();
        $cause = array('class'   => get_class($this),
                       'message' => $this->getMessage(),
                       'file' => 'unknown',
                       'line' => 'unknown');
        if (isset($trace[0])) {
            if (isset($trace[0]['file'])) {
                $cause['file'] = $trace[0]['file'];
                $cause['line'] = $trace[0]['line'];
            }
        }

        $causes[] = $cause;

        if ($this->cause instanceof Exception) {
            $this->cause->getCauseMessage($causes);
        } elseif ($this->cause instanceof Exception) {
	        $causes[] = array('class'   => get_class($this->cause),
	                       'message' => $this->cause->getMessage(),
	                       'file' => $this->cause->getFile(),
	                       'line' => $this->cause->getLine());
    	}

        if (is_array($this->cause)) {
            foreach ($this->cause as $cause) {
                if ($cause instanceof Exception || $cause instanceof Exception) {
                    $cause->getCauseMessage($causes);
                } elseif ($cause instanceof Exception) {
			        $causes[] = array('class'   => get_class($cause),
			                       'message' => $cause->getMessage(),
			                       'file' => $cause->getFile(),
			                       'line' => $cause->getLine());
                } elseif (is_array($cause) && isset($cause['message'])) {
                    // PEAR_ErrorStack warning
                    $causes[] = array(
                        'class' => $cause['package'],
                        'message' => $cause['message'],
                        'file' => isset($cause['context']['file']) ? $cause['context']['file'] : 'unknown',
                        'line' => isset($cause['context']['line']) ? $cause['context']['line'] : 'unknown',
                    );
                } else {
                	$causes[] = array('class'=>null, 'message'=>$cause, 'file'=>null, 'line'=>null);
                }
            }
        }
    }

    /**
     * Return the class from where the exception was thrown
     *
     * @return string
     */
    public function getErrorClass()
    {
        $trace = $this->getTrace();
        return $trace ? $trace[0]['class'] : null;
    }

    /**
     * Return the function from where the exception was thrown
     *
     * @return string
     */
    public function getErrorMethod()
    {
        $trace = $this->getTrace();
        return $trace ? $trace[0]['function'] : null;
    }

    
    /**
     * Return a HTML represention of the exception
     *
     * @return string
     */
    public function toHtml()
    {
        $trace = $this->getTrace();
        $causes = array();
        $this->getCauseMessage($causes);
        $html =  '<table border="1" cellspacing="0">' . "\n";
        foreach ($causes as $i => $cause) {
            $html .= '<tr><td colspan="3" bgcolor="#ff9999">'
               . str_repeat('-', $i) . ' <b>' . $cause['class'] . '</b>: '
               . htmlspecialchars($cause['message']) . ' in <b>' . $cause['file'] . '</b> '
               . 'on line <b>' . $cause['line'] . '</b>'
               . "</td></tr>\n";
        }
        $html .= '<tr><td colspan="3" bgcolor="#aaaaaa" align="center"><b>Exception trace</b></td></tr>' . "\n"
               . '<tr><td align="center" bgcolor="#cccccc" width="20"><b>#</b></td>'
               . '<td align="center" bgcolor="#cccccc"><b>Function</b></td>'
               . '<td align="center" bgcolor="#cccccc"><b>Location</b></td></tr>' . "\n";

        foreach ($trace as $k => $v) {
            $html .= '<tr><td align="center">' . $k . '</td>'
                   . '<td>';
            if (!empty($v['class'])) {
                $html .= $v['class'] . $v['type'];
            }
            $html .= $v['function'];
            $args = array();
            if (!empty($v['args'])) {
                foreach ($v['args'] as $arg) {
                    if (is_null($arg)) $args[] = 'null';
                    elseif (is_array($arg)) $args[] = 'Array';
                    elseif (is_object($arg)) $args[] = 'Object('.get_class($arg).')';
                    elseif (is_bool($arg)) $args[] = $arg ? 'true' : 'false';
                    elseif (is_int($arg) || is_double($arg)) $args[] = $arg;
                    else {
                        $arg = (string)$arg;
                        $str = htmlspecialchars(substr($arg, 0, 16));
                        if (strlen($arg) > 16) $str .= '&hellip;';
                        $args[] = "'" . $str . "'";
                    }
                }
            }
            $html .= '(' . implode(', ',$args) . ')'
                   . '</td>'
                   . '<td>' . (isset($v['file']) ? $v['file'] : 'unknown')
                   . ':' . (isset($v['line']) ? $v['line'] : 'unknown')
                   . '</td></tr>' . "\n";
        }
        $html .= '<tr><td align="center">' . ($k+1) . '</td>'
               . '<td>{main}</td>'
               . '<td>&nbsp;</td></tr>' . "\n"
               . '</table>';
        return $html;
    }

    /**
     * Return a text represention of the exception
     *
     * @return string
     */
    public function toText()
    {
        $causes = array();
        $this->getCauseMessage($causes);
        $causeMsg = '';
        foreach ($causes as $i=>$cause) {
            $causeMsg .= str_repeat(' ', $i) . $cause['class'] . ': ' . $cause['message'] . ' in ' . $cause['file'] . ' on line ' . $cause['line'] . "\n";
        }
        return $causeMsg . $this->getTraceAsString();
    }
    
    /**
     * Return a text represention of the exception.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toText();
    }   
}

?>