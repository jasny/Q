<?php
namespace Q;

require_once 'Q/DB.php';
require_once 'Q/DB/Record.php';
require_once 'Q/DB/Field.php';

/**
 * An object representation of a database table.
 * 
 * @package DB
 * 
 * @todo Add factory method
 * @todo Do field creating in constructor (not lazy)
 * @todo Don't do semantic mapping
 * @todo Auto set view, descview and overview queries
 */
class DB_Table extends \ArrayObject
{
    /**
	 * Database connection
	 * @var DB
	 */
	protected $_connection;
	
	/**
	 * Field objects.
	 * @var DB_Field[]
	 */
	protected $_fields;
	
	/**
	 * Field by name.
	 * @var DB_Field[]
	 */
	protected $_fieldindex;
	
	
	/**
	 * The class constructor
	 *
	 * @param DB    $conn        Database connection
	 * @param array $properties  Table properties
	 */
	public function __construct($conn, $properties)
	{
		$this->_connection = $conn;

		parent::__construct($properties['#table']);
		$this->createFields($properties);
		
		if ($this->_connection) {
			if (!isset($this['view'])) $this['view'] = (string)$this->_connection->selectStatement($this, '*');
			if (!isset($this['load'])) $this['load'] = $this['view'];
			
			if ((!isset($this['overview']) || !isset($this['descview'])) && isset($this->_fieldindex['#role:description'])) {
				$fields = new DB_FieldList();
				$fields[] = $this->getPrimaryKey();
				$fields[] = $this->_fieldindex['#role:description'];
				if (isset($this->_fieldindex['#role:active'])) $fields[] = $this->_fieldindex['#role:active'];
				
				$stmt = (string)$this->_connection->selectStatement($this, $fields);
				if (!isset($this['overview'])) $this['overview'] = $stmt;
				if (!isset($this['descview'])) $this['descview'] = $stmt;
			}
		}
	}
	
	/**
	 * Countable; Count number of fields (not properties)
	 * 
	 * @return int
	 */
	public function count()
	{
		return count($this->_fields);
	}
	
	/**
	 * IteratorAggregate; Iterate through fields (not properties) 
	 * 
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->_fields);
	}
	
	
	/**
	 * Magic get method: get a field
	 *
	 * @param string $name
	 * @return DB_Field
	 */
	public function __get($index)
	{
		return $this->getField($index);
	}
	
	/**
	 * Magic set method
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 */
	public function __set($name, $value)
	{
		if (!($value instanceof DB_Field)) throw new Exception("Unable to add '$name': Value is a " . (is_object($value) ? get_class($value) : gettype($value)) . ", not a DB_Field");
		if ($name[0] !== '#') throw new Exception("You can't overwrite add an alias: Aliases must start with '#', '$name' does not");
		if (!array_search($this->_fields, $value, true)) throw new Exception("Unable to add field '$name': The provided field is not a field in the table");
		
		$this->fieldindex[$name] = $value;
	}
	
	/**
	 * Cast object to string. Alias of DB_Table::getName().
	 * 
	 * @return string
	 */
    public function __toString()
    {  
        return $this['name'];
    }  
	
    
	/**
	 * Get the database connection
	 * 
	 * @return DB
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	
	/**
	 * ArrayAccess; Set property
	 * 
	 * @param string $key
	 * @param string $value
	 */
	public function offsetSet($key, $value)
	{
		if ($key == 'name') throw new Exception("Won't change the name of table '{$this['name']} to '$value': Changing the name of a table is not possible");
		parent::offsetSet($key, $value);
	}
	
	/**
	 * Get a single property from table definition.
	 *
	 * @param string $index
	 * @return mixed
	 */
	public function getProperty($index)
	{
		return $this->offsetGet($index);
	}
	
	/**
	 * Set a single propertiy for table definition.
	 * (fluent interface)
	 * 
	 * @param string $key
	 * @param mixed  $value
	 * @return DB_Table
	 */
	public function setProperty($key, $value)
	{
		$this->offsetSet($key, $value);
		return $this;
	}
	
