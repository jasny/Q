<?php
namespace Q;

require_once 'Q/DB/MySQL/Result.php';

/**
 * DB abstraction layer for multiple mysql query results forming a tree result.
 * WARNING: Using tree's with mysql is simulated. Therefore the performance is not great. You should only use it for results with little (<100) rows.
 * 
 * @package    DB
 * @subpackage DB_MySQL
 */
class DB_MySQL_Result_Tree extends DB_MySQL_Result 
{
	/**
	 * The child results as array(pos=>array(result, join_parent, join_child, format), ...)
	 * @var array
	 */
	protected $children = array();
    
    
	/**
	 * Add a new child to the tree.
	 * NOTE: You may pass an array(column, result, format), instead of individual arguments
	 *
	 * @param int        $column  Column index
	 * @param DB_Result  $result
	 * @param int        $format
	 */
	public function addChild($column, $result, $format)
	{
		if (is_array($column)) {
			$child = $column;
			list($column, $result, $format) = $child;
		}
		
		$fi = $result->getFieldIndex('tree:join');
		if (!isset($fi)) throw new Exception("Unable to add result as child: Child result has no `tree:join` field.");

		$this->children[$column] = array($result, $column, $fi, $format);
	}
	
	/**
	 * Get a child result.
	 * 
	 * @param mixed $column  Column index (int) or column name (string)
	 * @return DB_MySQL_Result
	 */
	public function getChild($column)
	{
		if (!is_int($column)) $column = array_search($column, $this->getFieldnames());
		return $column !== false ? $this->children[$column][1] : null;
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
		if (!isset($this->children[$i])) return parent::convertFieldProperties($field, $i);
		
		$props[0]['name'] = $field->name;
		$props[0]['type'] = $this->children[$i][3] === DB::FETCH_VALUE ? 'array' : 'children';
		$props[0]['table'] = null;
		$props[0]['name_db'] = $props[0]['table_db'] = null;
		$props[0]['child_result'] = $this->children[$i][0];
		$props[0]['join_parent'] = $this->getFieldName($this->children[$i][1], DB::FIELDNAME_DB);
		$props[0]['join_child'] = $this->children[$i][0]->getFieldName($this->children[$i][2], DB::FIELDNAME_DB);
		$props[0]['child_type'] = $this->children[$i][3];
		$props[1]['default'] = array();
		return $props;
	}
	
    
	/**
	 * Fetch a result row in a specific format.
	 * DB::FETCH_VALUE, fetches the value of the first column
	 * 
	 * @param int $resulttype  A DB::FETCH_% constant
	 * @return array
	 */
	public function fetchRow($resulttype=DB::FETCH_ORDERED)
	{
	    $opt = $resulttype & ~0xFF;
	    
		switch ($resulttype & 0xFF) {
			case DB::FETCH_ORDERED:	return $this->fetchOrdered($opt);
			case DB::FETCH_ASSOC:		return $this->fetchAssoc($opt);
			case DB::FETCH_FULLARRAY:	return $this->fetchFullArray($opt);
			case DB::FETCH_PERTABLE:	return $this->fetchPerTable($opt);
			case DB::FETCH_VALUE:		return $this->fetchValue(0, $opt);
			case DB::FETCH_RECORD:		return $this->fetchRecord($opt);
			case DB::FETCH_ROLES:		return $this->fetchRoles($opt);
			
			default: throw new DB_Exception("Unable to fetch row: Unknown result type '$resulttype'");
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
		$row = $this->native->fetch_row();
		if (!isset($row)) return null;
		
		foreach ($this->children as $index=>$child) {
		    $row[$index] = $child[0]->seekRows($child[2], $row[$child[1]], $child[3] === DB::FETCH_VALUE ? DB::FETCH_VALUE : DB::FETCH_ORDERED);
		}
		return $row;
	}

	/**
	 * Fetch a result row as an associative array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchAssoc($opt=0)
	{
		$row = $this->native->fetch_assoc();
		if (!isset($row)) return null;
		
		$names = $this->getFieldNames();
		foreach ($this->children as $index=>$child) {
		    $row[$names[$index]] = $child[0]->seekRows($child[2], $row[$this->getFieldName($child[1])], $child[3] === DB::FETCH_VALUE ? DB::FETCH_VALUE : DB::FETCH_RECORD);
		}
		return $row;
	}
	
	/**
	 * Fetch a result row as a combination of a numbered array and an associative array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchFullArray($opt=0)
	{
		$row = $this->native->fetch_array();
		if (!isset($row)) return null;
		
		$names = $this->getFieldNames();		
		foreach ($this->children as $index=>$child) {
			$row[$index] = $child[0]->seekRows($child[2], $row[$child[1]], $child[3] == DB::FETCH_VALUE ? DB::FETCH_VALUE : DB::FETCH_ORDERED);
			$row[$names[$index]] = $child[3] == DB::FETCH_VALUE ? $row[$index] : $child[0]->seekRows($child[2], $row[$child[1]], DB::FETCH_ASSOC);
		}
		return $row;
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
		$row = $this->fetchOrdered($opt);
		if (!isset($row)) return null;
		
		$this->native->field_seek(0);
		while (($field = $this->native->fetch_field())) list(, $values[$field->table][$field->name]) = each($row);
		
		return $values;
	}

	/**
	 * Fetch a result row as an associative array with the roles as keys
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	public function fetchRoles($opt=0)
	{
		$row = $this->native->fetch_row();
		if (!isset($row)) return null;
		
		$values = array();
		$names = $this->getFieldNames(DB::FIELDNAME_ROLE);
		foreach ($names as $i=>$name) $values[$i] = $row[$name];

		foreach ($this->children as $index=>$child) {
			if (!isset($row[$names[$index]])) ; #continue 
			  elseif ($child[3] !== DB::FETCH_VALUE) $row[$names[$index]] = $child[0]->seekRows($child[2], $row[$child[1]], DB::FETCH_ROLES);
			  elseif (isset($names[$index])) $row[$names[$index]] = $child[0]->seekRows($child[2], $row[$child[1]], DB::FETCH_VALUE);
		}
		return $row;
	}
		
	/**
	 * Fetch row and return a single value.
	 * 
	 * @param mixed $column  Field name(string) or index(int)
	 * @param int   $opt     Additional options as binary list
	 * @return mixed
	 */
	public function fetchValue($column=0, $opt=0)
	{
		if (!is_int($column)) $column = $this->getFieldname($column);
		if (!isset($this->children[$column])) return parent::fetchValue($column);
		
		$child = $this->children[$column];
		$parent_value = $this->fetchValue($child[1]);
		return $child[0]->seekRows($child[1], $parent_value, $child[3]);
	}
	
	
	/**
	 * Returns all values from a single column.
	 * CAUTION: resets the result pointer.
	 * 
	 * @param mixed $column  Field name(string) or index(int)
	 * @param int   $opt     Additional options as binary list
	 * @return array
	 */
	public function getColumn($column=0, $opt=0)
	{
		if (!is_int($column)) $column = array_search($column, $this->getFieldnames());
		if (!isset($this->children[$column])) return parent::getColumn($column);

		$values = array();
		$child = $this->children[$column];
		
		if ($opt & DB::FETCH_CHILD_AS_RECORD && $child[3] !== DB::FETCH_VALUE) $child[3]= DB::FETCH_RECORD;
		
		$this->native->data_seek(0);
		while (($row = $this->native->fetch_row())) $values[] = $child[0]->seekRows($child[1], $row[$child[1]], $child[3]);
		$this->native->data_seek(0);
		
		return $values;
	}
	
	/**
	 * Returns the values of all rows.
	 * CAUTION: resets the result pointer.
	 * 
	 * @param int     $resulttype  A DB_Result_FETCH::% constant
	 * @param boolean $map         Add mapping for roles   
	 * @return array
	 */
	public function getAll($resulttype=DB::FETCH_ORDERED)
	{
		if ($resulttype == DB::FETCH_VALUE) return $this->getColumn();

		$key_field = $this->getFieldIndex('result:key');
	
	    $rows = array();
		$this->native->data_seek(0);

		$opt = $resulttype & ~0xFF;

		if (isset($key_field)) {
    		switch ($resulttype & 0xFF) {
    			case DB::FETCH_ORDERED:    while (($row = $this->fetchOrdered($opt))) $rows[$row[$key_field]] = $row; break;
    			case DB::FETCH_ASSOC:      while (($row = $this->fetchAssoc($opt))) $rows[$row['result:key']] = $row; break;
    			case DB::FETCH_FULLARRAY:  while (($row = $this->fetchFullArray($opt))) $rows[$row[$key_field]] = $row; break;
    			default:                   while (($row = $this->fetchRow($resulttype))) $rows[] = $row;
				                           $rows = array_combine($this->getColumn($key_field), $rows);
				                           break;
    		}
		} else {
    		while (($row = $this->fetchRow($resulttype))) $rows[] = $row;
		}
		$this->native->data_seek(0);
		
		return $rows;
	}
}

?>