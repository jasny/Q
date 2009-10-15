<?php
namespace Q;

/**
 * Interface to indicate object is a field of composite of fields
 *
 * @package DB
 */
interface DB_FieldAccess extends Countable
{
	/**
	 * Cast field to fieldname.
	 * 
	 * @return string
	 */
	public function __toString();
	
	
	/**
	 * Get the database connection.
	 * 
	 * @return DB
	 */
	public function getConnection();

	/**
	 * Get table, result or record containing this field.
	 * 
	 * @return DB_Table|DB_Result|DB_Record
	 */
	public function getParent();
		
	/**
	 * Get the mode of the field.
	 * 
	 * @return int
	 */
	public function getMode();

	/**
	 * Create an active field based on this field definition.
	 *
	 * @param mixed       $value
	 * @param Q\DB_Record $parent
	 * @param boolean     $default  Set value to default
	 * @return DB_Field
	 */
	public function asActive($value, DB_Record $parent=null, $default=false);

    
	/**
	 * Return the name of a field
	 *
	 * @param int $format  A FIELDNAME_% constant
	 * @return string
	 */
	public function getName($format=DB::FIELDNAME_FULL);
	
	
	/**
	 * Check if value has changed.
	 *
	 * @return boolean
	 */
	public function hasCanged();

	/**
	 * Get the value.
	 * 
	 * @return mixed
	 */
	public function getValue();

	/**
	 * Get the value as given by active record.
	 * 
	 * @return mixed
	 */
	public function getORMValue();
		
	/**
	 * Get the value to update a record.
	 * 
	 * @return mixed
	 */
	public function getValueForSave();
	
	/**
	 * Set the value
	 *
	 * @param mixed $value
	 */
	public function setValue($value);
}
