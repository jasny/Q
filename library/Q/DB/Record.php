<?php
namespace Q;

require_once 'Q/DB.php';
require_once 'Q/DB/Field.php';
require_once 'Q/misc.php';

/**
 * An active record based on the fields of a table or query result
 * 
 * @package DB
 */
class DB_Record
{
	/**
	 * Database connection
	 * @var DB
	 */
	protected $_link;
	
	/**
	 * The fields
	 * @var array
	 */
	protected $_fields;

	/**
	 * Field index as array (name=>position, ...)
	 * @var array
	 */
	protected $_fieldIndex;

	/**
	 * Fields for the primairy keys (per table)
	 * @var array
	 */
	protected $_primaryKeyFields;


	/**
	 * The table definition, responsible for this record
	 * @var DB_Table
	 */
	protected $_baseTable;
	
	
	/**
	 * Name of each table used in the record
	 * @var array
	 */
	protected $_tablerefs;

	/**
	 * Name of each field within the record
	 * @var array
	 */
	protected $_fieldnames;

	/**
	 * Orignal values, before any changes
	 *
	 * @var array
	 */
	protected $_originalValues;
	
	
	/**
	 * The class constructor.
	 *
	 * @param Q\DB_Result|Q\DB_Table $source
	 * @param array                  $values  Ordered or associated array with values
	 * @return DB_Record
	 */
	public static function create($source=null, $values=null)
	{
	    $recordtype = isset($source) ? $source->getRecordType() : null;
        if (!isset(DB::$recordtypes[$recordtype])) throw new Exception("Recordtype '{$recordtype}', set for " . ($source instanceof DB_Table ? $source->getName() : "result") . ", is not defined.");
        $class = DB::$recordtypes[$recordtype];	          
        
        if (!load_class($class)) throw new Exception("Could not load class '{$class}', defined for '{$recordtype}'.");
	    return new $class($source, $values); 
	}
	
	/**
	 * The class constructor.
	 *
	 * @param Q\DB_Result|Q\DB_Table $source
	 * @param array                  $values  Ordered or associated array with values
	 */
	protected function __construct($source=null, $values=null)
	{
		if (!isset($source)) {
			$fields = array();
			foreach ((array)$values as $key=>$value) {
				$this->_fieldIndex[$key] = array_push($this->_fields, DB_Field::create(null, array('name'=>$key, 'table'=>null, 'type'=>gettype($value)), $value)) - 1;
			}
		} else {
			$this->_link = $source->getLink();
			$this->_baseTable = $source instanceof DB_Table ? $source : $source->getBasetable();   
			
            list($fields, $this->_fieldIndex, $this->_fieldnames, $this->_tablerefs) = $source->getInternalInfo();

            if (!isset($values)) {
                foreach ($fields as $i=>$fd) $this->_fields[$i] = $fd->asNewActive($this);
            } else {
                foreach ($fields as $i=>$fd) $this->_fields[$i] = $fd->asActive(isset($values[$fd->getName()]) ? $values[$fd->getName()] : (isset($values[$i]) ? $values[$i] : null), $this);
            }
		}
	}
	
	
	/**
	 * Get a value (ORM)
	 *
	 * @param string $index
	 * @return mixed
	 */
	public function __get($index)
	{
		$field = $this->getField($index);
		if (!$field) $field = $this->getField("#orm:$index");
		
		if (!isset($field)) {
			trigger_error("Unable to get value: Record does not hold field '$index'.", E_USER_WARNING);
			return null;
		}
		
		return (!$field->getProperty('orm') || $field->getProperty('orm') == $index) ? $field->getORMValue() : $field->getValue();
	}
	
