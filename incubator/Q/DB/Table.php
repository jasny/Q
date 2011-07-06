<?php
namespace Q;

require_once 'Q/DB.php';
require_once 'Q/DB/Record.php';
require_once 'Q/DB/Field.php';

/**
 * Database table gateway.
 * 
 * @package DB
 * 
 * @todo Add factory method
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
	 * @param DB     $conn  Database connection
	 * @param string $name  Table name
	 */
	public function __construct($conn, $name)
	{
		$this->_connection = $conn;
		$this->recalc();
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
	 * @param string  $name
	 * @param mixed   $value
	 */
	public function __set($name, $value)
	{
		trigger_error("It's not possible to add fields to a table gateway dynamically.", E_USER_ERROR);
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
     * Get status information about the table.
     *
     * @return array
     */
    public function getInfo()
    {
        if (!$this->getConnection()) return null;
        return $this->getConnection()->getTableInfo($this);
    }
	
    /**
     * Refresh table definition by re-requesting the metadata from the database. 
     */
    public function recalc()
    {
    	$properties = $this->getConnection()->getMetaData($this);
    	
        $this->exchangeArray($properties['#table']);
        $this->createFields($properties);
        
		if (!isset($this['view'])) $this['view'] = (string)$this->_connection->selectStatement($this, '*');
		if (!isset($this['load'])) $this['load'] = $this['view'];
		
		if ((!isset($this['overview']) || !isset($this['descview'])) && isset($this->_fieldindex['#description'])) {
			$fields = new DB_FieldList();
			$fields[] = $this->getPrimaryKey();
			$fields[] = $this->_fieldindex['#description'];
			if (isset($this->_fieldindex['#active'])) $fields[] = $this->_fieldindex['#active'];
			
			$stmt = (string)$this->_connection->selectStatement($this, $fields);
			if (!isset($this['overview'])) $this['overview'] = $stmt;
			if (!isset($this['descview'])) $this['descview'] = $stmt;
		}
    }
    
	/**
	 * Create DB_Fields based on the supplied metadata
	 * 
	 * @param array $metadata
	 */
	protected function createFields($metadata)
	{
		foreach ($metadata as $name=>$properties) {
			if ($name[0] == '#') continue;
			
			$field = DB_Field::create($this, $properties);

			$this->_fieldindex[$name] = $field;
			if ($properties['db_name']) $this->_fields[] = $field;
		}
		
		$this->reindex();
	}
	
    /**
     * Reindex fields to apply changed semantic mapping. 
     */
    public function reindex()
    {
    	foreach (array_keys($this->_fieldindex) as $key) {
    		if ($key[0] == '#') unset($this->_fieldindex[$key]); 
    	}
    	    	
	    foreach ($this->_fieldindex as $field) {
	    	if (empty($field['role'])) continue;

	    	foreach ($field['role'] as $role) {
    			if (isset($this->_fieldindex["#$role"])) {
    				if (isset($field["auto:role:$role"])) continue;
    				
    				if (empty($this->_fieldindex["#$role"]["auto:role:$role"])) {
    					trigger_error("Found duplicate role '$role' for table '{$this['name']}'. The role is set for field '{$this->_fieldindex["#$role"]['name']}' and '{$field['name']}'.", E_USER_NOTICE);
    					continue;
    				}
    			}
    			
    			$this->_fieldindex["#$role"] = $field;
    		}
	    }
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
	 * @param string $key    Property name
	 * @return DB_Statement
	 */
	public function stmt($key)
 	{
 		$value = parent::offsetGet($key);
 		if (!isset($value)) throw new Exception("Unable to create statement for property '$key'; Property is not set.");
 		
 		return $this->getConnection()->statement($value);
 	}
 	
	/**
	 * Build a select query statement.
	 * @internal If $fields is an array, $fields[0] may be a SELECT statement and the other elements are additional fields
	 *
	 * @param mixed $columns    Array with fieldnames or fieldlist (string); NULL means all fields.
	 * @param mixed $criteria  The value for the primairy key (int/string or array(value, ...)) or array(field=>value, ...)
	 * @return DB_Statement
	 */
	public function select($columns=null, $criteria=null)
	{
		return $this->getConnection()->statement($this->getConnection()->sqlSplitter->buildSelectStatement($this, $columns, $criteria), $this);
	}
	
	/**
	 * Build an insert or insert/update query statement.
	 *
	 * @param array $colums  Assosiated array as (fielname=>value, ...) or ordered array (fielname, ...) with 1 value for each field
	 * @param array $values  Ordered array (value, ...) for one row  
	 * @param Addition arrays as additional values (rows)
	 * @return DB_Statement
	 */
	public function store($columns=null, $values=null)
	{
		return $this->getConnection()->statement($this->getConnection()->sqlSplitter->buildStoreStatement($this, $columns, $values), $this);
	}
	
	/**
	 * Build a update query statement.
	 *
	 * @param array $values  Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @return DB_Statement
	 */
	public function update($values=null)
	{
		return $this->getConnection()->statement($this->getConnection()->sqlSplitter->buildUpdateStatement($this, $values), $this);
	}

	/**
	 * Build a delete query statement.
	 *
	 * @return DB_Statement
	 */
	public function delete()
	{
		return $this->getConnection()->statement($this->getConnection()->sqlSplitter->buildDeleteStatement($this), $this);
	}
	
 	
	/**
	 * Return the field(s) of the primairy key.
	 *
	 * @return DB_Field
	 */
	public function getPrimaryKey()
	{
	    if ($this->hasField('#id')) return $this->getField('#id');
	    
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
	 * Select a single value from a table.
	 * 
	 * @param mixed $fieldname  The fieldname for the column to fetch the value from.
	 * @param mixed $id         The value for a primairy (or as array(key, ..) if multiple key fields ) or array(field=>value, ...)
	 * @return mixed
	 * 
	 * @throws DB_LimitException if query results in > 1 record
	 */
	public function lookupValue($fieldname, $id)
	{
		if (!$this->getConnection()) throw new Exception("Unable to load a record '$id' for table '$this': No database connection");
		
		$result = $this->getConnection()->select($this, $fieldname, isset($id) ? $id : false)->execute();
		if ($result->countRows() > 1) throw new DB_LimitException("Query returned " . $result->countRows() . " rows, while only 1 row was expected");
		return $result->fetchValue();
	}
    
	/**
	 * Load a record for this table
	 *
	 * @param mixed  $id          Value(s) for primary key or criteria or NULL for a new record
	 * @param string $mode        Use property 'load.$mode' (defaults back to property 'load' and 'view')
	 * @param int    $resulttype  A Q\DB::FETCH_% constant
	 * @return DB_Record
	 * 
	 * @throws DB_LimitException if query results in > 1 record
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
		
		$result = $this->select($statement, isset($id) ? $id : false)->execute();
		if (!isset($id)) return $result->newRecord();
		
		if ($result->countRows() > 1) throw new DB_LimitException("Query returned " . $result->countRows() . " rows, while only 1 row was expected");
		return $result->fetchRow($resulttype);
	}
	
	/**
	 * Count the number of rows in a table (with the given criteria)
	 * 
	 * @param mixed $criteria  The value for a primairy (or as array(key, ..) if multiple key fields ) or array(field=>value, ...)
	 * @return int
	 */
	public function countRows($criteria=null)
	{
		return $this->getConnection()->query($this->getConnection()->sqlSplitter->buildCountStatement($this, $criteria))->fetchValue();
	}
	
	/**
	 * Load/make a record for this table.
	 * Record is loaded if $values contains primary key.
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
		if (!$record) throw new DB_LimitException("Could not load record '" . join(", ", $id) . "' from table " . $this->getName() . " (" . join(", ", $pk) . ")");
		
		if (isset($values)) $record->setValues($values);
		return $record;
	}
}
