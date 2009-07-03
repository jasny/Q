<?php
namespace Q;

require_once 'Q/Log/FirePHP.php';

/**
 * Collect the given data and send to the FirePHP Firefox Extension as table.
 * 
 * @package Log
 */
class Log_FirePHPTable extends Log_FirePHP
{
    /**
     * Counter to group table data.
     * @var array
     */
    static protected $counter=0;

        
    /**
     * Title of the table
     * @var string
     */
    public $title;

    /**
     * Column headers
     * @var array
     */
    public $columns;
    
    
    /**
     * Beginning 3 numbers of unique, to group table data.
     * @var string
     */
    protected $unique_base;

    /**
     * Counter to make HTTP header unique.
     * @var int
     */
    protected $unique_counter=0;
        
    
    /**
     * Enter description here...
     *
     * @param string $title
     * @param array  $columns  Column headers
     */
    public function __construct($title, $columns=null)
    {
        $this->title = $title;
        if (isset($columns)) $this->columns = $columns;
        
        $this->unique_base = 4000 + (++self::$counter);
        if ($this->unique_base >= 5000) throw new Exception("Unable to create another FirePHPTable log: 1000 is really the limit.");
        
        parent::__construct();
    }
    
	/**
	 * Log a message.
	 *
	 * @param string $message
	 * @param string $type
	 */
	public function write($message, $type=null)
	{
	    if (is_array($message)) {
	        if (!isset($type) && isset($message['type'])) $type = $message['type'];
	          else unset($message['type']);
	    }
	     
	    if (isset($this->alias[$type])) $type = $this->alias[$type];
		if (!$this->shouldLog($type)) return;

        if (HTTP::headers_sent($filename, $linenum)) {
            trigger_error("Headers already sent in {$filename} on line {$linenum}. Cannot send log data to FirePHP. You must have Output Buffering enabled via ob_start() or output_buffering ini directive.", E_USER_NOTICE);
            return;
        }

        try {
            if (!self::initConsole()) return;
            
            $values = array_merge($this->_eventValues->getAll(), (isset($type) ? array('type'=>$type) : array()), (is_array($message) ? $message : array('message'=>$message)));
            if (!isset($this->columns)) $this->columns = array_combine(array_keys($values), array_map('ucfirst', array_keys(array_change_key_case($values, CASE_LOWER))));
            
            foreach (array_keys($this->columns) as $col) {
                $row[] = isset($values[$col]) ? $values[$col] : null; 
            }
            
            if ($this->unique_counter == 0) {
                HTTP::header("X-FirePHP-Data-{$this->unique_base}00000000: [\"{$this->title}\",");
                self::sendMessage(json_encode(array_values($this->columns)), $this->unique_base, $this->unique_counter);
                HTTP::header("X-FirePHP-Data-{$this->unique_base}99999999: ]");
            }
            self::sendMessage(json_encode($row), $this->unique_base, $this->unique_counter);
        } catch (\Exception $e) {
            trigger_error("An exception occured while writing a row to a FirePHP table.\n" . (string)$e, E_USER_WARNING);
        }
	}
	
	/**
	 * Not used.
	 * @ignore
	 *
	 * @param string $message
	 * @param string $type
	 */
	protected function writeLine($message, $type)
	{
	    trigger_error("This method should never be called.", E_USER_NOTICE);
	}
}

?>