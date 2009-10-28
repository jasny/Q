<?php
namespace Q;

require_once 'Q/DB/Statement.php';

/**
 * Abstraction layer for SQL query statements.
 * All editing statements support fluent interfaces.
 * 
 * @package DB
 */
class DB_SQLStatement implements DB_Statement
{
	/**
	 * Database connection
	 * @var DB
	 */
	protected $connection;
	
	/**
	 * The table definition responsible for this statement
	 * @var Q\DB_Table
	 */
	protected $basetable;
	
	/**
	 * Query statement
	 * @var string
	 */
	protected $statement;
	
	/**
	 * Cached empty result
	 * @var DB_Result
	 */
	protected $emptyResult;	

	
	/**
	 * The query splitter to use.
	 * @var DB_SQLSplitter
	 */
	public $sqlSplitter;
	
	/**
	 * The type of the query (for each subset)
	 * @var array
	 */
	protected $queryType;
	
	
	/**
	 * The parts of the split base statement.
	 * @var array
	 */
	protected $baseParts;
	
	/**
	 * The column names ot the base statement.
	 * @var array
	 */
	protected $baseColumns;

	
	/**
	 * The parts to replace the ones of the base statement.
	 * @var array
	 */
	protected $partsReplace;

	/**
	 * The parts to add to base statement.
	 * @var array
	 */
	protected $partsAdd;
	
	
	/**
	 * The build statements
	 * @var string
	 */
	protected $cachedStatement;

	/**
	 * The build parts
	 * @var string
	 */
	protected $cachedParts;
		
	/**
	 * The column names of the statement.
	 * @var array
	 */
	protected $cachedColumns;

	/**
	 * The values of an INSERT statement.
	 * @var array
	 */
	protected $cachedValues;
	
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
	 * @param mixed  $source     Optional: Q\DB, Q\DB_Table, Q\DB_SQLStatement or driver name (string)
	 * @param string $statement  Query statement
	 */
	public function __construct($statement)
	{
		if (func_num_args() > 1) {
			$source = func_get_arg(0);
			$statement = func_get_arg(1);
		} else {
			if ($statement instanceof self) $source = $statement;
			  else $source = DB::i();
			if ($source instanceof Mock) $source = null;
		}
		
		if (is_string($source)) {
			if (!isset(DB::$drivers[$source])) throw new Exception("Unable to create SQL statement: Unknown driver '$source'");
			$class = DB::$drivers[$source];
			if (!load_class($class)) throw new Exception("Unable to create SQL statement: Class '$class' for driver '$source' does not exist.");
			
			$refl = new ReflectionClass($class);
			$classes = $refl->getStaticPropertyValue('classes');
			if (isset($classes['sqlSplitter'])) $this->sqlSplitter = new $classes['sqlSplitter'](); 
		} elseif ($source instanceof DB) {
		    $this->connection = $source;
		    if (isset($this->connection->sqlSplitter)) $this->sqlSplitter = $this->connection->sqlSplitter;
	    } elseif ($source instanceof DB_Table) {
	        $this->connection = $source->getConnection();
	        $this->basetable = $source;
	        if (isset($this->connection->sqlSplitter)) $this->sqlSplitter = $this->connection->sqlSplitter;
	    } elseif ($source instanceof self) {
	        $this->connection = $source->getConnection();
	        $this->basetable = $source->getBaseTable();
	        if (isset($source->sqlSplitter)) $this->sqlSplitter = $source->sqlSplitter;
	    } elseif (isset($source)) {
	        throw new Exception("Parent of statement can only be a Q\DB or Q\DB_Table, not a " . (is_object($source) ? get_class($source) : gettype($source)));
	    }

		$this->statement = $statement;
	}
	
	/**
	 * Cast statement object to string.
	 *
	 * @return string
	 */
	public function __toString()
	{
	    return $this->getStatement();
	}
	
	/**
	 * Get the database connection.
	 * 
	 * @return DB
	 */
	function getConnection()
	{
		return $this->connection;
	}
	
	/**
	 * Get the table definition, responsible for this result
	 *
	 * @return Table
	 */
	public function getBaseTable()
	{
	    if (isset($this->basetable) && is_string($this->basetable)) $this->basetable = $this->connection->table($this->basetable);
	    return $this->basetable;
	}
	
