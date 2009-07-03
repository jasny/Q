<?php
namespace Q;

require_once 'Q/DB/Result.php';

/**
 * DB abstraction layer for mysql query result.
 * 
 * @package    DB
 * @subpackage DB_MySQL
 */
class DB_MySQL_Result extends DB_Result 
{
	/**
	 * Native mysql result object
	 *
	 * @var mysqli_result
	 */
	protected $native;

	
	/**
	 * Db name and alias of each table used in the result as array(alias=>dbname)
	 *
	 * @var array
	 */
	protected $tablerefs;

	/**
	 * Table name (or alias) of each table for each column
	 *
	 * @var array
	 */
	protected $tablecols;
		
	/**
	 * Name of each field within the result as array(names, fullnames, dbnames)
	 *
	 * @var array
	 */
	protected $fieldnames;
	
	/**
	 * Created indexes
	 *
	 * @var array
	 */
	protected $indexes = array();
	
	/**
	 * Created index for `tree:key`
	 *
	 * @var array
	 */
	protected $key_index;	

	
	/**
	 * Class constructor
	 *
	 * @param Q\DB|Q\DB_Table $source
	 * @param mysqli_result   $native     The native result object or resource
	 * @param string          $statement  The query statement which created this result
	 */
	function __construct($source, \mysqli_result $native, $statement)
	{
		parent::__construct($source, $native, $statement);
	}

	/**
	 * Get the native result object or resource
	 * 
	 * @return mysqli_result
	 */
	function getNative()
	{
		return $this->native;
	}
		
	
	/**
	 * Set the fieldnames and tablenames.
	 * 
	 * @return array
	 */	
	protected function initFieldinfo()
	{
		$i = 0;
		$this->native->field_seek(0);
		
		while (($field = $this->native->fetch_field())) {
			$this->fieldnames[DB::FIELDNAME_COL][] = $field->name;
			$this->fieldnames[DB::FIELDNAME_FULL][] = (isset($field->table) ? $field->table . '.' . $field->name : $field->name);
			$this->fieldnames[DB::FIELDNAME_DB][] = (isset($field->table) ? '`' . $field->table . '`.`' . $field->orgname . '`' : $field->orgname);
			$this->fieldnames[DB::FIELDNAME_DBFULL][] = (isset($field->table) ? '`' . $field->table . '`.`' . $field->orgname . '`' : $field->orgname) . ($field->name != $field->orgname ? ' AS `' . $field->name . '`' : '');
			
			if (!isset($this->tablerefs[$field->table])) $this->tablerefs[$field->table] = $field->orgtable;
			$this->tablecols[] = $field->table;
			
			$this->fieldindex[$field->name] = $i;
			$this->fieldindex[$field->table . '.' . $field->name] = $i;
			$i++;
		}
	}
	
	
	/**
	 * Get the number of different tables used in resultset
	 * 
	 * @return  int
	 */
	function numTables()
	{
		if (!isset($this->tablerefs)) $this->initFieldinfo();
		return sizeof($this->tablerefs);
	}

	/**
	 * Get the number of fields/columns
	 * 
	 * @return  int
	 */
	function numFields()
	{
		return $this->native->field_count;
	}

	/**
	 * Return the names (or alias) for all tables
	 * 
	 * @return array
	 */	
	function getTableNames()
	{
		if (!isset($this->tablerefs)) $this->initFieldinfo();
		return array_keys($this->tablerefs);
	}

	/**
	 * Return the db names and aliases for all tables as array(alias=>dbname, ...)
	 * 
	 * @return array
	 */	
	function getTableRefs()
	{
		if (!isset($this->tablerefs)) $this->initFieldinfo();
		return $this->tablerefs;
	}

	/**
	 * Returns the table name (or alias) for a specific column
	 * 
	 * @return array
	 */	
	function seekTableName($column, $alias=true)
	{
		if (!isset($this->tablecols)) $this->initFieldinfo();
		
		$index = $this->getFieldIndex($column);
		if (!isset($this->tablecols[$index])) return null;
		return $alias ? $this->tablecols[$index] : $this->tablerefs[$this->tablecols[$index]];
	}
		
