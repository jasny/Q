<?php
namespace Q;

require_once 'Q/DB/Field.php';
require_once 'Q/DB/Record.php';

/**
 * Abstraction layer for database query result.
 * 
 * @package DB
 */
abstract class DB_Result
{
	/**
	 * Database connection
	 * @var DB
	 */
	protected $link;

	/**
	 * The native result object or resource
	 * @var object|result
	 */
	protected $native;

	/**
	 * The query statement which created this result
	 * @var string
	 */
	protected $statement;
	
	/**
	 * Default fetch mode
	 * @var int
	 */
	protected $fetchMode=self::FETCH_ASSOC;
	
	
	/**
	 * The factory name used to create a record object
	 * @var string
	 */
	protected $recordtype;	

	/**
	 * The table definition, responsible for this result
	 * @var Q\DB_Table
	 */
	protected $basetable = false;
		
	/**
	 * The fields (DB_Field objects) of the result
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * The roles of the fields
	 *
	 * @var array
	 */
	protected $roles;
	
	/**
	 * Fieldname index.
	 *
	 * @var array
	 */
	protected $fieldindex;
		
	
	/**
	 * Class constructor
	 *
	 * @param Q\DB|Q\DB_Table $source
	 * @param object|resource $native     The native result object or resource
	 * @param string          $statement  The query statement which created this result
	 */
	public function __construct($source, $native, $statement)
	{
	    if ($source instanceof DB) {
		    $this->link = $source;
	    } elseif ($source instanceof DB_Table) {
	        $this->basetable = $source;
	        $this->link = $source->getConnection();
	    } else {
	        throw new Exception("Parent of result can only be a Q\DB or Q\DB_Table, not a " . (is_object($source) ? get_class($source) : gettype($source)));
	    }
	    
		$this->native = $native;
		$this->statement = $statement;
	}	
	
	
	/**
	 * Get the database connection
	 * 
	 * @return DB
	 */
	public function getConnection()
	{
		return $this->link;
	}
	
	/**
	 * Get the native result object or resource
	 * 
	 * @return object|resource
	 */
	public function getNative()
	{
		return $this->native;
	}

	/**
	 * Get the query statement which created this result
	 * 
	 * @return string
	 */
	public function getStatement()
	{
		return $this->statement;
	}

	/**
	 * Set the factory name for the record
	 * 
	 * @param string $class
	 */
	public function setRecordType($class)
	{
		return $this->recordtype = $class;
	}
		
	/**
	 * Return the factory name for the record
	 * 
	 * @return string
	 */
	public function getRecordType()
	{
		if (!isset($this->recordtype)) {
			$bt = $this->getBaseTable();
			$this->recordtype = $bt ? $bt->getRecordType() : 'Q/DB_Record';
		}
		return $this->recordtype;
	}
		
	
	/**
	 * Get the number of different tables used in resultset
	 * 
	 * @return int
	 */
	abstract public function numTables();
	
	/**
	 * Get the number of fields/columns
	 * 
	 * @return int
	 */
	abstract public function numFields();
	
    
	/**
	 * Get the table definition, responsible for this result
	 *
	 * @return Table
	 */
	public function getBaseTable()
	{
	    if ($this->basetable === false) {
	        $table = $this->seekTableName(0);
	        if ($table) {
	            $refs = $this->getTableRefs();
	            $this->basetable = $this->link->table($refs[$table]);
	        } else {
	            $this->basetable = null;
	        }
	    }
	    
	    return $this->basetable;
	}
	
	
	/**
	 * Returns the names (or alias) for all tables
	 * 
	 * @return array
	 */	
	abstract public function getTableNames();

	/**
	 * Return the db names and aliases for all tables as array(alias=>dbname, ...)
	 * 
	 * @return array
	 */	
	abstract public function getTableRefs();
	
	/**
	 * Returns the table name (or alias) for a specific column
	 * 
	 * @return array
	 */	
	abstract public function seekTableName($column, $alias=true);
				
	/**
	 * Returns the fieldnames for all columns
	 * 
     * @param int    $format  DB::FIELDNAME_* constant
	 * @return array
	 */	
	abstract public function getFieldNames($format=DB::FIELDNAME_COL);

	/**
	 * Return the fieldname of a field, based on the position
	 * 
	 * @param int $index
	 * @return string
	 */
	abstract public function getFieldName($index, $format=DB::FIELDNAME_COL);
	