	/**
	 * Get database name for column.
	 * Mapped fieldname (starting with '#') will be resolved.
	 *
	 * @param mixed  $column  Column name or column index, multiple columns may be specified as array
	 * @param string $table   Default table for column
	 * @param int    $flags   Options about how to quote $column
	 * @return string
	 */
	public function getColumnDBName($column, $table=null, $alias=null, $flags=0)
	{
   		if (is_array($column)) {
   			foreach ($column as $a=>&$c) $c = $this->getColumnDBName($c, $table, is_int($a) ? null : $a, $flags);
   			return $column;
   		}
		
   		if (is_int($column)) {
   			$fields = $this->getFields();
   			if (!isset($fields[$column])) throw new Exception("Unable to get the name of field $column: Statement only has " . count($column) . " fields");
   			return $this->sqlSplitter->makeIdentifier($fields[$column]['table'], $fields[$column]['name'], $alias);
   		}
   		
   		if ($column[0] !== '#') return $this->sqlSplitter->makeIdentifier($table, $column, $alias); // Most cases
		
   		if (!($table instanceof DB_Table)) $table = isset($table) && isset($this->connection) ? $this->connection->table($table) : $this->getBaseTable();
   		if (!$table) throw new DB_Exception("Unable to add criteria for column '$column'. Unable to resolve symantic data mapping, because statement does not have a base table.");
   		
		return $this->sqlSplitter->makeIdentifier($table, $table->$column, $alias);
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
		if (isset($this->queryType) && array_key_exists($subset, $this->queryType)) return $this->queryType[$subset];
		
		if ($subset > 0) {
		    $sets = $this->sqlSplitter->extractSubsets($this->statement);
		    if (!isset($sets[$subset])) throw new Exception("Unable to get query type of subset $subset: Statement doesn't have $subset subqueries.");
		    $statement = $sets[$subset];
		} else {
		    $statement = $this->statement;
		}
		
		$this->queryType[$subset] = $this->sqlSplitter->getQueryType($statement);
		return $this->queryType[$subset];
	}


   	//------------- Get base query ------------------------

	/**
     * Return the statement without any appended parts.
     *
	 * @param array $args  Argument to parse on placeholders
     * @return string
     */
   	public function getBaseStatement($args=array())
   	{
   		if ($args === array()) return $this->statement;
		
		if (func_num_args() > 1) $args = func_get_args();
		return $this->sqlSplitter->parse($this->statement, $args);
   	}

	/**
     * Return a subquery without any added or replaced parts
     *
	 * @param int   $subset
	 * @param array $args    Arguments to parse into subquery on placeholders 
     * @return string
     */
	public function getBaseSubset($subset, $args=array())
	{
		if ($subset == 0) {
			$statement = $this->statement;
		} else {
			$sets = $this->sqlSplitter->extractSubsets($this->statement);
			if (!isset($sets[$subset])) return null;
			
			$sets[0] = $sets[$subset];
			$statement = $this->sqlSplitter->injectSubsets($sets);
		}

		if ($args === array()) return $statement;
		
		if (func_num_args() > 2) $args = func_get_args();
		return $this->sqlSplitter->parse($this->statement, $args);
	}
	
	/**
     * Return all the parts of the base statement
     *
	 * @param boolean $extract  Extract subsets from main statement and split each subset seperatly
     * @return array
     */
	public function getBaseParts($extract=false)
	{
		if (!isset($this->baseParts[(bool)$extract])) {
			if (!$extract) $this->baseParts[false] = $this->sqlSplitter->split($this->statement);
			  else $this->baseParts[true] = $this->sqlSplitter->extractSplit($this->statement);
		}
		
		return $this->baseParts[(bool)$extract];
	}

	/**
     * Return a specific part of the base statement
     *
	 * @param mixed $key     The key identifying the part
	 * @param int   $subset  Get the parts of a subquery (0=main query)
     * @return string
     */
	public function getBasePart($key, $subset=0)
	{
		if ($subset == 0) {
			$parts = $this->getBaseParts(false);
			return isset($parts[$key]) ? $parts[$key] : null;
			
		} else {
			$parts = $this->getBaseParts(true);
			$parts[0] = $parts[$subset][$key];
			return $this->sqlSplitter->injectSubsets($parts);
		}
	}
		
