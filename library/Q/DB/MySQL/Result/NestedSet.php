<?php
namespace Q;

require_once 'Q/DB/MySQL/Result.php';

/**
 * DB abstraction layer for a mysql query result using the nested set model.
 * Warning! No check will be preformed to ensure the hierarcy. Missing children and adoption (because of missing parents) is allowed and are not reported.
 * 
 * @package    DB
 * @subpackage DB_MySQL
 */
class DB_MySQL_Result_NestedSet extends DB_MySQL_Result_Tree 
{
	const FETCH_CURRENT = 0x100000;
	
	const FIELDNAME_LEFT = "nested:left";
	const FIELDNAME_RIGHT = "nested:right";
	const FIELDNAME_CHILDREN = "nested:children";
	
	/**
	 * Current record
	 * @var int
	 */
	protected $record_ptr;

	
	/**
	 * Index of nested set by record pointer
	 * @var array
	 */
	protected $index_nested;
	
	/**
	 * Index of nested set by left key
	 * @var array
	 */
	protected $index_left;

	/**
	 * Index of nested set by right key
	 * @var array
	 */
	protected $index_right;
	
	
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
		$this->indexNested();
	}
	
	/**
	 * Index the nested keys
	 */
	protected function indexNested()
	{
		$i=0;
		while (($row = $this->native->fetch_assoc())) {
			$this->index_nested[$i] = $this->index_left[$row[self::FIELDNAME_LEFT]] = $this->index_right[$row[self::FIELDNAME_RIGHT]] = array($i, $row[self::FIELDNAME_LEFT], $row[self::FIELDNAME_RIGHT]);
			$i++;
		}
		ksort($this->index_left);
		ksort($this->index_right);
	}
	

	/**
	 * Set data pointer to fetch next sibling.
	 * 
	 * @return boolean
	 */
	protected function prepareFetchSibling()
	{
		if (empty($this->index_nested)) return false;
		
		if (!isset($this->record_ptr)) {
			list($i) = reset($this->index_left);
			$this->record_ptr = $i;
		} else {
			$pi = $this->index_nested[$this->record_ptr][2];

			// Use a loop to skip missing children and parents (adoption)
			while (true) { // Exit loop through return or break
				$pi++;
				if (isset($this->index_right[$pi]) || $pi > max(array_keys($this->index_right))) return false;
				
				if (isset($this->index_left[$pi])) {
					$this->record_ptr = $this->index_left[$pi][0];
					break;
				}
			}
		}
		
		$this->native->data_seek($this->record_ptr);
		return true;
	}
	
	/**
	 * Set data pointer to fetch first child.
	 * 
	 * @return boolean
	 */
	protected function prepareFetchChild()
	{
		if (empty($this->index_nested)) return false;
		
		if (!isset($this->record_ptr)) {
			list($i) = reset($this->index_left);
			$this->record_ptr = $i;
		} else {
			$pi = $this->index_nested[$this->record_ptr][1];
			
			while (true) { // Exit loop through break
				$pi++;
				if ($this->index_nested[$this->record_ptr][2] == $pi) return false;
				
				if (isset($this->index_left[$pi])) {
					$this->record_ptr = $this->index_left[$pi][0];
					break;
				}
			}
		}
		
		$this->native->data_seek($this->record_ptr);
		return true;
	}


	/**
	 * Fetch a result row as a numbered array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	function fetchOrdered($opt=0)
	{
		if (!($opt & self::FETCH_CURRENT) && !$this->prepareFetchSibling()) return null;
		$row = parent::fetchOrdered($opt);
		
		$ptr = $this->record_ptr;
		if (!($opt & DB::FETCH_NON_RECURSIVE) && isset($row) && $this->prepareFetchChild()) {
			$opt &= ~self::FETCH_CURRENT;
			$row[-1] = array();
			while (($subrow = $this->fetchOrdered($opt & (isset($row[-1]) ? 0 : self::FETCH_CURRENT)))) $row[-1][] = $subrow;
			$this->record_ptr = $ptr;
		}
		
		return $row;
	}

	/**
	 * Fetch a result row as an associative array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	function fetchAssoc($opt=0)
	{
		if (!($opt & self::FETCH_CURRENT) && !$this->prepareFetchSibling()) return null;
		$row = parent::fetchAssoc($opt);
		
		$ptr = $this->record_ptr;
		if (!($opt & DB::FETCH_NON_RECURSIVE) && isset($row) && $this->prepareFetchChild()) {
			$opt &= ~self::FETCH_CURRENT;
			$row[self::FIELDNAME_CHILDREN] = array();
			while (($subrow = $this->fetchFullArray($opt & (isset($row[-1]) ? 0 : self::FETCH_CURRENT)))) $row['nested:children'][] = $subrow;
			$this->record_ptr = $ptr;
		}
		
		return $row;
	}
	
	/**
	 * Fetch a result row as a combination of a numbered array and an associative array
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	function fetchFullArray($opt=0)
	{
		if (!($opt & self::FETCH_CURRENT) && !$this->prepareFetchSibling()) return null;
		$row = parent::fetchFullArray($opt);
		
		$ptr = $this->record_ptr;
		if (!($opt & DB::FETCH_NON_RECURSIVE) && isset($row) && $this->prepareFetchChild()) {
			$opt &= ~self::FETCH_CURRENT;
			$row[-1] = $row[self::FIELDNAME_CHILDREN] = array();
			while (($subrow = $this->fetchFullArray($opt & (isset($row[-1]) ? 0 : self::FETCH_CURRENT)))) $row[-1][] = $row['nested:children'][] = $subrow;
			$this->record_ptr = $ptr;
		}
		
		return $row;
	}

	/**
	 * Fetch a result row as an associative array, group per table
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	function fetchPerTable($opt=0)
	{
		if (!($opt & self::FETCH_CURRENT) && !$this->prepareFetchSibling()) return null;
		$row = parent::fetchPerTable($opt);
		
		$ptr = $this->record_ptr;
		if (!($opt & DB::FETCH_NON_RECURSIVE) && isset($row) && $this->prepareFetchChild()) {
			$opt &= ~self::FETCH_CURRENT;
			$row[self::FIELDNAME_CHILDREN] = array();
			while (($subrow = $this->fetchPerTable($opt & (isset($row[-1]) ? 0 : self::FETCH_CURRENT)))) $row['nested:children'][] = $subrow;
			$this->record_ptr = $ptr;
		}
		
		return $row;
	}

	/**
	 * Fetch a result row as an associative array, group per table
	 * 
	 * @param int $opt  Additional options as binary list
	 * @return array
	 */
	function fetchRoles($opt=0)
	{
		if (!($opt & self::FETCH_CURRENT) && !$this->prepareFetchSibling()) return null;
		$row = parent::fetchRoles($opt);
		
		$ptr = $this->record_ptr;
		if (!($opt & DB::FETCH_NON_RECURSIVE) && isset($row) && $this->prepareFetchChild()) {
			$opt &= ~self::FETCH_CURRENT;
			$row[self::FIELDNAME_CHILDREN] = array();
			while (($subrow = $this->fetchRoles($opt & (isset($row[-1]) ? 0 : self::FETCH_CURRENT)))) $row['nested:children'][] = $subrow;
			$this->record_ptr = $ptr;
		}
		
		return $row;
	}	
}

?>