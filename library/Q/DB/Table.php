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
class DB_Table implements \ArrayAccess
{
    /**
	 * Database connection
	 * @var DB
	 */
	protected $_link;
	
	/**
	 * The properties of the table
	 * @var array
	 */
	protected $_properties;

	/**
	 * Field names.
	 * @var array
	 */
	protected $_fieldnames;

	/**
	 * Field objects.
	 * @var array
	 */
	protected $_fields = array();
	
	/**
	 * Index of created fields.
	 * @var array
	 */
	protected $_fieldindex = array();	

	/**
	 * Flag to indicate $this->_fields is fully set and in order.
	 * @var boolean
	 */
	protected $_fieldsComplete = false;	
	
	/**
	 * The class constructor
	 *
	 * @param Q\DB $link        Database connection
	 * @param array $properties  Table properties (can be passed as reference)
	 */
	function __construct($link, $properties)
	{
		$this->_link = $link;
		$this->_properties =& $properties;
	}

	
	/**
	 * Check if the tabledef property exists. 
	 *
	 * @param string $index
	 * @return boolean
	 */
	public function offsetExists($index)
	{
		return isset($this->_properties['#table'][$index]);
	}
	
	/**
	 * Get a tabledef property. 
	 *
	 * @param string $index
	 * @return Log::Handler
	 */
	public function offsetGet($index)
 	{
 	    if (!isset($this->_properties['#table'][$index])) return null;
 		return $this->_properties['#table'][$index];
 	}
 	
	/**
	 * Set a tabledef property. 
	 *
	 * @param string $index
	 * @param mixed $value
	 */ 	
 	public function offsetSet($index, $value)
 	{
 		trigger_error("Setting a table property using ArrayAccess access is not supported, use setProperty instead. Trying to set '$index' for " . $this->getName(), E_USER_WARNING);
    }
 	
	/**
	 * Unset a tabledef property.
	 *
	 * @param string $index
	 */
 	public function offsetUnset($index)
 	{
 		trigger_error("Setting a table property using ArrayAccess is not supported, use setProperty instead. Trying to set '$index' for " . $this->getName(), E_USER_WARNING);
 	}	

 	
	/**
	 * Magic get method: get a field
	 *
	 * @param  string  $name
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
		trigger_error("It is not possible to add a field through the table gateway. Trying to add field to " . $this->getName(), E_WARNING);
	}
	
	
	/**
	 * Get the database connection
	 * 
	 * @return DB
	 */
	public function getLink()
	{
		return $this->_link;
	}

	/**
	 * Return the name for the table definition
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->_properties['#table']['name'];
	}

	/**
	 * Return the table name (as known in the database)
	 * 
	 * @return string
	 */
	public function getTableName()
	{
		return isset($this->_properties['#table']['table']) ? $this->_properties['#table']['table'] : null;
	}
		
	/**
	 * Return the factory name for the record
	 * 
	 * @return string
	 */
	public function getRecordType()
	{
		return isset($this->_properties['#table']['recordtype']) ? $this->_properties['#table']['recordtype'] : null;
	}

	/**
	 * Return the names of the fields of this table
	 * 
	 * @return array
	 */
	public function getFieldnames()
	{
	    if (isset($this->_fieldnames)) return $this->_fieldnames;
	    
	    $this->_fieldnames = array();
		foreach ($this->_properties as $fieldname=>&$props) {
		    if ($fieldname[0] != '#' && !empty($props['table'])) $this->_fieldnames[] = $fieldname; 
		}
		return $this->_fieldnames;
	}
	
	/**
	 * Return the fieldname(s) of the primairy key.
	 *
	 * @param boolean $autoIncrementOnly  Only return fields with the autoincrement feature
	 * @param boolean $asIdentifier       Add table and quote
	 * @return string|array
	 */
	public function getPrimaryKey($autoIncrementOnly=false, $asIdentifier=false)
	{
	    if (isset($this->_properties['#role:id'])) return $asIdentifier ? $this->getLink()->makeIdentifier($this->getTableName(), $this->_properties['#role:id']['name']) : $this->_properties['#role:id']['name'];
	    if ($autoIncrementOnly) return null;
	    
	    $fields = null;
	    foreach ($this->properties as $name=>&$props) {
	         if ($name[0] == '#') continue;
	         if ($props['is_primary']) $fields[] = $asIdentifier ? $this->getLink()->makeIdentifier($this->getTableName(), $name) : $name;
	    }
	    
	    return $fields;
	}
	