	/**
     * Return the column names of the base statement.
     *
	 * @param boolean $splitFieldname  Split fieldname in array(table, field, alias)
	 * @param boolean $assoc           Remove '[AS] alias' (for SELECT) or 'to=' (for INSERT/UPDATE) and return as associated array
	 * @param int     $subset          Get the columns of a subquery (0=main query)
     * @return array
     */
	public function getBaseColumns($splitFieldname=false, $assoc=false, $subset=0)
	{
		if (!isset($this->baseColumns[$subset])) {
			$this->baseColumns[$subset] = $this->sqlSplitter->splitColumns($subset == 0 ? $this->statement : $this->getBaseSubset($subset), $splitFieldname, $assoc);
		}
		return $this->baseColumns[$subset];
	}
	
	/**
     * Return the values of the base statement.
     * Only for INSERT INTO ... VALUES ... statement.
     *
     * @return array
     */
	public function getBaseValues()
	{
		trigger_error("Sorry, parsing out values for an INSERT query is not yet implemented", E_USER_WARNING);
		return array();
	}
	
	
   	//------------- Get statement ------------------------
	
		
	/**
	 * Return the complete statement with any added or replaced parts.
	 * 
	 * @param array $args  Arguments to parse on place holders
	 * @return string
	 */
	public function getStatement($args=null)
	{
		if (empty($this->partsAdd) && empty($this->partsReplace)) {
			$stmt =& $this->statement;
		} else {
			if (!isset($this->cachedStatement)) $this->cachedStatement = $this->sqlSplitter->join($this->getParts());
			$stmt =& $this->cachedStatement;
		}
		
		if (func_num_args() == 0) return $stmt;
		
		if (func_num_args() > 1) $args = func_get_args();
		return $this->sqlSplitter->parse($this->cachedStatement, $args);
	}

	/**
	 * Apply the added and replacement parts to the parts of the base query.
	 * 
	 * @param boolean $extract  Extract subsets from main statement and split each subset seperatly
	 * @return array
	 */
	public function getParts($extract=false)
	{
		if (empty($this->partsAdd) && empty($this->partsReplace)) return $this->getBaseParts($extract);
		if (isset($this->cachedParts[$extract])) return $this->cachedParts[$extract];
		
		$use_subsets = $extract || sizeof($this->partsAdd) > (int)isset($this->partsAdd[0]) || sizeof($this->partsReplace) > (int)isset($this->partsReplace[0]);
		if ($use_subsets) $sets_parts = $this->getBaseParts(true);
		  else $sets_parts = array($this->getBaseParts(false));
		
		foreach ($sets_parts as $i=>&$parts) {
			if (!empty($this->partsReplace[$i])) $parts = array_merge($parts, $this->partsReplace[$i]);

			if (empty($this->partsAdd[$i])) continue;
			
			foreach ($this->partsAdd[$i] as $key=>&$partsAdd) {
				if (!empty($parts[$key])) $parts[$key] = trim($parts[$key]);
				
				if ($key === 'columns' || $key === 'set' || $key === 'group by' || $key === 'order by') {
					$parts[$key] = join(', ', array_merge(isset($partsAdd[DB::ADD_PREPEND]) ? $partsAdd[DB::ADD_PREPEND] : array(), !empty($parts[$key]) ? array($parts[$key]) : array(), isset($partsAdd[DB::ADD_APPEND]) ? $partsAdd[DB::ADD_APPEND] : array()));
				} elseif ($key === 'values') {
					$parts[$key] = (isset($partsAdd[DB::ADD_PREPEND]) ? ' (' . join('), (', $partsAdd[DB::ADD_PREPEND]) . ')' : '') . (isset($partsAdd[DB::ADD_PREPEND]) && !empty($parts[$key]) ? ', ' : '') . $parts[$key] . (isset($partsAdd[DB::ADD_APPEND]) && !empty($parts[$key]) ? ', ' : '') .  (isset($partsAdd[DB::ADD_APPEND]) ? ' (' . join('), (', $partsAdd[DB::ADD_APPEND]) . ')' : '');
				} elseif ($key === 'from' || $key === 'into' || $key === 'tables') {
					$parts[$key] = trim((isset($partsAdd[DB::ADD_PREPEND]) ? join(' ', $partsAdd[DB::ADD_PREPEND]) . ' ' : '') . (!empty($parts[$key]) ? '(' . $parts[$key] . ')' : '') . (isset($partsAdd[DB::ADD_APPEND]) ? ' ' . join(' ', $partsAdd[DB::ADD_APPEND]) : ''), ',');
				} elseif ($key === 'where' || $key === 'having') {
					$items = array_merge(isset($partsAdd[DB::ADD_PREPEND]) ? $partsAdd[DB::ADD_PREPEND] : array(), !empty($parts[$key]) ? array($parts[$key]) : array(), isset($partsAdd[DB::ADD_APPEND]) ? $partsAdd[DB::ADD_APPEND] : array());
					if (!empty($items)) $parts[$key] = '(' . join(') AND (', $items) . ')';
				} else {
					$parts[$key] = (isset($partsAdd[DB::ADD_PREPEND]) ? join(' ', $partsAdd[DB::ADD_PREPEND]) . ' ' : '') . (!empty($parts[$key]) ? $parts[$key] : '') . (isset($partsAdd[DB::ADD_APPEND]) ? ' ' . join(' ', $partsAdd[DB::ADD_APPEND]) : '');
				}
			}
		}
		
		if ($extract) $this->cachedParts[true] = $sets_parts;
		  else $this->cachedParts[false] = !$use_subsets ? $sets_parts[0] : $this->sqlSplitter->injectSubsets($sets_parts);
		
		return $this->cachedParts[$extract];
	}

