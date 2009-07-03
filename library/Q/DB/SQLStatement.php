<?php
namespace Q;

require_once 'Q/DB/Statement.php';

/**
 * Abstraction layer for SQL query statements.
 * All editing statements support fluent interfaces.
 * 
 * @package DB
 */
class DB_SQLStatement extends DB_Statement
{
	/**
	 * The query splitter to use.
	 * @var DB_QuerySplitter
	 */
	public $querySplitter;
	
	/**
	 * The type of the query (for each subset)
	 * @var array
	 */
	protected $queryType = array();
	
	
	/**
	 * The parts of the split base statement.
	 * @var array
	 */
	protected $parts;
	
	/**
	 * The column names ot the base statement.
	 * @var array
	 */
	protected $columns;
		
	
	/**
	 * The parts to add to base statement.
	 *
	 * @var array
	 */
	protected $partsAdd;

	/**
	 * The parts to replace parts of to base statement.
	 *
	 * @var array
	 */
	protected $partsReplace;
		
		
	/**
	 * The build statements
	 *
	 * @var string
	 */
	protected $cachedStatement;

	/**
	 * The build parts
	 *
	 * @var string
	 */
	protected $cachedParts;
		
	/**
	 * The query statements to count the number of records
	 *
	 * @var array
	 */
	protected $countStatement;

	
   	//------------- Class constructor ------------------------

	/**
	 * Class constructor
	 *
	 * @param Q\DB|Q\DB_Table $source
	 * @param string            $statement  Query statement
	 */
	public function __construct($source, $statement)
	{
		parent::__construct($source, $statement);
		
		if ($source instanceof self) $this->querySplitter = $source->querySplitter;
		  elseif (isset($this->link)) $this->querySplitter = $this->link->querySplitter;
	}
	
	
   	//------------- Get query type ------------------------
   	
	/**
     * Return the type of the query
     *
     * @param int $subset
     * @return string
     */
	public function getQueryType($subset=0)
	{
		if (array_key_exists($subset, $this->queryType)) return $this->queryType[$subset];
		
		if ($subset > 0) {
		    $sets = $this->querySplitter->extractSubsets($this->statement);
		    if (!isset($sets[$subset])) throw new Exception("Unable to get query type of subset $subset: Statement doesn't have $subset subqueries.");
		    $statement = $sets[$subset];
		} else {
		    $statement = $this->statement;
		}
		
		$this->queryType[$subset] = $this->querySplitter->getQueryType($statement);
		return $this->queryType[$subset];
	}


   	//------------- Get parts ------------------------

   	/**
     * Return all the parts of the base statement
     *
	 * @param boolean $extract  Extract subsets from main statement and split each subset seperatly
     * @return string
     */
	public function getParts($extract=false)
	{
		if (!isset($this->parts[(bool)$extract])) {
			if (!$extract) $this->parts[false] = $this->querySplitter->split($this->statement);
			 else $this->parts[true] = $this->querySplitter->extractSplit($this->statement);
		}
		
		return $this->parts[(bool)$extract];
	}

	/**
     * Return a specific part of the base statement
     *
	 * @param mixed $key     The key identifying the part
	 * @param int   $subset  Specify to wich subset the change applies (0=main query)
     * @return string
     */
	public function seekPart($key, $subset=0)
	{
		if ($subset == 0) {
			$parts = $this->getParts(false);
			return isset($parts[$key]) ? $parts[$key] : null;
			
		} else {
			$parts = $this->getParts(true);
			$parts[0] = $parts[$subset][$key];
			return $this->querySplitter->inject($parts);
		}
	}
		
	/**
     * Return the column names of the base statement
     *
	 * @param Passed arguments are parsed into prepared statement.
     * @return string
     */
	public function getColumns($subset=0)
	{
		if (!isset($this->columns[$subset])) {
			$statement = $subset == 0 ? $this->statement : $this->getBaseSubset($subset);
			$this->columns[$subset] = $this->querySplitter->splitColumns($statement);
		}
		return $this->columns[$subset];
	}
	
