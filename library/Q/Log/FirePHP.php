<?php
namespace Q;

require_once 'Q/Log.php';
require_once 'Q/HTTP.php';

/* ***** BEGIN LICENSE BLOCK *****
 *  
 * This file is part of FirePHP (http://www.firephp.org/).
 * 
 * Software License Agreement (New BSD License)
 * 
 * Copyright (c) 2006-2008, Christoph Dorn
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * 
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 * 
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 * 
 *     * Neither the name of Christoph Dorn nor the names of its
 *       contributors may be used to endorse or promote products derived from this
 *       software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * ***** END LICENSE BLOCK ***** */
 
 
/**
 * Sends the given data to the FirePHP Firefox Extension.
 * The data can be displayed in the Firebug Console or in the "Server" request tab.
 * 
 * For more informtion see: http://www.firephp.org/
 * 
 * @package Log
 */
class Log_FirePHP extends Log
{
    const LOG = 'LOG';
    const INFO = 'INFO';
    const WARN = 'WARN';
    const ERROR = 'ERROR';
    const DUMP = 'DUMP';
    const TRACE = 'TRACE';
    const EXCEPTION = 'EXCEPTION';
    const TABLE = 'TABLE';

    /**
     * Counter to make HTTP header unique.
     * @var int
     */
	protected static $counter = 1; 
	
    /**
	 * Log type to FirePHP type conversion
	 * @var array
	 */
	public $types = array(
		null=>self::LOG,
		'emerg'=>self::ERROR,
		'alert'=>self::ERROR,
		'crit'=>self::ERROR,
		'err'=>self::ERROR,
		'warn'=>self::WARN,
		'info'=>self::INFO,
		'notice'=>self::INFO,
		'debug'=>self::LOG,
	);  
	
	/**
	 * Get the line for logging joining the arguments.
	 *
	 * @param array $args
	 * @return string
	 */
	protected function getLine_Join($args)
	{
	    unset($args['type']);
	    return parent::getLine_Join($args);	
	}
		
    /**
	 * Write a message to FirePHP.
	 *
	 * @param string $message
	 * @param string $type
	 */
	protected function writeLine($message, $type)
	{
        self::fbMessage($message, null, isset($this->types[$type]) ? $this->types[$type] : $this->types[null]);
    }
    
	/**
	 * Log a message.
	 *
	 * @param string|array $message  Message or associated array with info
	 * @param string       $type
	 */
	public function write($message, $type=null)
	{
	    if ($message instanceof \Exception) {
	        self::fbException($message);
	        return;
	    }
	    
	    parent::write($message, $type);
	}
	
    
    /**
     * Set processor url.
     *
     * @param string $url
     */
    public static function setProcessorUrl($url)
    {
        HTTP::header("X-FirePHP-ProcessorURL: $url");
    }
    
    /**
     * Set renderer url.
     *
     * @param string $url
     */
    public static function setRendererUrl($url)
    {
        HTTP::header("X-FirePHP-RendererURL: $url");
    }

    /**
     * Check if FirePHP is installed on client
     *
     * @return boolean
     */
    public static function detectClientExtension()
    {
        $matches = null;
        return isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/\sFirePHP\/([\.|\d]*)\s?/si', $_SERVER['HTTP_USER_AGENT'], $matches) && version_compare($matches[1], '0.0.6', '>=');
    }

    
    /**
     * Escape the filename in a trace
     *
     * @param array $trace
     * @return array
     */
    protected static function escapeTrace($trace)
    {
        foreach ($trace as &$call) {
            $call['file'] = preg_replace('/\\\\+/','\\', $call['file']);
        }
        
        return $trace;
    }
    
    /**
     * Send init console header to the client, which should be initialised for anything exept dump.
     * 
     * @return boolean
     */
    protected static function initConsole()
    {
        if (!self::detectClientExtension()) return false;
        
        $filename = '';
        $linenum = 0;
        if (HTTP::headers_sent($filename, $linenum)) {
            trigger_error("Headers already sent in {$filename} on line {$linenum}. Cannot send log data to FirePHP. You must have Output Buffering enabled via ob_start() or output_buffering ini directive.", E_USER_NOTICE);
            return false;
        }
        
        HTTP::header('X-FirePHP-Data-100000000001: {');
        HTTP::Header('X-FirePHP-Data-300000000001: "FirePHP.Firebug.Console":[');
        HTTP::Header('X-FirePHP-Data-499999999999: ["__SKIP__"]],');
        HTTP::header('X-FirePHP-Data-999999999999: "__SKIP__":"__SKIP__"}');

        return true;
    }

    /**
     * Send the message as header to the client
     *
     * @param string $msg
     * @param string $type
     */
    protected static function sendMessage($msg, $base='3', &$counter=null)
    {
        if (!isset($counter)) $counter =& self::$counter;
        $pad = 12 - strlen($base);
        
        foreach(explode("\n", chunk_split($msg, 5000, "\n")) as $part) {
            if (empty($part)) continue;
            HTTP::header("X-FirePHP-Data-{$base}" . str_pad(++$counter, $pad, '0', STR_PAD_LEFT) . ": $part");
        }
    }

    /**
     * Send message to the client
     *
     * @param string $message
     * @param string $label
     * @param string $type
     * @return boolean
     */
    protected static function fbMessage($message, $label=null, $type=Log_FirePHP::LOG)
    {
        if (!self::initConsole()) return false;

        if (isset($label)) $message = array($label, $message);
        
		self::sendMessage("[\"{$type}\"," . json_encode($message) . "],");
		return true;
    }
    