	/**
	 * Check if statement has a specific part.
	 * 
	 * @param string $key     The key identifying the part
	 * @param int    $subset  Get the parts of a subquery (0=main query)
	 * @return boolean
	 */
	public function hasPart($key, $subset=0)
	{
		return !empty($this->partsAppend[$subset][$key]) || !empty($this->partsPrepend[$subset][$key]) || !empty($this->partsReplace[$subset][$key]) || (bool)$this->getBasePart($key, $subset); 
	}
	
	/**
     * Return a specific part of the statement.
     *
	 * @param mixed $key     The key identifying the part
	 * @param int   $subset  Get the parts of a subquery (0=main query)
     * @return string
     */
	public function getPart($key, $subset=0)
	{
		if ($subset == 0) {
			$parts = $this->getParts(false);
			return isset($parts[$key]) ? $parts[$key] : null;
		} else {
			$parts = $this->getParts(true);
			$parts[0] = $parts[$subset][$key];
			return $this->sqlSplitter->injectSubsets($parts);
		}
	}
	
	/**
	 * Get the columns used in the statement.
	 * 
	 * @param boolean $splitFieldname  Split fieldname in array(table, field, alias)
	 * @param boolean $assoc           Remove '[AS] alias' (for SELECT) or 'to=' (for INSERT/UPDATE) and return as associated array
	 * @param int     $subset          Get the columns of a subquery (0=main query)
	 * @return array
	 */
	public function getColumns($splitFieldname=false, $assoc=false, $subset=0)
	{
		if (empty($this->partsAdd) && empty($this->partsReplace)) return $this->getBaseColumns($splitFieldname, $assoc, $subset);
		if (array_key_exists($subset, $this->cachedColumns)) return $this->cachedColumns[$subset];
		
		if ($subset == 0) {
			$parts = $this->getParts();
		} else {
			$sets = $this->getParts(true);
			$sets[0] = $sets[$subset];
			$parts = $this->sqlSplitter->injectSubsets($sets);
		}
		
		if (isset($parts['columns'])) $this->cachedColumns[$subset] = $this->sqlSplitter->splitColumns($parts['columns']);
		  elseif (isset($parts['set'])) $this->cachedColumns[$subset] = $this->sqlSplitter->splitColumns($parts['set']);
		 
		return $this->cachedColumns[$subset];
	}

	/**
	 * Get the values used in the statement.
	 * Only for INSERT INTO ... VALUES ... query.
	 * 
	 * @return array
	 */
	public function getValues()
	{
		trigger_error("Sorry, parsing out values for an INSERT query is not yet implemented", E_USER_WARNING);
		return array();
	}
	