	/**
	 * Apply the added and replacement parts to the parts of the base query.
	 * Extracts subqueries only if neccesairy. $parts[0] = array(parts of main query) 
	 * 
	 * @return array
	 */
	public function buildParts()
	{
		if (isset($this->cachedParts)) return $this->cachedParts;
		
		$use_subsets = sizeof($this->partsAdd) > (int)isset($this->partsAdd[0]) || sizeof($this->partsReplace) > (int)isset($this->partsReplace[0]);
		if ($use_subsets) $sets_parts = $this->getParts(true);
		 else $sets_parts = array($this->getParts(false));
		
		foreach ($sets_parts as $i=>$parts) {
			if (!empty($this->partsReplace[$i])) $parts = array_merge($parts, $this->partsReplace[$i]);
			
			if (!empty($this->partsAdd[$i])) {
				foreach ($this->partsAdd[$i] as $key=>$newparts) { /* newparts[0] => prepend, newparts[1] => append */
					if ($key === 'columns' || $key === 'set' || $key === 'group by' || $key === 'order by') {
						$parts[$key] = join(', ', array_merge(isset($newparts[0]) ? $newparts[0] : array(), $parts[$key] !== null && trim($parts[$key]) !== '' ? array($parts[$key]) : array() , isset($newparts[1]) ? $newparts[1] : array()));
					} elseif ($key === 'values') {
						$parts[$key] = (isset($newparts[0]) ? ' (' . join('), (', $newparts[0]) . ')' : '') . (isset($newparts[0]) && trim($parts[$key]) !== '' ? ', ' : '') . $parts[$key] . (isset($newparts[1]) && trim($parts[$key]) !== '' ? ', ' : '') .  (isset($newparts[1]) ? ' (' . join('), (', $newparts[1]) . ')' : '');
					} elseif ($key === 'from' || $key === 'into' || $key === 'tables') {
						$parts[$key] = (isset($newparts[0]) ? join(' ', $newparts[0]) . ' ' : '') . '(' . $parts[$key] . ')' . (isset($newparts[1]) ? ' ' . join(' ', $newparts[1]) : '');
					} elseif ($key === 'where' || $key === 'having') {
						$items = array_merge(isset($newparts[0]) ? $newparts[0] : array(), $parts[$key] !== null && trim($parts[$key]) !== '' ? array($parts[$key]) : array() , isset($newparts[1]) ? $newparts[1] : array());
						if (!empty($items)) $parts[$key] = '(' . join(') AND (', $items) . ')';
					} else {
						$parts[$key] = (isset($newparts[0]) ? join(' ', $newparts[0]) . ' ' : '') . $parts[$key] . (isset($newparts[1]) ? ' ' . join(' ', $newparts[1]) : '');
					}
				}
			}
			
			$sets_parts[$i] = $parts;
		}
		
		$this->cachedParts = $sets_parts;
		return $sets_parts;
	}
	
	/**
	 * Get database name for mapped fieldname (starting with '#')
	 *
	 * @param mixed $column  String or array
	 * @return mixed
	 */
	public function getColumnDbName($column)
	{
   		if (is_array($column)) return array_map(array(__CLASS__, __FUNCTION__), $column);
		if ($column[0] !== '#') return $column;
		
		if (!$this->getBaseTable()) throw new DB_Exception("Unable to add criteria for column '$column'. Unable to resolve symantic data mapping, because statement does not have a base table. (It is not created by a DB_Table object)");
		$col_db = $this->getBaseTable()->getFieldProperty($column, 'name_db');
		if (empty($col_db)) throw new DB_Exception("Unable to add criteria for column '$column', no field with that mapping for table definition '" . $this->baseTable->getName() . "'.");
		return $this->querySplitter->makeIdentifier($this->getBaseTable()->getTablename(), $col_db);
	}
	
	
   	//------------- Get statement ------------------------
	