	/**
	 * Return the position of a field, based on the fieldname
	 * 
	 * @param string $index
	 * @return int
	 */
	public function getFieldIndex($index)
	{
		return is_int($index) ? $index : (isset($this->fieldindex[$index]) ? $this->fieldindex[$index] : null);
	}
	
	
	/**
	 * (Re)index the fieldnames.
	 */
	protected function refreshFieldIndex()
	{
	    $table = $this->getBaseTable()->getName();
	    
		foreach ($this->fields as $key=>$field) {
			$this->fieldindex[$field->getName()] = $key;
			$this->fieldindex[$field->getFullname()] = $key;
			if ($field->getDbname()) {
				$this->fieldindex[$field->getDbname()] = $key;
				if (!isset($this->fieldindex[$field->getDbname(false)])) $this->fieldindex[$field->getDbname(false)] = $key;
			}

			$fieldprops =& $field->getProperties();
			
			// Add field mapping based on properties (like role)
			foreach (DB::$mappingProperties as $mp) {
				// Check table bound
				if ($mp[0] == '~') {
				    if (!isset($fieldprops['table_def']) || $fieldprops['table_def'] !== $table) continue;
				    $mp[0] = substr($mp, 1);
				}

				if (isset($fieldprops[$mp])) {
					if (!is_bool($fieldprops[$mp]) && $fieldprops[$mp] !== '0' && $fieldprops[$mp] !== '1') {
						foreach ((array)$fieldprops[$mp] as $mv) {
							// Make symantic mapping if: it does not exist OR the new field is of $table and the old is not OR they are the same, but the old field is an alias and the new field not (and preference to alias $mp:$mv)
							$rf = isset($this->fieldindex["#$mp:$mv"]) ? $this->fields[$this->fieldindex["#$mp:$mv"]] : null;
							if (!isset($rf) || $rf->getProperty("auto:$mp:$mv") || (isset($table) && $fieldprops['table'] === $table && $rf->getProperty('table') !== $table) || ($fieldprops['table'] === $rf->getProperty('table') && ($fieldprops['name'] === $fieldprops['name_db'] || ($fieldprops['name'] === "$mp:$mv" && $rf->getProperty('name') !== $rf->getProperty('name_db'))))) {
								$this->fieldindex["#$mp:$mv"] = $key;
								if ($mp === 'role') $this->roles[$mv] = $key;
							}
						}
					} elseif ($mval) {
						$rf = isset($this->fieldindex["#$mp"]) ? $this->fields[$this->fieldindex["#$mp"]] : null;
						if (!isset($rf) || (isset($table) && $fieldprops['table'] === $table && $rf->getProperty('table') !== $table) || ($fieldprops['table'] === $rf->getProperty('table') && ($fieldprops['name'] === $fieldprops['name_db'] || ($fieldprops['name'] === "$mp" && $rf->getProperty('name') !== $rf->getProperty('name_db'))))) {
    					    $this->fieldindex["#$mp"] = $key;
						}
					}
				}
			}
		}
	}

	/**
	 * Return properties taken from result fields as array(props, default props)
	 * 
	 * @return array
	 */
	abstract protected function fetchFieldProperties();
	
	/**
	 * Create fields based on the result.
	 */	
	protected function initFields()
	{
	    $this->fields = array();
	    
	    if ($this->getBaseTable()) {
	        $meta[null] = $this->getBaseTable()->getProperties();
	        $meta[$this->getBaseTable()->getTablename()] =& $meta[null];
	    } else {
	        $meta[null] = array();
	    }
	    
	    foreach ($this->fetchFieldProperties() as $p) {
    	    list($props, $props_def) = $p;
    	    
    	    $tbl = !empty($props['table_db']) ? $props['table_db'] : null; 
    	    if (!isset($meta[$tbl])) $meta[$tbl] =& $this->link->getMetaData($tbl);
    	    $fieldmeta = isset($meta[$tbl][$props['name_db']]) ? $meta[$tbl][$props['name_db']] : array();

    		// Apply alias settings
			if ($props['name'] != $props['name_db']) {
			    unset($fieldmeta['description']);
			    $key = strpos($props['name'], ':') !== false ? "#{$props['name']}" : "#alias:{$props['name']}";
		        if (isset($meta[null][$key])) $fieldmeta = $meta[null][$key] + $fieldmeta;
			}

			// Fix: Enum and set fields in query results are interpreted as string
			if ($props['type'] === 'char' && isset($fieldmeta['type']) && ($fieldmeta['type'] === 'enum' || $fieldmeta['type'] === 'set')) unset($props['type']);

			$props = $props + $fieldmeta + $props_def;
			if (!isset($props['table_def'])) $this->link->applyFieldDefaults($props);
			
    		$this->fields[] = DB_Field::create($this, $props);
	    }
	    
		$this->refreshFieldIndex();
	}
	