	/**
	 * Count the number of placeholders in the statement.
	 *
	 * @return int
	 */
	public function countPlaceholders()
	{
		return $this->sqlSplitter->countPlaceholders($this->getStatement());
	}

	
	//------------- Add/Set part ------------------------
	
	/**
	 * Add a statement to any part of the query.
	 * (fluent interface)
	 * 
	 * Use DB_SQLStatement::ADD_PREPEND in $flags to prepend a statement (append is default)
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $statement
	 * @param int    $flags      Addition options as binairy set.
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addToPart($key, $statement, $flags=0, $subset=0)
	{
		$key = strtolower($key);
		
		if ($flags & DB::ADD_REPLACE) $this->partsReplace[$subset][$key] = $statement;
		  else $this->partsAdd[$subset][$key][$flags & DB::ADD_PREPEND ? DB::ADD_PREPEND : DB::ADD_APPEND][] = $statement;
		
		$this->clearCachedStatement();
		if ($key == 'columns' || $key == 'set') $this->cachedColumns = null;
		if ($key == 'values') $this->cachedValues = null;
		
		return $this;
	}

	/**
	 * Replace any part of the query.
	 * (fluent interface)
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $statement
	 * @param int    $flags      Addition options as binairy set.
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function replacePart($key, $statement, $flags=0, $subset=0)
	{
		$this->addToPart($key, $statement, $flags | DB::ADD_REPLACE, $subset);
		return $this;
	}

	
   	//------------- Add/Set specific part ------------------------

   	/**
   	 * Add column to query statement.
   	 * 
   	 * NOTE: This function does not escape $column and does not quote values
	 *
	 * @param mixed $column   Column name or array with column names
	 * @param int   $flags    Addition options as binairy set
	 * @param int   $subset   Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addColumn($column, $flags=0, $subset=0)
   	{
   		$type = $this->getQueryType($subset);
   		$key = $type == 'UPDATE' || ($type == 'INSERT' && $this->hasPart('set', $subset)) ? 'set' : 'columns';
   		
   		$column = $this->getColumnDBName($column, null, null, $flags);
		$this->addToPart($key, is_array($column) ? join(', ', $column) : $column, $flags, $subset);
		return $this;
   	}

	/**
	 * Add a join statement to the from part.
	 *
	 * @param string $table    tablename
	 * @param string $join     join type: INNER JOIN, LEFT JOIN, etc
	 * @param string $on       "querytable.column = $table.column" or array(querytable.column, $table.column); 
	 * @param int    $flags    Addition options as binairy set
	 * @param int    $subset   Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addTable($table, $join=null, $on=null, $flags=0, $subset=0)
   	{
   		switch ($this->getQueryType($subset)) {
   			case 'INSERT':	$key = 'into'; break;
   			case 'UPDATE':	$key = 'tables'; break;
   			default:		$key = 'from';
   		}
   		
   		if (!isset($join) && ~$flags & DB::ADD_REPLACE) $join = ',';
   		if (is_array($on)) $on = $this->getColumnDbName($on[0], null, null, $flags) . ' = ' . $this->getColumnDbName($on[1], $table, null, $flags);
   		  
   		if ($flags & DB::ADD_PREPEND && ~$flags & DB::ADD_REPLACE) {
   			$this->addToPart($key, $this->sqlSplitter->quoteIdentifier($table, $flags) . ' ' . $join, $flags, $subset);
   			if (isset($on)) $this->addToPart($key, "ON $on", $flags & ~DB::ADD_PREPEND, $subset);
   		} else {
			$this->addToPart($key, $join . ' '. $this->sqlSplitter->quoteIdentifier($table, $flags) . (isset($on) ? " ON $on" : ""), $flags, $subset);
   		}

		return $this;
   	}
	
   	/**
   	 * Add a row of values to an "INSERT ... VALUES (...)" query statement.
   	 * 
	 * @param mixed $values   Statement (string) or array of values
	 * @param int   $flags    Addition options as binairy set
	 * @param mixed $subset   Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addValues($values, $flags=0, $subset=0)
   	{
   		if (is_array($values)) {
   			foreach ($values as $i=>$value) $values[$i] = $this->sqlSplitter->quote($value, 'DEFAULT');
   			$values = join(', ', $values);
   		}
   		$this->addToPart('values', $values, $flags, $subset);
		return $this;
   	}
   	   	
	/**
	 * Add criteria as where or having statement as $column=$value.
	 * If $value == null and $compare == '=', $compare becomes 'IS NULL'.
	 * 
	 * @param mixed  $column    Column name, column number or expression with placeholders, can also be an array of columns ($column[0]=$value OR $column[1]=$value)
	 * @param mixed  $value     Value or array of values ($column=$value[0] OR $column=$value[1])
	 * @param string $compare   Comparision operator: =, !=, >, <, =>, <=, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN, NOT IN, ALL and BETWEEN
	 * @param int    $flags     Addition options (language specific) as binairy set
	 * @param int    $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addCriteria($column, $value, $compare="=", $flags=0, $subset=0)
	{
		$parts = $this->sqlSplitter->buildWhere($this->getColumnDbName($column), $value, $compare);
		if (isset($parts['having']) && $flags & DB::ADD_HAVING) throw new Exception("Criteria doing an '$compare' comparision can only be used as WHERE not as HAVING expression.");
		
		if ($subset === 0 && $this->getQueryType() === 'INSERT' && $this->hasPart('query', 0)) $subset = 1;
		if (isset($parts['where'])) $this->where($parts['where'], $flags, $subset);
		if (isset($parts['having'])) $this->having($parts['having'], $flags, $subset);
		
		return $this;
	}
	
	/**
	 * Add WHERE expression to query statement.
	 *
	 * @param string $statement  WHERE expression
	 * @param int    $flags      Addition options as binairy set
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function where($statement, $flags=0, $subset=0)
	{
 		$this->addToPart($flags & DB::ADD_HAVING ? 'having' : 'where', $this->sqlSplitter->quoteIdentifier($statement), $flags, $subset);
		return $this;
	}

	/**
	 * Add HAVING expression to query statement.
	 *
	 * @param string $statement  HAVING expression
	 * @param int    $flags      Addition options as binairy set
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function having($statement, $flags=0, $subset=0)
	{
 		$this->where($statement, $flags | DB::ADD_HAVING, $subset);
		return $this;
	}
	
	/**
	 * Add GROUP BY expression to query statement.
	 *
	 * @param string $statement  GROUP BY statement (string) or array with columns
	 * @param int    $flags      Addition options as binairy set
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function groupBy($statement, $flags=0, $subset=0)
	{
		$statement = $this->getColumnDbName($statement);
		if (is_array($statement)) $statement = join(', ', $statement);
		
 		$this->addToPart('group by', $statement, $flags, $subset);
		return $this;
	}

	/**
	 * Add ORDER BY statement to query statement.
	 * NOTE: In contrary of addStatement(), the statement is prepended by default (use DB_Statment_SQL::ADD_APPEND to append)
	 *
	 * @param mixed $statement  ORDER BY statement (string) or array with columns
	 * @param int   $flags      Addition options as binairy set
	 * @param int   $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function orderBy($statement, $flags=0, $subset=0)
	{
		if ($flags & (DB::ASC | DB::DESC)) {
			if (is_scalar($statement)) {
				$statement .= $flags & DB::DESC ? ' DESC' : ' ASC';
			} else {
				foreach ($statement as &$col) $col .= $flags & DB::DESC ? ' DESC' : ' ASC';
			}
		}
		
		$statement = $this->getColumnDbName($statement);
		if (!is_scalar($statement)) $statement = join(', ', $statement);
		
 		if (!($flags & DB::ADD_APPEND)) $flags |= DB::ADD_PREPEND;
		$this->addToPart('order by', $statement, $flags, $subset);
		return $this;
	}

	/**
	 * Set the limit for the number of rows returned when excecuted.
	 *
	 * @param int|string $rowcount  Number of rows of full limit statement
	 * @param int        $offset    Start at row
	 * @param int        $flags   Addition options as binairy set
	 * @param int        $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function limit($rowcount, $offset=null, $flags=0, $subset=0)
	{
		$this->replacePart('limit', $rowcount . (isset($offset) ? " OFFSET $offset" : ""), $flags, $subset);
		return $this;
	}

	/**
	 * Set the limit by specifying the page.
	 *
	 * @param int $page      Page numer, starts with page 1
	 * @param int $rowcount  Number of rows per page
	 * @param int $flags     Addition options as binairy set
	 * @param int $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function page($page, $rowcount, $flags=0, $subset=0)
	{
		$this->setLimit($rowcount, $rowcount * ($page-1), $flags, $subset);
		return $this;
	}

	
	//------------- Build statement - Add table ----------------
	
	/**
	 * Adds a table and optional columns to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param array|string $cols    The columns to select from this table.
     * @param string       $join    join type: INNER JOIN, LEFT JOIN, etc
     * @param array|string $on      "querytable.column = tablename.column"
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	protected function addTableAndColumns($table, $join=null, $on=null, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		$table = isset($schema) ? $this->sqlSplitter->makeIdentifier($schema, $table) : $this->sqlSplitter->quoteIdentifier($table);
		$this->addTable($table, $join, $on);
		$this->addColumn($this->getColumnDBName($cols, $table));
		
		return $this;
	}
	
	/**
	 * Adds a FROM table and optional columns to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function from($table, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, null, null, $cols, $schema, $flags, $subset);
	}

	/**
	 * Alias of Q\DB::joinInner()
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param array|string $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	final public function join($table, $on, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->joinInner($table, $on, $cols, $schema, $flags, $subset);
	}
	
	/**
	 * Adds an INNER JOIN table and columns to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinInner($table, $on, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'INNER JOIN', $on, $cols, $schema);
	}	

	/**
	 * Adds an LEFT JOIN table and columns to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinLeft($table, $on, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'LEFT JOIN', $on, $cols, $schema, $flags, $subset);
	}	

	/**
	 * Adds an RIGHT JOIN table and columns to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinRight($table, $on, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'RIGHT JOIN', $on, $cols, $schema, $flags, $subset);
	}
	
	/**
	 * Adds an FULL JOIN table and columns to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinFull($table, $on, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'FULL JOIN', $on, $cols, $schema, $flags, $subset);
	}
	
	/**
	 * Adds an CROSS JOIN table and columns to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinCross($table, $on, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'CROSS JOIN', $on, $cols, $schema, $flags, $subset);
	}
	
	/**
	 * Adds an NATURAL JOIN table and columns to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinNatural($table, $on, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'NATURAL JOIN', $on, $cols, $schema, $flags, $subset);
	}

	
	/**
	 * Alias of Q\DB::joinInnerUsing()
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @param int          $flags      Addition options as binairy set
	 * @param int          $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	final public function joinUsing($table, $on_column, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		return $this->joinInnerUsing($table, $on_column, $cols, $schema, $flags, $subset);
	}
	
	/**
	 * Adds an INNER JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @param int          $flags      Addition options as binairy set
	 * @param int          $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	final public function joinInnerUsing($table, $on_column, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table, $flags, $subset);
		return $this->addTableAndColumns($table, 'INNER JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}	

	/**
	 * Adds an LEFT JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @param int          $flags      Addition options as binairy set
	 * @param int          $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinLeftUsing($table, $on_column, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table, $flags, $subset);
		return $this->addTableAndColumns($table, 'LEFT JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}	

	/**
	 * Adds an RIGHT JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @param int          $flags      Addition options as binairy set
	 * @param int          $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinRightUsing($table, $on_column, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table, $flags, $subset);
		return $this->addTableAndColumns($table, 'RIGHT JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}
	
	/**
	 * Adds an FULL JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @param int          $flags      Addition options as binairy set
	 * @param int          $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinFullUsing($table, $on_column, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table, $flags, $subset);
		return $this->addTableAndColumns($table, 'FULL JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}
	
	/**
	 * Adds an CROSS JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @param int          $flags      Addition options as binairy set
	 * @param int          $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinCrossUsing($table, $on_column, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table, $flags, $subset);
		return $this->addTableAndColumns($table, 'CROSS JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}
	
	/**
	 * Adds an NATURAL JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @param int          $flags      Addition options as binairy set
	 * @param int          $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinNaturalUsing($table, $on_column, $cols='*', $schema=null, $flags=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table, $flags, $subset);
		return $this->addTableAndColumns($table, 'NATURAL JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
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
	 * Build a new query statement committing all changes.
	 * 
	 * @return DB_SQLStatement
	 */
	public function commit()
	{
		return new static($this);
	}

