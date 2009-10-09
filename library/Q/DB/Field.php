<?php
namespace Q;

require_once 'Q/Crypt.php';

/**
 * An object representation for a database field or a field of a record.
 *
 * @package DB
 */
class DB_Field extends \ArrayObject
{
	/** Representation of a field of a table/recordset **/
	const MODE_DEFINITION = 0;
	
	/** Representation of a field of a record **/
	const MODE_ACTIVE = 1;
	

	/**
     * A list with properties that may not be changed at runtime.
     * 
     * @var array()
     */
    static public $protectedProperties = array(
      'name',
      'name_db',
      'table',
      'table_db',
      'table_def',
      'fieldtype',
      'child_result'
    );
	
	/**
	 * Database connection.
	 * @var Q\DB
	 */
	protected $connection = null;

	/**
	 * Parent record or table.
	 * @var Q\DB_Table|Q\DB_Result|Q\DB_Record
	 */
	protected $parent = null;	
	
	/**
	 * Mode of the field. MODE_DEFINITION: definition only, MODE_ACTIVE: hold value, MODE_NEW: active/new.
	 * @var int
	 */
	protected $mode = self::MODE_DEFINITION;
	
	
	/**
	 * Value of the field
	 * @var mixed
	 */
	protected $value = null;

	/**
	 * Original value of the field
	 * @var mixed
	 */
	protected $originalValue;
		
	
	/**
	 * Factory method for new field definition objects
	 *
	 * @param mixed $parent      Parent record (Q\DB_Record), result (Q\DB_Result), table (Q\DB_Table) or Database connection (Q\DB)
	 * @param array $properties  Field properties (can be passed as reference)
	 * @param mixed $value
	 * @return DB_Field
	 */
	static public function create($parent, $properties, $value=null)
	{
	    $fieldtype = isset($properties['fieldtype']) ? $properties['fieldtype'] : null;
        if (!isset(DB::$fieldtypes[$fieldtype])) throw new Exception("Fieldtype '{$properties['fieldtype']}' set for " . (isset($properties['table_def']) ? "{$properties['table']}." : null) . "{$properties['name']} is not defined.");
        $class = DB::$fieldtypes[$fieldtype];
        
        if (!load_class($class)) throw new Exception("Could not load class '{$class}', defined for '{$fieldtype}'.");
	    return new $class($parent, $properties, $value);
	}
	
	/**
	 * Class constructor
	 *
	 * @param mixed $parent      Parent record (Q\DB_Record), result (Q\DB_Result), table (Q\DB_Table) or Database connection (Q\DB)
	 * @param array $properties  Field properties (can be passed as reference)
	 * @param int   $mode        MODE_DEFINITION: definition only, MODE_ACTIVE: hold value, MODE_NEW: hold value for new record
	 * @param mixed $value
	 */
	public function __construct($parent, $properties, $value=null)
	{
	    if ($parent instanceof DB) {
	        $this->connection = $parent;
	    } else {
	        $this->parent = $parent;
	        $this->connection = $parent->getConnection();
	    }
	    
		if (!isset($this->parent) || $this->parent instanceof DB_Record) {
		    $this->mode = self::MODE_ACTIVE;
		    $this->value =& $value;
		    $this->originalValue = $this->value;
		}
		
		parent::__construct($properties);
	}
	
