<?php
namespace Q;

require_once 'Q/Log.php';

/**
 * Log using a stream.
 * 
 * @package Log
 */
class Log_DB extends Log
{
	/**
	 * Database table
	 * @var DB_Table
	 */
	public $table;

    /**
     * Mapping of keys to field names
     * @var array
     */
    public $fields;	

    
	/**
	 * Class constructor
	 *
	 * @param DB_Table|string $table   DB_Table or table name (string)
	 * @param array           $fields  Mapping of keys to field names
	 */
	public function __construct($table)
	{
        $this->table = $table;
        $this->fields = $fields;
        
	    parent::__construct();
	}
	
	/**
	 * Log a message.
	 *
	 * @param string|array $message  Message or associated array with info
	 * @param string       $type
	 */
	public function write($message, $type=null)
	{
	    if (!isset($type) && is_array($message) && isset($message['type'])) $type = $message['type'];
	    
	    if (isset($this->alias[$type])) $type = $this->alias[$type];
	    if (!$this->shouldLog($type)) return;
		
	    $values = array_merge($this->_eventValues->getAll(), (isset($type) ? array('type'=>$type) : array()), (is_array($message) ? $message : array('message'=>$message)));
	    $store = null; 
	    if (!isset($this->fields)) {
	        $store =& $values;
	    } else {
	        foreach ($this->fields as $key=>$field) $store[$field] = isset($values[$key]) ? $values[$key] : null;
	    }

	    $conn = $this->table instanceof DB_Table ? $this->table->getLink() : DB::i();
	    $conn->store($this->table, $values);
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