	/**
	 * Clear cached statement.
	 * This doesn't clear cached columns and values.
	 */
	protected function clearCachedStatement()
	{
		$this->cachedStatement = null;
		$this->cachedParts = null;
		$this->countStatement = null;
		$this->emptyResult = null;
	}

	/**
	 * Clear cached statement and parts.
	 * This is done implicitly, so you probably don't ever need to call this function.
	 *
	 * @return DB_SQLStatement
	 */
	public function refresh()
	{
		$this->clearCachedStatement();
		$this->cachedColumns = null;
		$this->cachedValues = null;
		
		return $this;
	}
	
	
	//------------- Excecute ------------------------

	/**
	 * Alias of Q\DB_Statement::execute()
	 * 
	 * @param array $args
	 * @return DB_Result
	 */
	final public function query($args=null)
	{
		if (func_num_args() > 1) $args = func_get_args();
		return $this->execute($args);
	}

	/**
	 * Execute the query statement.
	 * 
	 * @param array $args  Arguments to be parsed into the query on placeholders.
	 * @return DB_Result
	 */
	public function execute($args=null)
	{
   	    if (!isset($this->connection)) throw new Exception("Unable to execute statement: Statement object isn't connectioned to a database connection."); 

		if (func_num_args() > 1) $args = func_get_args();
		return $this->connection->query($this, $args);
	}
	
