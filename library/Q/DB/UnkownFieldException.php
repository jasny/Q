<?php
namespace Q;

class DB_UnkownFieldException extends Exception
{
	/**
	 * Field name
	 * @var string
	 */
	protected $field;
	
	/**
	 * Table name
	 * @var string
	 */
	protected $table;
	
	/**
	 * Class constructor
	 * 
	 * @param string $field    Field name
	 * @param string $table    Table name
	 * @param string $message  Custom message  
	 */
	public function __construct($field, $table=null, $message=null, $code=0)
	{
		$this->field = $field;
		$this->table = $table;
		
		if (!isset($message)) $message = "Field '$field' does not exist" . (isset($table) ? " in table '$table'" : '') . ".";
		parent::__construct($message, $code);
	}
}
