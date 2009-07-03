<?php
namespace Q;

require_once 'Q/DB/Field.php';

/**
 * A field for a record.
 *
 * @package DB
 */
class DB_Field_ForRecord extends DB_Field 
{
	/**
	 * Get the value.
	 * 
	 * @return mixed
	 */
	public function getValue()
	{
		if ($this->mode === self::MODE_DEFINITION) return null;

		if ($this->value instanceof DB_Record) return empty($this->properties['foreign_field']) ? $this->value->getId() : $this->value->getValue($this->properties['foreign_field']); 
		return $this->value;
	}

	/**
	 * Get the value as given by active record.
	 * 
	 * @return DB_Record
	 */
	public function getORMValue()
	{
        if ($this->mode === self::MODE_DEFINITION) return null;
	    if (!isset($this->value) || $this->value instanceof DB_Record) return $this->value;
	    
        if (!$this->link) throw new Exception("Unable to load record for field '" . $this->getFullname() . "': Field is not linked to database connection.");

        if (empty($this->properties['foreign_table'])) {
            trigger_error("Unable to load record for field '" . $this->getFullname() . "': The field has no property 'foreign_table'.", E_USER_WARNING);
            return $this->value;
        }
        
        $this->value = $this->link->table($this->properties['foreign_table'])->load(empty($this->properties['foreign_field']) ? $this->value : array($this->properties['foreign_field']=>$this->value));
        return $this->value;
	}
		
	/**
	 * Get the value to update a record.
	 * {@internal Check mode to see if it's an insert or update.}}
	 * 
	 * @return mixed
	 */
	public function getValueForSave()
	{
	    if ($this->mode === self::MODE_DEFINITION) return null;
	    return $this->value;
	}

	/**
	 * Set the value
	 *
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		if ($this->mode == self::MODE_DEFINITION) {
			trigger_error("Unable to set a value for field '" . $this->getFullname() . "': Can't set a value of a field in definition mode.", E_USER_NOTICE);
			return;
		}
		
		if ($value instanceof DB_Record) {
		    if (isset($this->properties['foreign_table']) && (!$value->getBaseTable() || $value->getBaseTable()->getTableName() != $this->properties['foreign_table'])) throw new Exception("Can't use record for field '" . $this->getFullname() . "': The record should be based on table '" . $this->properties['foreign_table'] . "'" . ($value->getBaseTable() ? ", but is based on table '" . $value->getBaseTable()->getTableName . "'." : '.'));
		    if (!$value->getField(empty($this->properties['foreign_field']) ? '#role:id' : $this->properties['foreign_field'])) throw new Exception("Can't use record for field '" . $this->getFullname() . "': The record does not hold " . (empty($this->properties['foreign_field']) ? "a field for the primary key." : "field '{$this->properties['foreign_field']}'."));
		    $this->value = $record;
		    return;
		}

		$this->value = $value;
		
		if (is_array($value)) {
            if (!$this->link) throw new Exception("Unable to load record for field '" . $this->getFullname() . "': Field is not linked to database connection.");
            
            if (empty($this->properties['foreign_table'])) {
                trigger_error("Unable to load record for field '" . $this->getFullname() . "': The field has no property 'foreign_table'.", E_USER_WARNING);
                return;
            }
            
		    $field = empty($this->properties['foreign_field']) ? $this->link->table($this->properties['foreign_table'])->getFieldProperty('#role:id', 'name') : $this->properties['foreign_field'];
		    $this->value = $this->link->table($this->properties['foreign_table'])->load(isset($value[$field]) ? $value[$field] : null)->setValues($value);
		}
	}
}

?>