	/**
     * Excecute query with WHERE FALSE, returning an empty result.
     *
     * @return DB_Result
     */
   	protected function executeEmpty()
   	{
   	    if (isset($this->emptyResult)) return $this->emptyResult;
   	    
   		$qt = $this->getQueryType();
   		if ($qt !== 'SELECT' && ($qt !== 'INSERT' || !$this->hasPart('query'))) throw new DB_Exception("Unable to get a result for a " . $this->getQueryType() . " query:\n" . $this->getStatement());

   	    if (!isset($this->connection)) throw new Exception("Unable to execute statement: Statement object isn't connectioned to a database connection."); 
   		
   		$parts = $this->getParts();
   		
   		if ($qt === 'INSERT') {
   			$matches = null;
   			if (sizeof($parts) > 1 && preg_match('/^\#sub(\d+)$/', trim($parts['query']), $matches)) $parts[0] = $parts[(int)$matches[1]];
   			 else $parts[0] = $this->sqlSplitter->split($parts['query']);
   		}
   		
   		$parts[0]['where'] = 'FALSE';
   		$parts[0]['having'] = '';
   		
   		$class = get_class($this);
   		$this->emptyResult = $this->connection->query(new $class($this, $this->connection->parse($this->sqlSplitter->joinInject($parts), false)));
   		return $this->emptyResult;
   	}
	
	
	/**
     * Get a set of fields (DB_Field) based on the columns of the query statement.
     *
     * @return array
     */
   	public function getFields()
   	{
   		return $this->executeEmpty()->getFields();
   	}

   	/**
     * Return the number of rows that the resultset would contain if the statement was executed.
     * For better readability use: $result->countRows(DB::ALL_ROWS).
     * 
     * @param boolean $all  Don't use limit
     * @return int
     */
   	public function countRows($all=false)
   	{
   	    $all = (boolean)$all;
   		if (!isset($this->countStatement[$all])) {
   			$parts = $this->getParts();
   			$this->countStatement[$all] = $this->connection->parse($this->sqlSplitter->buildCountStatement(count($parts) == 1 ? reset($parts) : $this->getStatement(), $all), false);
   			if (!isset($this->countStatement[$all])) throw new DB_Exception("Unable to count rows for " . $this->getQueryType() . " query:\n" . $this->getStatement());
   		}
   		
   		return $this->connection->query($this->countStatement[$all])->fetchValue();
   	}
   	
	/**
	 * Create a new record using the fields of the result of this statement.
	 * 
	 * @return DB_Record
	 */
	function newRecord()
	{
		return $this->executeEmpty()->newRecord();
	}
}