	/**
	 * Set a value (ORM)
	 *
	 * @param string $index
	 * @return mixed
	 */
	public function __set($index, $value)
	{
		$this->setValue($index, $value);
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
	 * Get the table definition, responsible for this record
	 * 
	 * @return DB
	 */
	public function getBaseTable()
	{
		return $this->_baseTable;
	}
	
	
	/**
	 * Get the number of different tables used in resultset
	 * 
	 * @return int
	 */
	public function numTables()
	{
		return sizeof($this->_tablerefs);
	}
	
	/**
	 * Get the number of fields/columns
	 * 
	 * @return int
	 */
	public function numFields()
	{
		return sizeof($this->_fields);
	}

	
	/**
	 * Return the names (or alias) for all tables
	 * 
	 * @return array
	 */	
	public function getTablenames()
	{
		return array_keys($this->_tablerefs);
	}

	/**
	 * Return the db names and aliases for all tables as array(alias=>dbname, ...)
	 * 
	 * @return array
	 */	
	public function getTablerefs()
	{
		return $this->_tablerefs;
	}
		
	/**
	 * Return the fieldnames for all columns
	 * 
	 * @return array
	 */	
	public function getFieldnames()
	{
		return $this->_fieldnames;
	}


	/**
	 * Get a numeric key for a fieldname
	 * 
	 * @param string $index
	 * @return int
	 */
	protected function getFieldIndex($index)
	{
		return is_int($index) ? $index : (isset($this->_fieldIndex[$index]) ? $this->_fieldIndex[$index] : null);
	}
	
	
	/**
	 * Returns a specific field.
	 * 
	 * @param mixed $index  Field name or index
	 * @return DB_Field
	 */	
	public function getField($index)
	{
		$index = $this->getFieldIndex($index);
		return isset($index) && isset($this->_fields[$index]) ? $this->_fields[$index] : null;
	}
		
	/**
	 * Returns all fields.
	 * 
	 * @return array
	 */	
	public function getFields()
	{
		return $this->_fields;
	}
	
	/**
	 * Return a specific value
	 * 
	 * @param mixed $index
	 * @return array
	 */	
	public function getValue($index)
	{
		$field = $this->getField($index);
		if (!isset($field)) {
			trigger_error("Unable to get value: Record does not hold field '$index'.", E_USER_WARNING);
		    return null;
		}
		
		return $field->getValue();
	}

	/**
	 * Set a specific value.
	 * (Fluent interface)
	 *
	 * @param string $index
	 * @param mixed  $value
	 * @return DB_Record
	 */
	public function setValue($index, $value)
	{
		$field = $this->getField($index);
		if (!isset($field)) {
			trigger_error("Unable to set value: Record does not hold field '$index'.", E_USER_WARNING);
            return;
		}

		$field->setValue($value);
		return $this;
	}
	
	/**
	 * Return an ordered array with all values.
	 * 
	 * @param int $fetchmode
	 * @return array
	 */	
	public function getValues($fetchmode=DB::FETCH_ORDERED)
	{
		$values = array();
		switch ($fetchmode & 0xFF) {
			case DB::FETCH_ORDERED: foreach ($this->_fields as $index=>$field) $values[$index] = $field->getValue(); break;
			case DB::FETCH_ASSOC: foreach ($this->_fields as $field) $values[$field['name']] = $field->getValue(); break;
			case DB::FETCH_PERTABLE: foreach ($this->_fields as $field) $values[$field['table']][$field['name']] = $field->getValue(); break;
			default: throw new DB_Exception('Incorrect fetchmode');
		}
		return $values;
	}
	
	/**
	 * Set all values.
	 * (Fluent interface)
	 * 
	 * @param array $values  Ordered or associated array with values
	 * @return DB_Record
	 */	
	public function setValues($values)
	{
		foreach ($values as $index=>$value) {
		    $field = $this->getField($index);
		    if ($field) $field->setValue($value);
		}
		return $this;
	}

	/**
	 * Validate values
	 * (Fluent interface)
	 * 
	 * @param array $values  Ordered or associated array with values
	 * @return DB_Record
	 * 
	 * @throws Q\Validation_Exception if any value is invalid
	 * 
	 * @todo Implement validation for Q\DB_Record
	 */	
	public function validate()
	{
	    return $this;
	}
	
	/**
	 * Index the primairy key fiels per table
	 */
	protected function indexPrimairyKeyFields()
	{
		$this->_primaryKeyFields = array();
		
		// If there is not database connection rely on the 'id' role 
		if (!isset($this->_link) || empty($this->_tablerefs)) {
			$index = $this->getFieldIndex('#role:id');
			if (!isset($index)) $this->_primaryKeyFields[!empty($this->_baseTable) ? $this->_baseTable->getTablename() : 0][] = $this->_fieldnames[$index];
			return;
		}
		
		// Normal behaviour
		foreach ($this->_tablerefs as $name=>$dbtable) {
			$pkfields = null;
			$keys = $this->_link->getPrimaryKeys($dbtable);
			foreach ($keys as $key) {
				foreach ($this->_fields as $field) {
					if ($field['table'] == $name && $field['name_db'] == $key) {
						$pkfields[] = $field;
						break;
					}
				}
			}
			
			if (count($keys) !== count($pkfields)) continue; // 1 or more primary key fields for table is not in result
			$this->_primaryKeyFields[$name] = $pkfields;
		}
	}
	
	/**
	 * Get value(s) of primary key(s)
	 * 
	 * @param  mixed $table  Table index or name
	 * @return mixed
	 */
	public function getId($table=null)
	{
		if (!isset($table)) $table = isset($this->_baseTable) ? $this->_baseTable->getTablename() : 0;
		
		if (is_int($table) && !empty($this->_tablerefs)) {
			$tables = array_keys($this->_tablerefs);
			if (!isset($tables[$table])) throw new DB_Exception("Unable to get id of table number $table: Record only holds fields of " . count($tables) . " tables.");
			$table = $tables[$table];
		}
		
		if (!isset($this->_primaryKeyFields)) $this->indexPrimairyKeyFields();
		if (!isset($this->_primaryKeyFields[$table])) throw new DB_Exception("Unable to get id of table '$table': Record does not hold the fields for the primary key(s).");

		if (count($this->_primaryKeyFields[$table]) === 1) return $this->_primaryKeyFields[$table][0]->getValue();

		foreach ($this->_primaryKeyFields[$table] as $field) $ids[] = $field->getValue();
		return $ids;
	}
	
	
	/**
	 * Update or insert values to database.
	 * (Fluent interface)
	 * 
	 * @param  string $table  Only update values for specified table. NULL: update all tables
	 * @return DB_Record
	 * 
	 * @todo Storing child rows in Q\DB_Record::save() is not supported yet
	 * @todo Get fields from join part of statement instead of useing the foreign_table property
	 */
	public function save($table=null)
	{
		if (!isset($this->_link)) throw new Exception('Unable to perform update: Record does not have a database connection');

		if (!isset($this->_primaryKeyFields)) $this->indexPrimairyKeyFields();
		if (isset($table) && !isset($this->_primaryKeyFields[$table])) throw new Exception("Unable to update values for table '$table': Record does not have the primary key field(s) of this table.");
		  elseif (empty($this->_primaryKeyFields)) throw new Exception("Unable to update values: Record does not hold the fields for the primary key(s)");
		
		// Group the value
		$fields_table = array();
		$fields_foreign = array();
		$child_values = array();
		$child_rows = array();
		 
		foreach ($this->_fields as $field) {
			if ($field['readonly']) continue;

		    if ($field['table']) {
				$fields_table[$field['table']][] = $field;
				if ($field['foreign_table']) $fields_foreign[$field['foreign_table']][$field['table']] = $field;
			
			} elseif ($field['child_result']) {
				if ($field['child_type'] === DB::FETCH_VALUE) $child_values[] = $field;
				 else $child_rows[] = $field;
			}
		}

		$tables = isset($table) ? array($table) : array_keys($this->_primaryKeyFields);
		
		// Change to order in which the tables are updated
		if (sizeof($tables) > 1) {
			$srt = array();
			foreach ($tables as $curtbl) $srt[$curtbl] = isset($fields_foreign[$curtbl]) ? array_keys($fields_foreign[$curtbl]) : null;
			$tables = array_keys(refsort($srt, SORT_DESC));
		}
		
		// Store the values to each table with a primary key in the record 
		foreach ($tables as $curtbl) {
			$values = array();
			$dbtable = $this->_tablerefs[$curtbl];
			
			$idvals = null;
			foreach ($this->_primaryKeyFields[$curtbl] as $field) {
			    $idvals[$field['name_db']] = $field->getValueForSave();
			}
			
			foreach ($fields_table[$curtbl] as $field) {
			    if (!isset($values[$field['name_db']]) || $field['name'] == $field['name_db']) $values[$field['name_db']] = $field->getValueForSave();
			}
			$result = $this->_link->store($dbtable, $values);
			if ($result !== true) {
			    foreach ((array)$result as $i=>$rval) $this->_primaryKeyFields[$curtbl][$i]->setValue($rval);
			    if (isset($fields_foreign[$curtbl])) {
    			    foreach ($fields_foreign[$curtbl] as $field) $field->setvalue($result);
    			}
			}
		}
		
		// Save all values of multiselect fields
		foreach ($child_values as $field) {
			$curtbl = $field['child_result']->getTableName(0, false);
			$col_value = $field['child_result']->getFieldName(0, DB::FIELDNAME_DB);
			
			$parent_field = $this->getField($field['join_parent']);
			$val_parent = $parent_field ? $parent_field->getValue() : $field['parent_value'];
			
			$args = array($curtbl, array($field['join_child'], $col_value));
			if ($field->getValue()){
				foreach ($field->getValue() as $val) $args[] = array($val_parent, $val);
			}
			$qs = $this->_link->getQuerySplitter();
			if ($qs) $this->_link->prepare($qs->convertStatement($field['child_result']->getStatement(), 'DELETE'))->addCriteria($field['join_child'], $val_parent)->execute();
			 else $this->_link->delete($curtbl, array($field['join_child']=>$val_parent), DB_MULTIPLE_ROWS);
			
			call_user_func_array(array($this->_link, 'store'), $args);
		}
		
		// Save all values of fields with child rows
		if (!empty($child_rows)) {
			trigger_error("Storing child rows is not supported yet, sorry.", E_USER_WARNING);
		}
		
		return $this;
	}

	/**
	 * Delete record from database.
	 * 
	 * @return DB_Record
	 * 
	 * @todo Current only deletes record in basetable
	 */
	public function delete($table=null)
	{
    	if (!isset($this->_link)) throw new Exception('Unable to perform delete: Record does not have a database connection');
        if (!$this->getBaseTable()) throw new Exception('Unable to perform delete: Record does not have a base table');
        
        $this->getBaseTable()->delete($this->getId());
	}
	
	/**
	 * Don't call this unless you are a field and a mapping property changed.
	 * 
	 * @param Q\DB_Field $field
	 * @param string     $prop   Property name
	 * @param string     $value  Property value
	 * 
	 * @todo Not yet implemented Q\DB_Record::RemapField()
	 */
	public function RemapField($field, $prop, $value)
	{
	    //if (!isset($this->_fieldIndex
	}
}
?>