	/**
	 * Return the metadata properties of the table
	 * 
	 * @return array
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * Get the properties from table definition
	 *
	 * @return array
	 */
	public function getTableProperties()
	{
		return $this->_properties['#table'];
	}
		
	/**
	 * Get a single property from table definition
	 *
	 * @param string $index
	 * @return mixed
	 */
	public function getTableProperty($index)
	{
		return isset($this->_properties['#table'][$index]) ? $this->_properties['#table'][$index] : null;
	}

	/**
	 * Get properties for a field.
	 *
	 * @param string $field
	 * @return array
	 */
	public function getFieldProperties($field)
	{
		return isset($this->_properties[$field]) ? $this->_properties[$field] : null;
	}
		
	/**
	 * Get a single property for a field.
	 *
	 * @param string $field
	 * @param string $index
	 * @return mixed
	 */
	public function getFieldProperty($field, $index)
	{
		return isset($this->_properties[$field][$index]) ? $this->_properties[$field][$index] : null;
	}

	
	/**
	 * Set the metadata properties of the table.
	 * (fluent interface)
	 * 
	 * @param array $properties  array(field=>array(index=>value, ...), ...)
	 * @return DB_Table
	 */
	public function setProperties($properties)
	{
		foreach ($properties as $field=>&$props) {
			foreach ($props as $index=>$value) $this->_properties[$field][$index] = $value;
		}
		return $this;
	}
	
	/**
	 * Set the properties for table definition.
	 * (fluent interface)
	 * 
	 * @param array $properties  array(index=>value, ...)
	 * @return DB_Table
	 */
	public function setTableProperties($properties)
	{
		foreach ($properties as $index=>$value) $this->_properties['#table'][$index] = $value;
		return $this;
	}
	
	/**
	 * Set a single propertiy for table definition.
	 * (fluent interface)
	 * 
	 * @param string $index
	 * @param mixed  $value
	 * @return DB_Table
	 */
	public function setTableProperty($index, $value)
	{
		$this->_properties['#table'][$index] = $value;
		return $this;
	}

	/**
	 * Set the properties for a field.
	 * (fluent interface)
	 *
	 * @param string $field
	 * @param array  $properties
	 * @return DB_Table
	 */
	public function setFieldProperties($field, $properties)
	{
		foreach ($properties as $index=>$value) $this->_properties[$field][$index] = $value;
		return $this;
	}
	
	/**
	 * Set a single property for a field.
	 * (fluent interface)
	 *
	 * @param string $field
	 * @param string $index
	 * @param mixed  $value
	 * @return DB_Table
	 */
	public function setFieldProperty($field, $index, $value)
	{
		$this->_properties[$field][$index] = $value;
		return $this;
	}
	