	/**
	 * Cast field to fieldname.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this['name'];
	}
	

	/**
	 * Get the database connection.
	 * 
	 * @return DB
	 */
	public function getConnection()
	{
		return $this->connection;
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
	 * Create an active field based on this field definition.
	 *
	 * @param mixed       $value
	 * @param Q\DB_Record $parent
	 * @param boolean     $default  Set value to default
	 * @return DB_Field
	 */
	public function asActive($value, DB_Record $parent=null, $default=false)
	{
	    if ($this->mode !== self::MODE_DEFINITION) throw new Exception("You can only make an active field based on a field definition, not on an active field.");
	    
	    $field = clone $this;
	    
	    $field->parent = $parent;
	    if (isset($parent)) $field->connection = $parent->getConnection();

	    $field->mode = self::MODE_ACTIVE;

	    if ($default) {
	    	if (isset($this['default'])) $field->originalValue = $field->value = $this['default'];
		} else {
			$field->value =& $value;
		    $field->originalValue = $value;
		}
		
	    return $field;
	}

    
	/**
	 * Return the name of a field
	 *
	 * @param int $format  A FIELDNAME_% constant
	 * @return string
	 */
	public function getName($format=DB::FIELDNAME_COL)
	{
		switch ($format) {
			case DB::FIELDNAME_COL:      return $this['name'];
			case DB::FIELDNAME_FULL:     return isset($this['table']) && $this['table'] !== '' ? $this['table'] . '.' . $this['name'] : $this['name'];
			case DB::FIELDNAME_ORG:
				if (!isset($this['name_db'])) return null;
				return isset($this->connection) ? $this['table_db'] . '.' . $this['name_db'] : $this->connection->makeIdentifier($this['table'], $this['name_db'], $format & DB::FIELDNAME_WITH_ALIAS ? $this['name'] : null);
			case DB::FIELDNAME_DB:       
				if (!isset($this['name_db'])) return null;
				return isset($this->connection) ? $this['table'] . '.' . $this['name_db'] : $this->connection->makeIdentifier($this['table'], $this['name_db'], $format & DB::FIELDNAME_WITH_ALIAS ? $this['name'] : null);
		}
	}

	/**
	 * Get a single property.
	 *
	 * @param string $index
	 * @return mixed
	 */
	public function getProperty($index)
	{
		return $this->offsetGet($index);
	}
	
	/**
	 * Set the value of a property.
	 * (fluent interface)
	 * 
	 * @param string $name
	 * @param mixed  $value
	 * @return DB_Table
	 */
	public function setProperty($name, $value)
	{
		if (in_array($name, self::$protectedProperties)) {
		    trigger_error("Unable to set property '$name' for field '{$this['name']}': This property is read-only.", E_USER_WARNING);
		    return;
		}
		
		$this[$name] = $value;
		if (isset($this->parent) && isset(DB::$mappingProperties[$name])) $this->parent->RemapField($this, $name, $value);
		return $this; 
	}

	/**
	 * Set the properties for table definition.
	 * (fluent interface)
	 * 
	 * @param array $properties  array(key=>value, ...)
	 * @return DB_Table
	 */
	public function setProperties($properties)
	{
		foreach ($properties as $key=>&$value) {
			$this->offsetSet($key, $value);
		}
		
		return $this;
	}
	
	
	/**
	 * Check if value has changed.
	 *
	 * @return boolean
	 */
	public function hasCanged()
	{
	    return $this->value != $this->originalValue;
	}

	/**
	 * Check if value is crypted.
	 *
	 * @return boolean
	 */
	public function isCrypted()
	{
	    return !empty($this['crypt']) && !isset($this->value) && is_scalar($this->value) && !$this->hasCanged();
	}
	
	
	/**
	 * Get the value.
	 * 
	 * @return mixed
	 */
	public function getValue()
	{
		if ($this->mode === self::MODE_DEFINITION) return null;
		return $this->value;
	}

	/**
	 * Get the value as given by active record.
	 * 
	 * @return mixed
	 */
	public function getORMValue()
	{
		return $this->getValue();
	}
		
	/**
	 * Get the value to update a record.
	 * 
	 * @return mixed
	 */
	public function getValueForSave()
	{
	    if ($this->mode === self::MODE_DEFINITION) return null;
	    return $this->castValue($this->cryptValue($this->getValue()), true);
	}

	/**
	 * Get subrecord, so it can be saved.
	 * 
	 * @return null
	 */
	public function getBubbleSave()
	{
	    return null;
	}
	
	/**
	 * Set the value
	 *
	 * @param mixed $value
	 */
	public function setValue($value)
	{
		if ($this->mode === self::MODE_DEFINITION) {
			trigger_error("Unable to set a value for field '" . $this->getFullname() . "': Can't set a value of a field in definition mode.", E_USER_NOTICE);
			return;
		}
		
		$this->value = $this->castValue($value);
	}
	
	
	/**
	 * Cast a value according to the datatype property
	 *
	 * @param mixed   $value
	 * @param boolean $force  Force casting and cast to type instead of datatype
	 * @return mixed
	 */
	public function castValue($value, $force=null)
	{	
		if ($value === null || $value === "") return null;
		if (!isset($this['type'])) return $value;
		
		$cast = null;
		$type = strtolower($force ? $this['type'] : $this['datatype']);
		
		switch ($type) {
			case 'children':
			    $cast = (array)$value;
				break;

			case 'array':
				$cast = !is_array($value) && !empty($value) ? split_set(';', $value) : $value;
				break;
			
			case 'bool':
			case 'boolean':
				if ($force || $value == 0 || $value == 1) $cast = (bool)$value;
				  else $cast = $value;
				break;

			case 'set':
				if (!is_int($value) && !preg_match('/^-?\d*$/', $value)) $value = split_set(';', $value);
				if (is_array($value) && !empty($value) && is_string(reset($value))) {
					$opts = $this->getProperty('values');
					if (!empty($opts)) {
						$intval = 0;
						if (!is_array($opts)) $opts = split_set(';', $opts);
						foreach ($opts as $val=>$opt) if (in_array($opt, $value)) $intval += pow(2, $val);
						$value = $intval;
					}
				}				
				if (is_array($value)) $value = array_sum($value);
			case 'bit':
			case 'tinyint':
			case 'smallint':
			case 'mediumint':
			case 'int':
			case 'integer':
			case 'bigint':
				if (is_string($value)) $value = trim($value, '"');
				$cast = (int)$value;
				if (is_object($value) || is_array($value) || (!is_int($value) && !preg_match('/^-?\d*$/', $value))) {
					if ($force) trigger_error("Field " . $this->getFullname() . ": Value $value is cast to integer $cast", E_USER_NOTICE);
					   else $cast = $value;
				}
				break;
				
			case 'float':
			case 'double':
			case 'double precision':
			case 'real':
			case 'decimal':
			case 'dec':
			case 'fixed':
				if (is_string($value)) $value = trim($value, '"');
				$matches = null;
				if (is_object($value) || is_array($value) || (!is_float($value) && !preg_match('/^(-?\d*)([\.,]?)(\d*)$/', $value, $matches)) && $force) {
					trigger_error("Field " . $this->getFullname() . ": Value $value is cast to float " . (float)$value, E_USER_NOTICE);
				}
				$cast = !empty($matches) ? $cast = (float)($matches[1] . '.' . $matches[3]) : (!$force ? $value : (float)$value);  
				break;
			
			case 'date':
			case 'datetime':
			    if (is_int($value) || (is_string($value) && ctype_digit($value))) {
			        $date = new \DateTime();
			        $date->setTimestamp($value);
			        $cast = $date->format('c');
			    } elseif (is_string($value) && preg_match('/^\d{1,2}-\d{1,2}-\d{4}(\s+\d{1,2}:\d{1,2}:\d{1,2}\d{1,2})?$/', $value, $matches)) {
			        $date = \DateTime::createFromFormat('j-n-Y H:i:s', $value . ($matches[1] ? '' : ' 00:00:00'));
			        if ($date) $cast = $date->format('c');
			    }
			    if (empty($cast)) $cast = $value;
			    
			    break;
			    
			case 'time':
				if (is_string($value) && preg_match('/^([0-1]\d|2[0-3])\:([0-5]\d)/', $value, $matches)) $cast = $matches[0];
				  elseif ($force) trigger_error("Field " . $this->getFullname() . ": Value $value is not a valid time: set to NULL", E_USER_NOTICE);
				  else $cast = $value;
				break;
			
			default:
				if ($force) $value = $this['datatype'] == 'array' ? $value = implode_set(';', $value) : (string)$value;
				$cast = is_string($value) ? trim($value) : $value;
		}
		
		return $cast;
	}

	/**
	 * Encrypt value according to the field crypt property
	 *
	 * @param string $value
	 * @return string
	 */
	public function cryptValue($value)
	{
		return empty($this['crypt']) || !isset($value) || !is_scalar($value) || $value === $this->originalValue ? $value : Crypt::with($this['crypt'])->encrypt($value);
	}
}