   	/**
     * Return a subquery without any added or replaced parts
     *
	 * @param int $subset  Specify to wich subset the change applies (0=main query)
	 * @param Additional arguments are parsed into subquery statement.
     * @return string
     */
	public function getBaseSubset($subset)
	{
		if ($subset == 0) {
			$statement = $this->statement;
		} else {
			$sets = $this->querySplitter->extractSubsets($this->statement);
			if (!isset($sets[$subset])) return null;
			
			$sets[0] = $sets[$subset];
			$statement = $this->querySplitter->injectSubsets($sets);
		}

		if (func_num_args() > 1) {
			$args = func_get_args();
			$statement = $this->querySplitter->parse($this->statement, $args);
		}
		
		return $statement;
	}
		
	/**
	 * Return the complete statement with any added or replaced parts.
	 * 
	 * @param Passed arguments are parsed into prepared statement.
	 * @return string
	 */
	public function getStatement()
	{
		$statement = empty($this->partsAdd) && empty($this->partsReplace) ? $this->statement : $this->buildStatement();
		if (func_num_args() == 0) return $statement;
		
		$args = func_get_args();
		return $this->link->parse($statement, $args);
	}

	/**
	 * Create a statement using the base query and the added and replacement parts.
	 * 
	 * @return string
	 */
	protected function buildStatement()
	{
		if (!isset($this->cachedStatement)) $this->cachedStatement = $this->querySplitter->joinInject($this->buildParts());
		return $this->cachedStatement;
	}
	

	//------------- Add/Set part ------------------------
	