	/**
	 * Return if the table has the field.
	 *
	 * @param string $index  Fieldname or index
	 * @return boolean
	 */
	public function hasField($index)
	{
	    return is_int($index) || ctype_digit($index) ? $index < count($this->getFieldnames()) : !empty($this->_properties[$index]['table']);
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
	    if (!is_int($index) && !isset($this->_properties[$index])) throw new Exception("Unknown field '$index'");
	    
	    if ($index[0] === '#') {
	        if (empty($this->_properties[$index]['table'])) throw new Exception("Can create field object for '$index': Column '{$this->_properties[$index]['name']}' is an expression.");
	        $field = $this->_properties[$index]['name'];
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
	    
		$i = array_push($this->_fields, DB_Field::create($this, &$this->_properties[$field]))-1;
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

	    foreach ($this->_properties as $fieldname=>$props) {
	        if ($fieldname === '#table' || !isset($props['table'])) continue;
	        
	        if ($fieldname[0] === '#') {
	            $aliases[$fieldname] = $props['name'];
	            continue;
	        }

	        $fieldnames[] = $fieldname;
	        $field = isset($this->_fieldindex[$fieldname]) ? $this->_fieldindex[$fieldname] : DB_Field::create($this, &$this->_properties[$fieldname]);
	        $i = array_push($fields, $field) - 1;
	        $fieldindex[$fieldname] =& $fields[$i];
	        $fieldindex[$i] =& $fields[$i];
	    }
	    
	    foreach ($aliases as $alias) {
	        if (isset($this->_properties[$alias]['name']) && isset($fieldindex[$this->_properties[$alias]['name']])) {
	            $fieldindex[$this->_properties[$alias]['name']] =& $fieldindex[$this->_properties[$alias]['name']];
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
        if (!$this->getLink()) return null;
        return $this->getLink()->getTableInfo($this->getName());
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
        
        $this->getLink()->clearCache($this->getName());
        $this->_properties =& $this->getLink()->getMetaData($this->getName());
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
		if (!$this->getLink() || $this->getTablename() === null){
			if (isset($id)) throw new DB_Exception("Unable to load a record for table definition '" . $this->getName() . "': " . (!$this->getLink() ? "No database connection" : "No 'table' property. Table might be virtual (does not exists in db)"));
			return DB_Record::create($this);
		}
		
		// Create a record using though a query result
		if (isset($mode) && isset($this->_properties['#table']["load.$mode"])) $statement = $this->_properties['#table']["load.$mode"];
		  elseif (isset($this->_properties['#table']['load'])) $statement = $this->_properties['#table']['load'];
		  else $statement = $this->_properties['#table']['view'];
		
		$result = $this->getLink()->prepareSelect($this, $statement, isset($id) ? $id : false, isset($this->_properties['#table']['filter']) ? $this->_properties['#table']['filter'] : null)->execute();
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
    	          elseif (($p = array_search($fieldname, $this->_properties)) && isset($values[$p])) $id[$i] = $values[$p];
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
	    if (isset($this->_properties['#table']['filter']) && $this->load($id, null, DB::FETCH_ORDERED) === null) return;
		$this->_link->delete($this->getTablename(), $id, $constraint);
	}
	
	
	/**
	 * Prepare a query statement for this the table definition.
	 *
	 * @param string $property  Property name (or array with fieldnames)
	 * @param array  $fields    Additional fieldnames. Fieldnames may include mapping links, eg; '#role:status' or '#role:*'
	 * @param mixed  $criteria  The value for a primairy (or as array(key, ..) if multiple key fields) or array(field=>value, ...)
	 * @return DB_Statement
	 */
	function getStatement($property='view', $fields=null, $criteria=null)
	{
		if (!$this->getLink()) throw new DB_Exception("Unable to prepare statement for table definition '" . $this->getName() . "': No database connection");
		if (!isset($this->_properties['#table']['table'])) throw new DB_Exception("Unable to prepare statement for table definition '" . $this->getName() . "': No 'table' property. Table might be virtual (does not exists in db)");

		$fields = (array)$fields;
		if (is_array($property)) {
			$fields = array_merge($property, $fields);
			unset($property);
		}
		
		if (isset($property) && !isset($this->_properties['#table'][$property])) throw new DB_Exception("Unable to prepare statement for table definition '" . $this->getName() . "': Propery '$property' does not exist'");

		// Check if query is something else than a select (or select with no other options) 
		$qs = $this->getLink()->prepare($this, $this->_properties['#table'][$property]);
		$querytype = $qs->getQueryType();
		if (!empty($querytype) && ($querytype != 'SELECT' || (empty($fields) && empty($criteria) && empty($this->_properties['#table']['filter'])))) return $qs;
		
		// Get real fieldnames for mapping links
		foreach ($fields as $i=>$field) {
			if ($field[0] === '#') {
				if (substr($field, -2, 2) === ':*') {
					unset($fields[$i]);
					$len = strlen($field) - 1;
					foreach ($this->_properties as $index=>$props) {
						if (substr($field, 0, $len) == substr($index, 0, $len)) {
							$fields[] = $this->getLink()->makeIdentifier(isset($props['table']) ? $props['table'] : null, $props['name'], substr($index, 1));
						}
					}
				} elseif (isset($this->_properties[$field]['name'])) {
					$fields[$i] = $this->_link->makeIdentifier($this->_properties[$field]['table'], $this->_properties[$field]['name'], substr($field, 1));
				} else {
					unset($fields[$i]);
				}
			}
		}

		// Add value of property to fields and create prepared statement
		if (isset($property)) array_unshift($fields, $this->_properties['#table'][$property]);
		  elseif (empty($fields)) $fields = null;
		
		$qs = $this->getLink()->prepareSelect($this, $fields, $criteria, isset($this->_properties['#table']['filter']) ? $this->_properties['#table']['filter'] : null);
		return $qs;
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