	/**
	 * Return the fieldnames for all columns
	 * 
	 * @param int $format  DB::FIELDNAME_* constant
	 * @return array
	 */	
	function getFieldNames($format=DB::FIELDNAME_COL)
	{
		if (!isset($this->fieldnames)) $this->initFieldinfo();
		return $this->fieldnames[$format];
	}

	/**
	 * Return the fieldname of a field, based on the position
	 * 
	 * @param int $index
	 * @param int $format  DB::FIELDNAME_* constants
	 * @return string
	 */
	function getFieldName($index, $format=DB::FIELDNAME_COL)
	{
		if (!isset($this->fieldnames)) $this->initFieldinfo();
		return isset($this->fieldnames[$format][$index]) ? $this->fieldnames[$format][$index] : null;
	}
	
	/**
	 * Get a numeric key for a fieldname
	 * 
	 * @param string $index
	 * @return int
	 */
	function getFieldIndex($index)
	{
		if (is_int($index)) return $index;
		
		if ($index[0] !== '#') {
		    if (!isset($this->fieldindex)) $this->initFieldinfo();
		} else {
		    if (!isset($this->fields)) $this->initFields();
		}
		  
		return isset($this->fieldindex[$index]) ? $this->fieldindex[$index] : null;
	}
	
	
	/**
	 * Return properties taken from result fields as array(props, default props)
	 * 
	 * @return array
	 */
	protected function fetchFieldProperties()
	{
		foreach ($this->native->fetch_fields() as $i=>$field) $props[] = $this->convertFieldProperties($field, $i);
		return $props;
	}

	/**
	 * Return an array of properties taken from a mysql field
	 * 
	 * @param stdClass $field  MySQL result field
	 * @param int      $i      Field index
	 * @return array
	 */
	protected function convertFieldProperties($field, $i)
	{
	    $props = array();
		$props[0]['name'] = $field->name;
		$props[0]['table'] = !empty($field->table) ? $field->table : null;
		$props[0]['name_db'] = !empty($field->orgname) ? $field->orgname : null;
		$props[0]['table_db'] = !empty($field->orgtable) ? $field->orgtable : null;
		$props[0]['type'] = $field->type === MYSQLI_TYPE_TINY && $field->length === 1 ? 'boolean' : DB_MySQL::$fieldtypes[$field->type];

		$props[1]['default'] = $field->def;
		$props[1]['maxlength'] = $field->length;
		$props[1]['decimals'] = $field->decimals;

        $props[1]['description'] = ucfirst(preg_replace(array("/_id$/", "/_/"), array("", " "), trim($field->name)));
        
        return $props;
	}
	
	
	/**
	 * Set result pointer to the first row of the result
	 */
	function resetPointer()
	{
		$this->native->data_seek(0);
	}
	
	/**
	 * Get the number of rows.
     * For better readability use: $result->countRows(DB::ALL_ROWS)
     * 
     * @param boolean $all  Get the number of rows that would be returned for a statement with limit
	 * @return int
	 */
	function countRows($all=false)
	{
	    if ($all) trigger_error("Count all rows of a result is not implemented yet, sorry.", E_USER_WARNING);
		return $this->native->num_rows;
	}

	
	/**
	 * Fetch a result row in a specific format.
	 * DB::FETCH_VALUE, fetches the value of the first column
	 * 
	 * @param  int   $resulttype  A DB::FETCH_% constant
	 * @return array
	 */
	public function fetchRow($resulttype=DB::FETCH_ORDERED)
	{
		switch ($resulttype & 0xFF) {
			case DB::FETCH_ORDERED:   return $this->native->fetch_row();
			case DB::FETCH_ASSOC:     return $this->native->fetch_assoc();
			case DB::FETCH_FULLARRAY: return $this->native->fetch_array();
			case DB::FETCH_PERTABLE:  return $this->fetchPerTable();
			case DB::FETCH_VALUE:     $row=$this->native->fetch_row(); return $row[0];
			case DB::FETCH_RECORD:    return $this->fetchRecord();
			case DB::FETCH_ROLES:     return $this->fetchRoles();
			case DB::FETCH_OBJECT:    return $this->native->fetch_object();
			
			default: throw new DB_Exception("Unable to fetch row: Unknown result type '" . ($resulttype & 0xFF) . "'");
		}
	}

