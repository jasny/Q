<?php
namespace Q;

require_once 'DB/Exception.php';

/**
 * An execption when a foreign key constraint prevents modifying or deleting a row.
 * @package DB
 */
class DB_ConstraintException extends DB_Exception
{
	/**
	 * Target table
	 * @var string
	 */
	protected $target_table;

	/**
	 * Target fieldname
	 * @var string
	 */
	protected $target_field;
	
	/**
	 * Foreign table
	 * @var string
	 */
	protected $foreign_table;

	/**
	 * Foreign fieldname
	 * @var string
	 */
	protected $foreign_field;
	
	
	/**
	 * Class constructor 
	 * 
	 * @param string $error          Error message
	 * @param string $errno          Error code
	 * @param string $target_table
	 * @param string $target_field
	 * @param string $foreign_table
	 * @param string $foreign_field
	 */
	public function __construct($error, $errno=0, $target_table=null, $target_field=null, $foreign_table=null, $foreign_field=null)
	{
		parent::construct($error, $errno);
		
		$this->target_table = $target_table;
		$this->target_field = $target_field;
		$this->foreign_table = $foreign_table;
		$this->foreign_field = $foreign_field;
	}

	/**
	 * Get table that is referenced in the foreign key.
	 * This is the table which the row should have been modified or deleted.
	 * 
	 * @return string
	 */
	public function getTargetTable()
	{
		return $this->target_table;
	}
	
	/**
	 * Get the fieldname that is referenced in the foreign key.
	 * 
	 * @return string
	 */
	public function getTargetField()
	{
		return $this->target_field;
	}
	
	/**
	 * Get the table that holds the constraint.
	 * 
	 * @return string
	 */
	public function getForeignTable()
	{
		return $this->foreign_table;
	}
	
	/**
	 * Get the field used in foreign key constraint.
	 * 
	 * @return string
	 */
	public function getForeignField()
	{
		return $this->foreign_field;
	}
}