	/**
	 * Return a specific field.
	 * 
	 * @param mixed $index  Field name or index
	 * @return DB_Field
	 */	
	public function getField($index)
	{
		if (!isset($this->fields)) $this->initFields();
		
		$index = $this->getFieldIndex($index);
		return isset($index) ? $this->fields[$index] : null;
	}	

	/**
	 * Returns all fields
	 * 
	 * @return array
	 */
	function getFields()
	{
		if (!isset($this->fields)) $this->initFields();
		return $this->fields;
	}
		
	/**
	 * Returns the roles with field index
	 * 
	 * @return array
	 */	
	function getRoles()
	{
		if (!isset($this->roles)) $this->initFields();
		return $this->roles;
	}

	/**
	 * Return array(fields, fieldindex, fieldnames, tablerefs)
	 * 
	 * @return array
	 */
    public function getInternalInfo()
    {
        return array($this->getFields(), $this->fieldindex, $this->getFieldNames(), $this->getTableRefs());
    }
	
	
	/**
	 * Set result pointer to the first row of the result
	 */
	abstract public function resetPointer();
	
	/**
	 * Get the number of rows.
     * For better readability use: $result->countRows(DB::ALL_ROWS)
     * 
     * @param boolean $all  Get the number of rows that would be returned for a statement with limit
	 * @return int
	 */
	abstract public function countRows($all=false);

	/**
	 * Get the number of rows (alias of countRows).
     * 
	 * @return int
	 */
	public final function numRows()
	{
	    return $this->countRows();
	}
	
	/**
	 * Fetch a result row in a specific format.
	 * 
	 * {@internal Overwrite this method to improve performance.}}
	 * 
	 * @param int $resulttype  A DB::FETCH_% constant
	 * @return array
	 */
	public function fetch($resulttype=0)
	{
		if ($resulttype & 0xFF == 0) $resulttype |= $this->fetchMode; 
	    $opt = $resulttype & ~0xFF;
	    
		switch ($resulttype & 0xFF) {
			case DB::FETCH_ORDERED:   return $this->fetchOrdered($opt);
			case DB::FETCH_ASSOC:     return $this->fetchAssoc($opt);
			case DB::FETCH_FULLARRAY: return $this->fetchFullArray($opt);
			case DB::FETCH_PERTABLE:  return $this->fetchPerTable($opt);
			case DB::FETCH_VALUE:     return $this->fetchValue(0, $opt);
			case DB::FETCH_RECORD:    return $this->fetchRecord($opt);
			case DB::FETCH_ROLES:     return $this->fetchRoles($opt);
			case DB::FETCH_OBJECT:    return $this->fetchObject($opt);
			
			default: throw new Exception("Unable to fetch row: Unknown result type '$resulttype'");
		}
	}

	/**
	 * Alias of Q\DB::fetch().
	 * 
	 * @param int $resulttype
	 * @return array
	 */
	final public function fetchRow($resulttype=0)
	{
		return $this->fetch($resulttype);
	}
	
	/**
	 * Fetch a result row as a numbered array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	abstract public function fetchOrdered($opt=0);
		
	/**
	 * Fetch a result row as an associative array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	abstract public function fetchAssoc($opt=0);

	/**
	 * Fetch a result row as a simple object
	 * 
     * @param string $class  Name of the class to create.
     * @param array  $config Constructor arguments for the class.
	 * @param int    $opt    Additional options as binary list
	 * @return object
	 */
	public function fetchObject($class=null, $config=array(), $opt=0)
	{
		$values = $this->fetchAssoc($opt);
		if (!isset($values)) return null;
		
		if (!isset($class)) return (object)$values;
		
		$object = new $class($config);
		foreach ($values as $key=>$value) {
			$object->$key = $value;
		}
		return $object;
	}
	
	/**
	 * Fetch a result row as a combination of a numbered array and an associative array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	abstract public function fetchFullArray($opt=0);

	/**
	 * Fetch a result row as an associative array, group per table.
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchPerTable($opt=0)
	{
		$values = array();
		$row = $this->fetchOrdered();
		if (!isset($row)) return null;
		
		foreach ($this->getFields() as $field) {
		    list(, $values[$field->table][$field->name]) = each($row);
		}
		
		return $values;
	}	
	
	/**
	 * Fetch row and return a single value.
	 * 
	 * @param mixed $column   Field name or index
	 * @param int   $opt      Additional options as binary list
	 * @return mixed
	 */
	public function fetchValue($column=0, $opt=0)
	{
		if (is_int($column)) $row = $this->fetchOrdered($opt);
		 else $row = $this->fetchAssoc($opt);
		 
		return isset($row[$column]) ? $row[$column] : null;
	}