	/**
	 * Fetch a result row as a numbered array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchOrdered($opt=0)
	{
		return $this->native->fetch_row();
	}
		
	/**
	 * Fetch a result row as an associative array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchAssoc($opt=0)
	{
		return $this->native->fetch_assoc();
	}

	/**
	 * Fetch a result row as a simple object
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return object
	 */
	public function fetchObject($opt=0)
	{
        return $this->native->fetch_object();	    	
	}
	
	/**
	 * Fetch a result row as a combination of a numbered array and an associative array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchFullArray($opt=0)
	{
		return $this->native->fetch_array();
	}
	
	/**
	 * Fetch a result row as an associative array, group per table
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchPerTable($opt=0)
	{
		$values = array();
		$row = $this->native->fetch_row();
		if (!isset($row)) return null;
		
		$this->native->field_seek(0);
		while (($field = $this->native->fetch_field())) list(, $values[$field->table][$field->name]) = each($row);
		
		return $values;
	}
			
	/**
	 * Fetch row and return a single value.
	 * 
	 * @param mixed $column  Field name or index
	 * @param int   $opt     Additional options as binary list
	 * @return mixed
	 */
	public function fetchValue($column=0, $opt=0)
	{
		$row = $this->native->fetch_array();
		return isset($row[$column]) ? $row[$column] : null;
	}

	/**
	 * Create an index on a specific field, so al rows with a specific value for that field can be quickly found.
	 * NOTE: resets the result pointer.
	 * 
	 * @param mixed   $column  Fieldname(string) or index(int)
	 * @param boolean $unique
	 */
	protected function indexColumn($column, $unique=false)
	{
		if (array_key_exists($column, $this->indexes)) return;

		$this->indexes[$column] = null;
		
	    if (!is_int($column)) {
		    $column = $this->getFieldIndex($column);
		    if (!isset($column)) return;
		}
		$fieldname = $this->getFieldName($column, DB::FIELDNAME_FULL);
		if (!isset($fieldname)) return;

		$this->indexes[$column] = null;
		foreach (array_keys($this->fieldindex, $column) as $fieldname) $this->indexes[$fieldname] =& $this->indexes[$column];
				
		$i = 0;
		$this->native->data_seek(0);
		while (($row = $this->native->fetch_row())) $this->indexes[$column][$row[$column]][] = $i++;
		
		$this->native->data_seek(0);
	}
	
	/**
	 * Get the rows where a column has a specific value using an index.
	 * 
	 * @param mixed $column      Fieldname(string) or index(int)
	 * @param mixed $value
	 * @param int   $resulttype  A Q\DB_Result::FETCH_% constant
	 * @return array
	 */
	function seekRows($column, $value, $resulttype=DB::FETCH_ORDERED)
	{
		if (!isset($this->key_index)) {
		    $key_col = $this->getFieldIndex('tree:key');
		    if (!isset($key_col)) {
		        $this->key_index = false;
		    } else {
		         $this->native->data_seek(0);
		         while (($row = $this->native->fetch_row())) $this->key_index[] = $row[$key_col];
		     }
		}
		
	    if (!isset($this->indexes[$column])) $this->indexColumn($column);
		
		if (!isset($this->indexes[$column][$value])) return null;

		$prev_nr = null;
		$rows = array();
		
		foreach ($this->indexes[$column][$value] as $nr) {
			if (!isset($prev_nr) || $nr !== $prev_nr+1) $this->native->data_seek($nr);
			if ($this->key_index) $rows[$this->key_index[$nr]] = $this->fetchRow($resulttype);
             else $rows[] = $this->fetchRow($resulttype);
			$prev_nr = $nr;
		}
		
		return $rows;
	}
	