    /**
     * Send log message to the client
     *
     * @param string $message
     * @param string $label
     * @return boolean
     */
    public static function fbLog($message, $label=null)
    {
        return self::fbMessage($message, $label, self::LOG);
    }
    
    /**
     * Send info message to the client
     *
     * @param string $label    [optional]
     * @param string $message
     * @return boolean
     */
    public static function fbInfo($message, $label=null)
    {
        return self::fbMessage($message, $label, self::INFO);
    }    
    
    /**
     * Send warning to the client
     *
     * @param string $label    [optional]
     * @param string $message
     * @return boolean
     */
    public static function fbWarn($message, $label=null)
    {
        return self::fbMessage($message, $label, self::WARN);
    }
    
    /**
     * Send error message to the client
     *
     * @param string $label    [optional]
     * @param string $message
     * @return boolean
     */
    public static function fbError($message, $label=null)
    {
        return self::fbMessage($message, $label, self::ERROR);
    }

    /** 
     * Send variable dump to the client
     *
     * @param string $key
     * @param mixed  $variable
     * @return boolean
     */
    public static function fbDump($key, $variable)
    {
        if (!self::detectClientExtension()) return false;
        
        if ($key == 'FirePHP.Firebug.Console') $key="_right_";
        
        $filename = '';
        $linenum = 0;
        if (HTTP::headers_sent($filename, $linenum)) {
            trigger_error("Headers already sent in {$filename} on line {$linenum}. Cannot send log data to FirePHP. You must have Output Buffering enabled via ob_start() or output_buffering ini directive.", E_USER_NOTICE);
            return false;
        }
        
        HTTP::header('X-FirePHP-Data-100000000001: {');
    	HTTP::Header('X-FirePHP-Data-200000000001: "FirePHP.Dump":{');
        HTTP::header('X-FirePHP-Data-2' . str_pad(++self::$counter, 11, '0', STR_PAD_LEFT) . ': "' . $key . '":' .json_encode($variable) . ',');
        HTTP::Header('X-FirePHP-Data-299999999999: "__SKIP__":"__SKIP__"},');
        HTTP::header('X-FirePHP-Data-999999999999: "__SKIP__":"__SKIP__"}');

		return true;
    } 
  
    /** 
     * Send trace dump to the client
     *
     * @return boolean
     */
    public static function fbTrace()
    {
        if (!self::initConsole()) return false;
        
        $trace = debug_backtrace();
        if(!$trace) return false;
        
        for ($i=0, $m=sizeof($trace); $i<$m; $i++) {
            if ($trace[$i]['class'] !== __CLASS__) break;
            unset($trace[$i]);
        }

        $trace = self::escapeTrace($trace);		
        $call = array_shift($trace);
		$call['trace'] =& $trace;

        self::sendMessage('["TRACE",' . json_encode($call) . '],');
		return true;
    }
  
	/** 
     * Send exception dump to the client
     *
     * @param Exception $exception
     * @return boolean
     */
    public static function fbException(\Exception $exception)
    {
        if (!self::initConsole()) return false;
        
        $dump = array('class'=>get_class($exception),
                      'message'=>$exception->getMessage(),
                      'file'=>preg_replace('/\\\\+/','\\', $exception->getFile()),
                      'line'=>$exception->getLine(),
                      'type'=>'throw',
                      'trace'=>self::escapeTrace($exception->getTrace()));
        
        self::sendMessage('["EXCEPTION",' . json_encode($dump) . '],');
		return true;
    }

    /**
     * Send data as a table to the client
     *
     * @param string $title
     * @param array $table
     * @return boolean
     */
    public static function fbTable($title, $table)
    {
        return self::fbMessage($table, $title, self::TABLE);
    }
    
    /**
     * Send data to the client.
     *
     * @param Arguments differ per type with last argument as $type.
     * @return boolean
     */
    public static function fb($type)
    {
        $args = func_get_args();
        if (empty($args)) return false;
        
        $type = array_pop($args);
        
        switch (strtoupper($type)) {
            case self::LOG: 
            case self::INFO:
            case self::WARN:
            case self::ERROR:
                if (empty($args)) {
                    $args = array($type);
                    $type = self::LOG;
                }
                return self::fbMessage($args[0], isset($args[1]) ? $args[1] : null, $type);
                
            case self::DUMP:
                if (empty($args)) {
                    trigger_error("Unable to send variable dump to FirePHP: no variable specified.", E_USER_WARNING);
                    return false;
                }
                if (count($args) < 2) array_unshift($args, '_unknown_');
                return self::fbDump($args[0], $args[1]);
                
            case self::TRACE:
                return self::fbTrace();
                
            case self::EXCEPTION:
                if (empty($args)) {
                    trigger_error("Unable to send exception to FirePHP: no exception specified.", E_USER_WARNING);
                    return false;
                }
                return self::fbException($args[0]);
                
            case self::TABLE:
                if (empty($args)) {
                    trigger_error("Unable to send table to FirePHP: no data specified.", E_USER_WARNING);
                    return false;
                }
                if (count($args) < 2) array_unshift($args, 'unnamed table');
                return self::fbTable($args[1], $args[0]);
                
            default:
                if ($args[0] instanceof \Exception) return self::fbException($args[0]);
                return self::fbMessage($args[0], isset($args[1]) ? $args[1] : null, self::LOG);
        }
    }
}

?>