	/**
	 * Add a statement to any part of the query.
	 * Use DB_SQLStatement::ADD_PREPEND in $options to prepend a statement (append is default)
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $statement
	 * @param int    $options    Addition options as binairy set.
	 * @param int    $subset     Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addToPart($key, $statement, $options=0, $subset=0)
	{
		if ($options & DB::ADD_REPLACE) return $this->replacePart($key, $statement, $options, $subset);
		
		$this->partsAdd[$subset][strtolower($key)][$options & DB::ADD_PREPEND ? 0 : 1][] = $statement;
		$this->refresh();
		return $this;
	}

	/**
	 * Replace any part of the query
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $statement
	 * @param int    $options    Addition options as binairy set.
	 * @param int    $subset     Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function replacePart($key, $statement, $options=0, $subset=0)
	{
		$this->partsReplace[$subset][strtolower($key)] = $statement;
		$this->refresh();
		
		return $this;
	}

	
   	//------------- Add/Set specific part ------------------------

   	/**
   	 * Add column to query statement.
   	 * 
   	 * NOTE: This function does not escape $column and does not quote values
	 *
	 * @param mixed $column   Column name or array with column names
	 * @param int   $options  Addition options as binairy set
	 * @param int   $subset   Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addColumn($column, $options=0, $subset=0)
   	{
   		$type = $this->getQueryType($subset);
   		$key = $type === 'UPDATE' || ($type === 'INSERT' && $this->seekPart('set', $subset) !== null) ? 'set' : 'columns';
   		
   		$column = $this->getColumnDbName($column);
		$this->addToPart($key, is_array($column) ? join(', ', $column) : $column, $options, $subset);
		return $this;
   	}

	/**
	 * Add a join statement to the from part.
	 *
	 * @param mixed  $table    tablename or "tablename ON querytable.column = tablename.column"
	 * @param string $join     join type: INNER JOIN, LEFT JOIN, etc
	 * @param string $on       "querytable.column = tablename.column"
	 * @param int    $options  Addition options as binairy set
	 * @param int    $subset   Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addTable($table, $join=null, $on=null, $options=0, $subset=0)
   	{
   		switch ($this->getQueryType($subset)) {
   			case 'INSERT':	$key = 'into'; break;
   			case 'UPDATE':	$key = 'tables'; break;
   			default:		$key = 'from';
   		}
   		
   		if (is_array($on)) $on = $this->getColumnDbName($on[0]) . ' = ' . $this->getColumnDbName($on[1]);
   		  
   		if ($options & DB::ADD_PREPEND) {
   			$this->addToPart($key, "$table $join", $options, $subset);
   			if (isset($on)) $this->addToPart($key, "ON $on", $options & ~DB::ADD_PREPEND, $subset);
   		} else {
			$this->addToPart($key, "$join $table" . (isset($on) ? " ON $on" : ""), $options, $subset);
   		}

		return $this;
   	}
   	   	
   	/**
   	 * Add a row of values to an "INSERT ... VALUES (...)" query statement.
   	 * 
	 * @param mixed $values   Statement (string) or array of values
	 * @param int   $options  Addition options as binairy set
	 * @param mixed $subset   Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addValues($values, $options=0, $subset=0)
   	{
   		if (is_array($values)) {
   			foreach ($values as $i=>$value) $values[$i] = $this->querySplitter->quote($value, 'DEFAULT');
   			$values = join(', ', $values);
   		}
   		$this->addToPart('values', $values, $options, $subset);
		return $this;
   	}
   	   	
	/**
	 * Add criteria as where or having statement as $column=$value.
	 * If $value == null and $compare == '=', $compare becomes 'IS NULL'.
	 * 
	 * NOTE: This function does not escape $column
	 *
	 * @param mixed  $column    Column name, column number or array of column names ($column[0]=$value OR $column[1]=$value)
	 * @param mixed  $value     Value or array of values ($column=$value[0] OR $column=$value[1])
	 * @param string $compare   Comparision operator: =, !=, >, <, =>, <=, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN, NOT IN, ALL and BETWEEN
	 * @param int    $options   Addition options (language specific) as binairy set
	 * @param int    $subset    Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addCriteria($column, $value, $compare="=", $options=0, $subset=0)
	{
		$this->querySplitter->addCriteria($this, $this->getColumnDbName($column), $value, $compare, $options, $subset);
		return $this;
	}
	
	/**
	 * Add WHERE statement to query statement
	 *
	 * @param string $statement  WHERE statement
	 * @param int    $options    Addition options as binairy set
	 * @param int    $subset     Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addWhere($statement, $options=0, $subset=0)
	{
 		$this->addToPart($options & DB::ADD_HAVING ? 'having' : 'where', $statement, $options, $subset);
		return $this;
	}

	/**
	 * Add group by statement to query
	 *
	 * @param string $statement  GROUP BY statement (string) or array with columns
	 * @param int    $options    Addition options as binairy set
	 * @param int    $subset     Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addGroupBy($statement, $options=0, $subset=0)
	{
		$statement = $this->getColumnDbName($statement);
		if (is_array($statement)) $statement = join(', ', $statement);
		
 		$this->addToPart('group by', $statement, $options, $subset);
		return $this;
	}
		
	/**
	 * Add ORDER BY statement to query statement.
	 * NOTE: In contrary of addStatement(), the statement is prepended by default (use DB_Statment_SQL::ADD_APPEND to append)
	 *
	 * @param mixed $statement  ORDER BY statement (string) or array with columns
	 * @param int   $options    Addition options as binairy set
	 * @param int   $subset     Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addOrderBy($statement, $options=0, $subset=0)
	{
		$statement = $this->getColumnDbName($statement);
		if (is_array($statement)) $statement = join(', ', $statement);
		
 		if (!($options & DB::ADD_APPEND)) $options |= DB::ADD_PREPEND;
		$this->addToPart('order by', $statement, $options, $subset);
		return $this;
	}

	/**
	 * Set the limit for the number of rows returned when excecuted.
	 *
	 * @param int|string $rowcount  Number of rows of full limit statement
	 * @param int        $offset    Start at row or Q\DB::arg('page', $page)
	 * @param int        $options   Addition options as binairy set
	 * @param int        $subset    Specify to wich subset the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function setLimit($rowcount, $offset=null, $options=0, $subset=0)
	{
	    if (is_object($offset)) $offset = $rowcount * ($offset->value-1);
	    
		$this->replacePart('limit', $rowcount . (isset($offset) ? " OFFSET $offset" : ""), $options, $subset);
		return $this;
	}

	//------------- Zend_DB compatiblity --------------------
	
	/**
	 * Adds a table and optional columns to the query.
	 * 
     * @param  array|string $table  The table name or an associative array relating table name to correlation name.
     * @param  array|string $cols   The columns to select from this table.
     * @param  string       $join   join type: INNER JOIN, LEFT JOIN, etc
     * @param  array|string $on     "querytable.column = tablename.column"
     * @param  string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	protected function addTableAndColumns($table, $join=null, $on=null, $cols='*', $schema=null)
	{
		$table = isset($schema) ? $this->querySplitter->makeIdentifier($schema, $table) : $this->querySplitter->quoteIdentifier($table);
		$this->addTable($table, $join, $on);
		
		foreach ((array)$cols as $col) {
			$this->addColumn($this->querySplitter->makeIdentifier($table, $col));
		}

		return $this;
	}
	
	/**
	 * Adds a FROM table and optional columns to the query.
	 * 
     * @param  array|string $table  The table name or an associative array relating table name to correlation name.
     * @param  array|string $cols   The columns to select from this table.
     * @param  string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function from($table, $cols='*', $schema=null)
	{
		return $this->addTableAndColumns($table, null, null, $cols, $schema);
	}

	/**
	 * Alias of Q\DB::joinInner()
	 * 
     * @param  array|string $table  The table name or an associative array relating table name to correlation name.
     * @param  array|string $on     "querytable.column = tablename.column"
     * @param  array|string $cols   The columns to select from this table.
     * @param  string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	final public function join($table, $on, $cols='*', $schema=null)
	{
		return $this->joinInner($table, $on, $cols, $schema);
	}
	
	/**
	 * Adds an INNER JOIN table and columns to the query.
	 * 
     * @param  array|string $table  The table name or an associative array relating table name to correlation name.
     * @param  string       $on     "querytable.column = tablename.column"
     * @param  array|string $cols   The columns to select from this table.
     * @param  string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinInner($table, $on, $cols='*', $schema=null)
	{
		return $this->addTableAndColumns($table, 'INNER JOIN', $on, $cols, $schema);
	}	

	/**
	 * Adds an INNER JOIN table and columns to the query.
	 * 
     * @param  array|string $table  The table name or an associative array relating table name to correlation name.
     * @param  string       $on     "querytable.column = tablename.column"
     * @param  array|string $cols   The columns to select from this table.
     * @param  string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinLeft($table, $on, $cols='*', $schema=null)
	{
		return $this->addTableAndColumns($table, 'LEFT JOIN', $on, $cols, $schema);
	}	
	
   	//------------- Finalize changes ------------------------
   	
	/**
     * Remove all the changes.
     * 
	 * @return DB_SQLStatement
     */
   	public function revert()
   	{
   		$this->partsAdd = null;
   		$this->partsReplace = null;
   		
   		$this->cachedStatement = null;
   		$this->cachedParts = null;
   		$this->countStatement = null;
   		
   		return $this;
   	}