	/**
	 * Get all properties from table definition.
	 * 
	 * @return array
	 */
	public function getProperties()
	{
		return $this->getArrayCopy();
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
	 * Get a table property as DB_Statement. 
	 *
	 * @param string $index
	 * @return DB_Statement
	 */
	public function get($index)
 	{
 		$value = parent::offsetGet($index);
 		if (!isset($value)) throw new Exception("Unable to create statement for property '$index'; Property is not set.");
 		
 		return $this->_connection->prepare($value);
 	}
 	
	/**
	 * Return the field(s) of the primairy key.
	 *
	 * @return DB_Field
	 */
	public function getPrimaryKey()
	{
	    if ($this->hasField('#role:id')) return $this->getField('#role:id');
	    
	    $pk = array();
	    foreach ($this->fields as $field) {
        	if ($field['is_primary']) $pk[] = $field;
	    }
	    
	    return empty($pk) ? null : (count($pk) == 1 ? reset($pk) : new DB_FieldList($pk));
	}
	
	/**
	 * Return if the table has the field.
	 *
	 * @param string|int $index  Field name, alias or index
	 * @return boolean
	 */
	public function hasField($index)
	{
	    return is_int($index) || ctype_digit($index) ? $index < count($this->_fields) : !empty($this->_fieldindex[$index]);
	}
	
	/**
	 * Get a field.
	 * 
	 * @param string|int $index  Field name, alias or index
	 * @return DB_Field
	 */	
	public function getField($index)
	{
	    if (is_int($index) || ctype_digit($index)) {
	    	if ($index >= count($this->_fields)) throw new Exception("Unable to get field $index: Table '{$this['name']}' only has " . count($this->_fields) . " fields");
	    	return $this->_fields[$index];
	    }
	    
	    if (empty($this->_fieldindex[$index])) throw new Exception("Unable to get field '$index' for table '{$this['name']}': Field doesn't exist");
	    return $this->_fieldindex[$index];
	}
	
	/**
	 * Get all fields of this table.
	 * 
	 * @return array
	 */
	public function getFields()
	{
	    return $this->_fields;
	}

	
    /**
     * Get status information about the table.
     *
     * @return array
     */
    public function getInfo()
    {
        if (!$this->getConnection()) return null;
        return $this->getConnection()->getTableInfo($this->getName());
    }
	
    /**
     * Refresh table definition by re-requesting the metadata from the database. 
     */
    public function recalc()
    {
    	$properties = $this->getConnection()->getMetaData($this->getName());
    	
        $this->exchangeArray($properties['#table']);
        $this->createFields($properties);
    }
    
    /**
     * Reindex fields to apply changed semantic mapping. 
     */
    public function reindex()
    {
	    foreach ($this->_fields as $field) {
	        if ($fieldname[0] === '#') {
	            $aliases[$fieldname] = $props['name'];
	            continue;
	        }

	        $fieldnames[] = $fieldname;
	        $field = isset($this->_fieldindex[$fieldname]) ? $this->_fieldindex[$fieldname] : DB_Field::create($this, &$this[$fieldname]);
	        $i = array_push($fields, $field) - 1;
	        $fieldindex[$fieldname] =& $fields[$i];
	        $fieldindex[$i] =& $fields[$i];
	    }
	    
	    foreach ($aliases as $alias) {
	        if (isset($this[$alias]['name']) && isset($fieldindex[$this[$alias]['name']])) {
	            $fieldindex[$this[$alias]['name']] =& $fieldindex[$this[$alias]['name']];
	        }
	    }
	    
	    $this->_fieldindex =& $fieldindex;
	    $this->_fields =& $fields;
	    $this->_fieldnames = $fieldnames;

	    return $this->_fields;
    }
    
    
	/**
	 * Load a record for this table
	 *
	 * @param mixed  $id          Value(s) for primary key or criteria or NULL for a new record
	 * @param string $mode        Use property 'load.$mode' (defaults back to property 'load' and 'view')
	 * @param int    $resulttype  A Q\DB::FETCH_% constant
	 * @return DB_Record
	 */
	public function load($id, $mode=null, $resulttype=DB::FETCH_RECORD)
	{
	    if (!isset($id) && $resulttype != DB::FETCH_RECORD) throw new Exception("Loading a new record for any other result type than DB::FETCH_RECORD is not supported.");
	    
	    // No link or no table property, so create new record directly (mode is ignored)
		if (!$this->getConnection()) {
			if (isset($id)) throw new Exception("Unable to load a record '$id' for table '$this': No database connection");
			return DB_Record::create($this);
		}
		
		// Create a record using though a query result
		if (isset($mode) && isset($this["load.$mode"])) $statement = $this["load.$mode"];
		  else $statement = $this['load'];
		
		$result = $this->getConnection()->prepareSelect($this, $statement, isset($id) ? $id : false)->execute();
		return isset($id) ? $result->fetchRow($resulttype) : $result->newRecord();
	}

	/**
	 * Create a record for this table.
	 * Record is loaded if $values contains primary key
	 * 
	 * @param array  $values  Set values to record
	 * @param string $mode    Use property 'load.$mode' (defaults back to property 'load' and 'view')
	 * @return DB_Record
	 * 
	 * @throws DB_Constraint_Exception when primary key value is set, but no record can be loaded. 
	 */
	public function getRecord($values=null, $mode=null)
	{
	    $id = null;
	    
	    if (isset($values)) {
    	    $tablename = $this->getName();
    	    $pk = (array)$this->getPrimaryKey();
    	    
    	    foreach ($pk as $i=>$fieldname) {
    	        if (isset($values[$fieldname]) && $values[$fieldname] !== '') $id[$i] = $values[$fieldname];
    	          elseif (isset($values["$tablename.$fieldname"])) $id[$i] = $values["$tablename.$fieldname"];
    	          elseif (($p = array_search($fieldname, $this)) && isset($values[$p])) $id[$i] = $values[$p];
    	    }
    	    if (isset($id) && count($id) != count($pk)) $id = null;
	    }
	    
		$record = $this->load($id, $mode, DB::FETCH_RECORD);
		if (!$record) throw new DB_ConstraintException("Could not load record '" . join(", ", $id) . "' from table " . $this->getName() . " (" . join(", ", $pk) . ")");
		
		if (isset($values)) $record->setValues($values);
		return $record;
	}
	
	/**
	 * Delete a single record or multiple records from this table.
	 * 
	 * @param mixed $id          The value for a primairy (or as array(value, ..) if multiple key fields) or criteria as array(field=>value, ...)
	 * @param int   $constraint  Constraint based on the number or rows: SINGLE_ROW, MULTIPLE_ROWS, ALL_ROWS.
	 * 
	 * @throws Q\DB_Constraint_Exception if query results in > 1 record and $constraint == SINGLE_ROW
	 */
	public function delete($id, $constraint=DB::SINGLE_ROW)
	{
		$this->getConnection()->delete($this->getTablename(), $id, $constraint);
	}
}