	/**
	 * Returns all values from a single column.
	 * CAUTION: resets the result pointer.
	 * 
	 * @param mixed $column  Field name(string) or index(int)
	 * @param int   $opt     Additional options as binary list
	 * @return array
	 */
	function getColumn($column=0, $opt=0)
	{
		if (is_string($column) && $column[0] === '#') $column = $this->getFieldIndex($column); 
		
		$this->native->data_seek(0);
		$row = $this->native->fetch_array();
		if (!isset($row[$column])) return null;
		
		$this->native->data_seek(0);
		$values = null;
		
		$key_field = $this->getFieldIndex('result:key');
		
		if (isset($key_field)) while (($row = $this->native->fetch_array())) $values[$row[$key_field]] = $row[$column];
		 else while (($row = $this->native->fetch_array())) $values[] = $row[$column];
		
		$this->native->data_seek(0);
		return $values;
	}
	

	/**
	 * Returns the values of all rows.
	 * CAUTION: resets the result pointer.
	 * 
	 * {@internal Mind the performance: not ifs in while loop}}
	 * 
	 * @param  int      $resulttype  A DB_Result::FETCH::% constant
	 * @param  boolean  $map         Add mapping for roles   
	 * @return array
	 */
	function getAll($resulttype=DB::FETCH_ORDERED)
	{
		if ($resulttype == DB::FETCH_VALUE) return $this->getColumn();

		$key_field = $this->getFieldIndex('result:key');
	
	    $rows = array();
		$this->native->data_seek(0);

		$opt = $resulttype & ~0xFF;
		
		if (isset($key_field)) {
    		switch ($resulttype & 0xFF) {
    			case DB::FETCH_ORDERED:   while (($row = $this->native->fetch_row())) $rows[$row[$key_field]] = $row; break;
    			case DB::FETCH_ASSOC:     while (($row = $this->native->fetch_assoc())) $rows[$row['result:key']] = $row; break;
    			case DB::FETCH_FULLARRAY: while (($row = $this->native->fetch_array())) $rows[$row[$key_field]] = $row; break;
    			case DB::FETCH_OBJECT:    while (($row = $this->native->fetch_object())) $rows[$row->{'result:key'}] = $row; break;
    			
    			default:
    			  while (($row = $this->fetchRow($resulttype))) $rows[] = $row;
				  if (!empty($rows)) $rows = array_combine($this->getColumn($key_field), $rows);
				  break;
    		}
		} else {
    		switch ($resulttype & 0xFF) {
    			case DB::FETCH_ORDERED:
    			    if (function_exists('mysqli_fetch_all')) $rows = $this->native->fetch_all(MYSQLI_NUM);
    				  else while (($row = $this->native->fetch_row())) $rows[] = $row;
    				break;
    			case DB::FETCH_ASSOC:
    			    if (function_exists('mysqli_fetch_all')) $rows = $this->native->fetch_all(MYSQLI_ASSOC);
                      else while (($row = $this->native->fetch_assoc())) $rows[] = $row;
                    break;
    			case DB::FETCH_OBJECT:
                    while (($row = $this->native->fetch_object())) $rows[] = $row;
                    break;
                case DB::FETCH_FULLARRAY:
                    if (function_exists('mysqli_fetch_all')) $rows = $this->native->fetch_all(MYSQLI_BOTH);
                      else while (($row = $this->native->fetch_array())) $rows[] = $row;
                    break;
    			
				case DB::FETCH_PERTABLE:  while (($row = $this->fetchPerTable($opt))) $rows[] = $row; break;
				case DB::FETCH_VALUE:     while (($row = $this->fetchValue(0, $opt))) $rows[] = $row; break;
				case DB::FETCH_RECORD:    while (($row = $this->fetchRecord($opt))) $rows[] = $row; break;
				case DB::FETCH_ROLES:     while (($row = $this->fetchRoles($opt))) $rows[] = $row; break;
    			default:                    throw new DB_Exception("Unable to fetch all rows: Unknown result type '$resulttype'");
    		}
		}
		$this->native->data_seek(0);
		
		return $rows;
	}

}

?>