	/**
	 * Commit all changes to base query.
	 * 
	 * @return DB_SQLStatement
	 */
	public function commit()
	{
		$this->statement = $this->getStatement();
		
		$this->parts = null;
   		$this->partsAdd = null;
   		$this->partsReplace = null;
   		
		$this->cachedStatement = null;
		$this->cachedParts = null;
		$this->countStatement = null;
		
		return $this;
	}

	/**
	 * Clear cached statement.
	 *
	 * @return DB_SQLStatement
	 */
	public function refresh()
	{
		$this->cachedStatement = null;
		$this->cachedParts = null;
		$this->countStatement = null;
		$this->emptyResult = null;
		
		return $this;
	}
		
	
	//------------- Excecute ------------------------

	/**
     * Excecute query with WHERE FALSE, returning an empty result.
     *
     * @return DB_Result
     */
   	protected function executeEmpty()
   	{
   	    if (isset($this->emptyResult)) return $this->emptyResult;
   	    
   		$qt = $this->getQueryType();
   		if ($qt !== 'SELECT' && ($qt !== 'INSERT' || $this->seekPart('query') == null)) throw new DB_Exception("Unable to get a result for a " . $this->getQueryType() . " query:\n" . $this->getStatement());
   		
   		$parts = $this->buildParts();
   		
   		if ($qt === 'INSERT') {
   			$matches = null;
   			if (sizeof($parts) > 1 && preg_match('/^\#sub(\d+)$/', trim($parts['query']), $matches)) $parts[0] = $parts[(int)$matches[1]];
   			 else $parts[0] = $this->querySplitter->split($parts['query']);
   		}
   		
   		$parts[0]['where'] = 'FALSE';
   		$parts[0]['having'] = '';
   		
   		$class = get_class($this);
   		$this->emptyResult = $this->link->query(new $class($this, $this->link->parse($this->querySplitter->joinInject($parts), false)));
   		return $this->emptyResult;
   	}
	