	/**
	 * Fetch a result row as an associative array with the roles as keys
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchRoles($opt=0)
	{
		$values = $this->fetchOrdered($opt);
		if (!isset($values)) return null;
		
		$row = array();
		foreach ($this->getRoles() as $name=>$i) $row[$name] = $values[$i];
		return $row; 
	}
	
	/**
	 * Returns the current row of a result set as DB_Record
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return DB_Record
	 */
	public function fetchRecord($opt=0)
	{
		$row = $this->fetchOrdered($opt);
		return isset($row) ? DB_Record::create($this, $row) : null;
	}

	/**
	 * Returns a new record based on the fields of the result
	 * 
	 * @return DB_Record
	 */
	public function newRecord()
	{
		return DB_Record::create($this);
	}
		
	
	/**
	 * Get the rows where a column has a specific value.
	 * 
	 * @param mixed $column      Fieldname(string) or index(int)
	 * @param mixed $value
	 * @param int   $resulttype  A DB::FETCH_% constant
	 * @return array
	 */
	abstract public function seekRows($column, $value, $resulttype=0);
	
	
	/**
	 * Returns all values from a single column.
	 * 
	 * @param mixed $column  Field name(string) or index(int)
	 * @param mixed $key_col Field to use as associated key
	 * @param int   $opt     Additional options as binary list
	 * @return array
	 */
	public function fetchColumn($column=0, $key_col=null, $opt=0)
	{
	    $values = null;
	    if (!isset($key_col)) $key_col = $this->getFieldIndex('result:key');
		
		if (isset($key_col)) while (($row = $this->fetchFullArray($opt))) $values[$row[$key_col]] = $row[$column];
		  else while (($row = $this->fetchFullArray($opt))) $values[] = $row[$column];
	    
		$this->resetPointer();
		return $values;
	}

	/**
	 * Alias of Q\DB_Result::fetchColumn().
	 * 
	 * @param mixed $column  Field name(string) or index(int)
	 * @param mixed $key_col Field to use as associated key
	 * @param int   $opt     Additional options as binary list
	 * @return array
	 */
	final public function fetchCol($column, $key_col, $opt)
	{
		return $this->fetchColumn($column, $key_col, $opt);
	}
	
	/**
	 * Returns the values of all rows.
	 * 
	 * @param int $resulttype A DB::FETCH_% constant
	 * @return array
	 */
	public function fetchAll($resulttype=0)
	{
		if ($resulttype & 0xFF == 0) $resulttype |= $this->fetchMode; 
		$opt = $resulttype & ~0xFF;
	    $rows = array();
	    
		switch ($resulttype & 0xFF) {
			case DB::FETCH_ORDERED:   while (($row = $this->fetchOrdered($opt))) $rows[] = $row; break;
			case DB::FETCH_ASSOC:     while (($row = $this->fetchAssoc($opt))) $rows[] = $row; break;
			case DB::FETCH_FULLARRAY: while (($row = $this->fetchFullArray($opt))) $rows[] = $row; break;
			case DB::FETCH_PERTABLE:  while (($row = $this->fetchPerTable($opt))) $rows[] = $row; break;
			case DB::FETCH_VALUE:     while (($row = $this->fetchValue($opt))) $rows[] = $row; break;
			case DB::FETCH_RECORD:    while (($row = $this->fetchRecord($opt))) $rows[] = $row; break;
			case DB::FETCH_ROLES:     while (($row = $this->fetchRoles($opt))) $rows[] = $row; break;
			case DB::FETCH_OBJECT:    while (($row = $this->fetchObject($opt))) $rows[] = $row; break;
			
			default: throw new DB_Exception("Unable to fetch rows: Unknown result type '$resulttype'");
		}
		
		$this->resetPointer();
		return $rows;
	}
	
	/**
	 * Don't call this unless you are a field and a mapping property changed.
	 * 
	 * @param Q\DB_Field  $field
	 * @param string      $prop   Property name
	 * @param string      $value  Property value
	 * 
	 * @todo Not yet implemented Q\DB_Result::remapField()
	 */
	public function remapField($fieldname, $prop, $value)
	{
	    
	}
}

