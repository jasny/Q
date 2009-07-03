<?php
namespace Q;

require_once 'Q/Log.php';

/**
 * Log using a stream.
 * 
 * @package Log
 */
class Log_Text extends Log
{
	/**
	 * File name
	 * @var string
	 */
	public $file;

	/**
	 * File of stream
	 * @var resource
	 */
	protected $stream_file;
	
	/**
	 * Stream
	 * @var resource
	 */
	protected $stream;

	
	/**
	 * Class constructor
	 *
	 * @param string $file
	 */
	public function __construct($file)
	{
		if (is_resource($file)) $this->stream = $file;
		  else $this->file = $file;

	    parent::__construct();
	}
	
	/**
	 * Write a log line
	 *
	 * @param string $line
	 * @param string $type
	 */
    protected function writeLine($line, $type)
    {
		if ($this->stream_file !== $this->file) {
		    $this->stream = fopen($this->file, 'w+');
		    $this->stream_file = $this->file;
		}
		
		fwrite($this->stream, $line . "\n");
    }
}

?>