	/**
     * Return the number of rows that the resultset would contain if the statement was executed.
     * For better readability use: $result->countRows(DB::ALL_ROWS)
     * 
     * @param boolean $all  Don't use limit
     * @return int
     */
   	public function countRows($all=false)
   	{
   	    $all = (boolean)$all;
   		if (!isset($this->countStatement[$all])) {
   			$parts = $this->buildParts();
   			$this->countStatement[$all] = $this->link->parse($this->querySplitter->buildCountStatement(count($parts) == 1 ? reset($parts) : $this->getStatement(), $all), false);
   			if (!isset($this->countStatement[$all])) throw new DB_Exception("Unable to count rows for " . $this->getQueryType() . " query:\n" . $this->getStatement());
   		}
   		
   		return $this->link->query($this->countStatement[$all])->fetchValue();
   	}
   	
   	
	/**
	 * Load a record using this statement
	 *
	 * @param mixed $criteria    Value for the first column or array(column=>value)
	 * @param int   $resulttype  A DB::FETCH_% constant
	 * @return DB_Record
	 * 
	 * @throws Q\DB_Constraint_Exception when statement would return multiple records
	 */
	function load($criteria=null, $resulttype=DB::FETCH_RECORD)
	{
		$prepared = isset($criteria) && (!empty($this->partsAdd) || !empty($this->partsReplace)) ? $this->commitToNew() : $this;
   		
		try {
			if (isset($criteria)) {
				if (!is_array($criteria)) $criteria = array($this->querySplitter->quoteIdentifier(reset($this->getFieldnames(DB::FIELDNAME_DB)), true)=>$criteria);
				foreach ($criteria as $col=>$value) $prepared->addCriteria($col, $value);
			}
			
			if ($prepared->countRows() > 1) throw new DB_Constraint_Exception("Unable to load record: Statement returned multiple records.");
			$result = $this->link->query($prepared->getStatement());
			
		} catch (Exception $e) {}
		
		// finally
		if ($prepared === $this && isset($criteria)) $this->revert();

		if (isset($e)) {
		    if ($e instanceof DB_Constraint_Exception) throw $e;
		    $class = $e instanceof Exception ? get_class($e) : 'Q\Exception';
		    throw new $class("Unable to load record.", $e);
		}
		
		return $result->fetchRow($resulttype);
	}
	
	/**
	 * Create a new record using the fields of the result of this statement
	 * 
	 * @return DB_Record
	 */
	function newRecord()
	{
		return $this->executeEmpty()->newRecord();
	}
}

?>