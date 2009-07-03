<?php
namespace Q;

require_once "Q/DB/Field.php";

/**
 * Field to hold a child record.
 * 
 * @package DB
 */
class DB_Field_ForRecordset extends DB_Field
{
	/**
	 * Class constructor
	 *
	 * @param mixed $parent      Parent record (Q\DB_Record), parent table (Q\DB_Record) or Database connection (Q\DB)
	 * @param array $properties  Field properties (can be passed as reference)
	 * @param mixed $value
	 */
	protected function __construct($parent, $properties, $value=null)
	{
	    parent::__construct($parent, $properties);
		if ($this->mode == self::MODE_ACTIVE) $this->setValue($value);
	}
	
	
	/**
	 * Create an active field based on this field definition.
	 *
	 * @param mixed       $value
	 * @param Q\DB_Record $parent
	 * @return DB_Field
	 */
	public function asActive($value, DB_Record $parent=null)
	{
	    $field = $this->asNewActive($parent);
	    
	    $field->mode = self::MODE_ACTIVE;
	    $field->setValue($value);
	    
	    return $field; 
	}

    
	/**
	 * Get the values.
	 *  
	 * @return array
	 */
	public function getValue()
	{
		if ($this->mode == self::MODE_DEFINITION) return null;
		
		$values = null;
		foreach ($this->value as $key=>$obj) $values[$key] = $obj->getValues(DB::FETCH_ASSOC);
		return $values;
	}
		
	/**
	 * Get the values to update a record.
	 *  
	 * @return array
	 */
	public function getValueForStore()
	{
		if ($this->mode == self::MODE_DEFINITION) return null;
		
		$values = null;
		foreach ($this->value as $key=>$obj) $values[$key] = $obj->getValues(DB::FETCH_ORDERED);
		return $values;
	}
	
	/**
	 * Get the child records.
	 *  
	 * @return DB_Field_ForRecord_Value
	 */
	public function getORMValue()
	{
		if ($this->mode == self::MODE_DEFINITION) return null;
		return $this->value;
	}
		
	/**
	 * Set the value
	 *
	 * @param  mixed   $value
	 */
	public function setValue($value)
	{
		if ($this->mode == self::MODE_DEFINITION) {
			trigger_error('Unable to set a value of a field in definition mode', E_USER_NOTICE);
			return;
		}
		
		if (!isset($value)) {
		    $this->value = null;
		    return;
		}
		
		if (!is_array($value)) {
		    trigger_error("Unable to set a scalar value for field of type '" . $this->getProperty('type') . "', value should be an array.", E_USER_NOTICE);
		    return;
		}
		
		$this->value = new DB_Field_ForRecordset_Value($this->properties['child_result'], $value);
	}	
}

/**
 * Array object to hold DB_Records.
 * @ignore
 *
 * @package DB
 */
class DB_Field_ForRecordset_Value extends \ArrayObject
{
    /**
     * Parent result
     * @var Q\DB_Result
     */
    protected $parent;
    
	/**
	 * Class constructor
	 *
	 * @param Q\DB_Result $parent
	 * @param array        $records  Child records
	 */    
    function __construct($parent, $records)
    {
        $this->parent = $parent;
        
        foreach ($records as $key=>$record) {
            if (!($record instanceof DB_Record)) $records[$key] = DB_Record::create($parent, $record); 
        }
        parent::__construct($records);
    }
    
    /**
     * Set an item of (or add to) the array object
     * 
     * @param int          $offset
     * @param Q\DB_Record $value   New Child record
     */
    function offsetSet($offset, $value)
    {
        if (!($value instanceof DB_Record)) $value = DB_Record::create($this->parent, $value);
        parent::offsetSet($offset, $value);
    }
}

?>