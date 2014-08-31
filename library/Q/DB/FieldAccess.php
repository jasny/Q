<?php
namespace Q;

/**
 * Interface to indicate object is a field of composite of fields
 *
 * @package DB
 */
interface DB_FieldAccess extends \Countable
{
	/**
	 * Cast field to fieldname.
	 * 
	 * @return string
	 */
	public function __toString();
	
	
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
	 * Return the name of a field.
	 * Names will be concatinated when adding DB::FIELDNAME_LIST.
	 *
	 * @param int $format  A DB::FIELDNAME_% constant
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
	 * @param int $flags  Options as boolean set; DB::ORM or DB::FOR_SAVE
	 * @return mixed
	 */
	public function getValue($flags=0);
	
	/**
	 * Set the value
	 *
	 * @param mixed $value
	 */
	public function setValue($value);
}
