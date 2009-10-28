<?php
namespace Q;

require_once 'Q/DB/FieldAccess.php';

/**
 * Composite of DB_Field objects.
 * Uses cannabilism: Adding a DB_FieldList will merge the 2 lists. 
 * 
 * @package DB 
 */
class DB_FieldList extends ArrayObject implements DB_FieldAccess
{
	/**
	 * Parent record or table.
	 * @var DB_Table|DB_Result|DB_Record
	 */
	protected $parent;	
	
	/**
	 * Mode of the field. MODE_DEFINITION: definition only, MODE_ACTIVE: hold value.
	 * @var int
	 */
	protected $mode = self::MODE_DEFINITION;
	
	
	/**
	 * Class constructor
	 *
	 * @param mixed $parent  Optional: Parent record (Q\DB_Record), result (Q\DB_Result), table (Q\DB_Table) or Database connection (Q\DB)
	 * @param array $fields
	 */
	public function __construct($fields=array())
	{
		if (func_num_args() > 1) {
			list($this->parent, $fields) = func_get_args();
			if ($this->parent instanceof DB_Record) $this->mode = self::MODE_ACTIVE;
		}
		
		foreach ($fields as &$field) $this->checkField($field);
		parent::__construct($fields);
	}
	
	/**
	 * Cast field to fieldname.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return join(', ', array_map(function($field) {return (string)$field;}, $this));
	}
	
	
	/**
	 * Get table, result or record containing this field.
	 * 
	 * @return DB_Table|DB_Result|DB_Record
	 */
	public function getParent()
	{
		return $this->parent;
	}
	
	/**
	 * Get the mode of the field.
	 * 
	 * @return int
	 */
	public function getMode()
	{
		return $this->mode;
	}

	
	/**
	 * Check if field can be a member of this list.
	 * 
	 * @param DB_Field $field
	 */
	protected function checkField(&$field)
	{
		if (!($field instanceof DB_Field)) throw new Exception("Unable to use a " . (is_object($field) ? $field : gettype($field)) . " in a field list.");
		
		if ($this->mode == DB_Field::MODE_ACTIVE && $field->getMode() != DB_Field::MODE_ACTIVE) {
			$field = $field->asActive(null, $this->parent, true);
		} else {
			if (isset($this->parent) && $field->getParent() !== $this->parent) throw new Exception("Unable to use '$field' in field list: The field exists in a different context (parent differs).");
		}
	}
	
	/**
	 * ArrayAccess; Append field
	 * 
	 * @param DB_Field $field
	 */
	public function append($field)
	{
		if ($field instanceof self) {
			foreach ($field as $f) $this->append($f);
			return;
		}

		$this->checkField($field);
		parent::append($field);
	}
	
	/**
	 * ArrayAccess; Set field by offset
	 * 
	 * @param int|string $key
	 * @param DB_Field   $field
	 */
	public function offsetSet($key, $field)
	{
		if ($field instanceof self) throw new Exception("Can only append a DB_FieldList to a field list, not set it for a specific key.");
		
		$this->checkField($field);
		parent::offsetSet($key, $field);
	}
	
	/**
	 * ArrayAccess; Exchange the array for another one.
	 * 
	 * @param array $fields
	 * @return array
	 */
	public function exchangeArray($fields)
	{
		foreach ($fields as &$field) $this->checkFields($field);
		return parent::exchangeArray($fields);
	}
	
	
	/**
	 * Return the names of the fields.
	 * Names will be concatinated when adding DB::FIELDNAME_LIST.
	 *
	 * @param int $format  A DB::FIELDNAME_% constant
	 * @return array|string
	 */
	public function getName($format=DB::FIELDNAME_FULL)
	{
		$names = array_map(function($field) use($format) {return $field->getName($format);}, $this);
		return $format & DB::FIELDNAME_LIST ? join(', ', $names) : $names;
	}
	
	
	/**
	 * Check if value has changed.
	 *
	 * @return boolean
	 */
	public function hasCanged()
	{
		return (bool)array_sum(array_map(function($field) {return $field->hasCanged();}, $this));
	}
	
	/**
	 * Get the values of the fields.
	 * 
	 * @return array
	 */
	public function getValue()
	{
		return array_map(function($field) {return $field->getValue();}, $this);
	}
	
	/**
	 * Set the values.
	 *
	 * @param array $values
	 */
	public function setValue($values)
	{
		foreach ($this as $i=>$field) {
			if (array_key_exists($i, $values)) $field->setValue($values[$i]);
		}
	}
	
	
	/**
	 * Magic clone method.
	 */
	public function __clone()
	{
		foreach ($this as &$field) $field = clone $field;
	}
}
