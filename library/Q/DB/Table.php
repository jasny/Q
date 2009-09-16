<?php
namespace Q;

require_once 'Q/DB.php';
require_once 'Q/DB/Record.php';
require_once 'Q/DB/Field.php';

/**
 * An object representation of a database table.
 * 
 * Because offsetGet can't return by reference, it is only possible to get, and not set, table properties through ArrayAccess.
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
	 * @var array
	 */
	protected $_fields = array();
	
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
		if (!($value instanceof DB_Field)) throw new Exception("Unable to add a " . (is_object($value) ? get_class($value) : gettype($value)) . " as a field.");
		if (!array_search($this->_fields, $value, true)) throw new Exception("You can only set an alias of a field, not add a new field.");
		if (isset($this->fields[$name])) throw new Exception("You can't overwrite a real fieldname with an alias.");
		
		$this->fieldindex["#$name"] = $value;
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
	 * @return DB_Field_Interface
	 */
	public function getPrimaryKey()
	{
	    if ($this->hasField('#role:id')) return $this->getField('#role:id');
	    
	    $pk = null;
	    foreach ($this->fields as $name=>$field) {
        	if ($field['is_primary']) $pk = !isset($pk) ? $field : new DB_Fields($pk, $field);
	    }
	    
	    return $pk;
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
	 * @param string $index  Fieldname or index
	 * @return DB_Field
	 */	
	public function getField($index)
	{
	    if (!empty($this->_fieldindex[$index])) return $this->_fieldindex[$index];

	    if (ctype_digit($index)) $index = (int)$index;
	    if (isset($this->_fieldindex[$index])) throw new Exception("Still can't create field object for '$index': You know why.");
	    if ($index === '#table') throw new Exception("Can't create a field object for '#table', that's the table not a field.");
	    if (!is_int($index) && !isset($this->___properties[$index])) throw new Exception("Unknown field '$index'");
	    
	    if ($index[0] === '#') {
	        if (empty($this->___properties[$index]['table'])) throw new Exception("Can create field object for '$index': Column '{$this->___properties[$index]['name']}' is an expression.");
	        $field = $this->___properties[$index]['name'];
	    } elseif (is_int($index)) {
	        $fieldnames = $this->getFieldnames();
	        if (!isset($fieldnames[$index])) throw new Exception("Can create field object for field $index: Table has only " . count($fieldnames) . " fields.");
	        $field = $fieldnames[$index];
	    }
	    
	    if (isset($field)) {
	        if (!isset($this->_fieldindex[$field])) $this->_fieldindex[$index] =& $this->_fieldindex[$field];
	          else $this->_fieldindex[$index] =& $this->_fields[array_push($this->_fields, null)-1];

	        if (isset($this->_fieldindex[$index])) return $this->_fieldindex[$index];
	    } else {
	        $field = $index;
	    }
	    
		$i = array_push($this->_fields, DB_Field::create($this, &$this->___properties[$field]))-1;
		$this->_fieldindex[$field] =& $this->_fields[$i];
		
		return $this->_fields[$i];
	}
	
	/**
	 * Get all fields of this table.
	 * 
	 * @return array
	 */
	public function getFields()
	{
	    if ($this->_fieldsComplete) return $this->_fields;
	    
	    $fields = array();
	    $fieldindex = array();
	    $fieldnames = array();
	    $aliases = array();

	    foreach ($this->___properties as $fieldname=>$props) {
	        if ($fieldname === '#table' || !isset($props['table'])) continue;
	        
	        if ($fieldname[0] === '#') {
	            $aliases[$fieldname] = $props['name'];
	            continue;
	        }

	        $fieldnames[] = $fieldname;
	        $field = isset($this->_fieldindex[$fieldname]) ? $this->_fieldindex[$fieldname] : DB_Field::create($this, &$this->___properties[$fieldname]);
	        $i = array_push($fields, $field) - 1;
	        $fieldindex[$fieldname] =& $fields[$i];
	        $fieldindex[$i] =& $fields[$i];
	    }
	    
	    foreach ($aliases as $alias) {
	        if (isset($this->___properties[$alias]['name']) && isset($fieldindex[$this->___properties[$alias]['name']])) {
	            $fieldindex[$this->___properties[$alias]['name']] =& $fieldindex[$this->___properties[$alias]['name']];
	        }
	    }
	    
	    $this->_fieldindex =& $fieldindex;
	    $this->_fields =& $fields;
	    $this->_fieldnames = $fieldnames;

	    return $this->_fields;
	}

	/**
	 * Return array(fields, fieldindex, fieldnames, tablerefs)
	 * 
	 * @return array
	 */
    public function getInternalInfo()
    {
        $fields = $this->getFields(); // This will set the complete fieldindex, so doing this first.
        
        $fieldindex = array();
        foreach ($this->_fieldIndex as $name=>$field) {
            if (is_string($name)) $fieldindex[$name] = array_search($this->fields, $field, true); 
        }

        $table = $this->getTablename();
        return array($fields, $fieldindex, $this->_fieldnames, array($table=>$table));
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
    public function refresh()
    {
        $this->_fieldnames = null;
        $this->_fields = array();
        $this->_fieldindex = array();
        $this->_fieldsComplete = false;
        
        $this->getConnection()->clearCache($this->getName());
        $this->___properties =& $this->getConnection()->getMetaData($this->getName());
    }
    
    
	/**
	 * Load a record for this table
	 *
	 * @param mixed  $id          Value(s) for primary key or criteria or NULL for a new record
	 * @param string $mode        Use property 'load.$mode' (defaults back to property 'load' and 'view')
	 * @param int    $resulttype  A Q\DB::FETCH_% constant
	 * @return DB_Record
	 */
	function load($id, $mode=null, $resulttype=DB::FETCH_RECORD)
	{
	    if (!isset($id) && $resulttype != DB::FETCH_RECORD) throw new Exception("Loading a new record for any other result type than DB::FETCH_RECORD is not supported.");
	    
	    // No link or no table property, so create new record directly
		if (!$this->getConnection() || $this->getTablename() === null){
			if (isset($id)) throw new DB_Exception("Unable to load a record for table definition '" . $this->getName() . "': " . (!$this->getConnection() ? "No database connection" : "No 'table' property. Table might be virtual (does not exists in db)"));
			return DB_Record::create($this);
		}
		
		// Create a record using though a query result
		if (isset($mode) && isset($this->___properties['#table']["load.$mode"])) $statement = $this->___properties['#table']["load.$mode"];
		  elseif (isset($this->___properties['#table']['load'])) $statement = $this->___properties['#table']['load'];
		  else $statement = $this->___properties['#table']['view'];
		
		$result = $this->getConnection()->prepareSelect($this, $statement, isset($id) ? $id : false, isset($this->___properties['#table']['filter']) ? $this->___properties['#table']['filter'] : null)->execute();
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
	function getRecord($values=null, $mode=null)
	{
	    $id = null;
	    
	    if (isset($values)) {
    	    $tablename = $this->getName();
    	    $pk = (array)$this->getPrimaryKey();
    	    
    	    foreach ($pk as $i=>$fieldname) {
    	        if (isset($values[$fieldname]) && $values[$fieldname] !== '') $id[$i] = $values[$fieldname];
    	          elseif (isset($values["$tablename.$fieldname"])) $id[$i] = $values["$tablename.$fieldname"];
    	          elseif (($p = array_search($fieldname, $this->___properties)) && isset($values[$p])) $id[$i] = $values[$p];
    	    }
    	    if (isset($id) && count($id) != count($pk)) $id = null;
	    }
	    
		$record = $this->load($id, $mode, DB::FETCH_RECORD);
		if (!$record) throw new DB_Constraint_Exception("Could not load record '" . join(", ", $id) . "' from table " . $this->getName() . " (" . join(", ", $pk) . ")");
		
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
	function delete($id, $constraint=DB::SINGLE_ROW)
	{
	    if (isset($this->___properties['#table']['filter']) && $this->load($id, null, DB::FETCH_ORDERED) === null) return;
		$this->getConnection()->delete($this->getTablename(), $id, $constraint);
	}
	
	/**
	 * Don't call this unless you are a field and a mapping property changed.
	 * 
	 * @param Q\DB_Field $field
	 * @param string     $prop   Property name
	 * @param string     $value  Property value
	 * 
	 * @todo Implement remapField
	 */
	public function remapField($field, $prop, $value)
	{
	    if (!array_search($field, $this->_fields, true)) return;
	}
}

?>
