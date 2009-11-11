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
	 * The query statements to count the number of records
	 * @var string
	 */
	protected $countStatement;

	
   	//------------- Class constructor ------------------------

	/**
	 * Class constructor
	 *
	 * @param string $statement  Query statement
	 * @param mixed  $source     Q\DB, Q\DB_Table, Q\DB_SQLStatement or driver name (string)
	 */
	public function __construct($statement, $source=null)
	{
		if (!isset($source)) {
			if ($statement instanceof self) $source = $statement;
			  elseif (!(DB::i() instanceof Mock)) $source = DB::i();
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
	        throw new Exception("Source of statement can only be a Q\DB, Q\DB_Table, Q\DB_SQLStatement or driver name, not a " . (is_object($source) ? get_class($source) : gettype($source)));
	    }

		$this->statement = $statement;
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
	 * @param mixed  $column  Field name or field index, multiple columns may be specified as array
	 * @param string $table   Default table for column
	 * @param string $alias   Alias the column
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
     * Return the statement without any added or replaced parts.
     *
     * @return DB_SQLStatement
     */
   	public function getBaseStatement()
   	{
   		return new static($this, $this->statement);
   	}

	/**
     * Return all the parts of the base statement
     *
	 * @param boolean $extract  Extract subsets from main statement and split each subset seperatly
     * @return array
     */
	protected function getBaseParts($extract=false)
	{
		if (!isset($this->baseParts[(bool)$extract])) {
			if (!$extract) $this->baseParts[false] = $this->sqlSplitter->split($this->statement);
			  else $this->baseParts[true] = $this->sqlSplitter->extractSplit($this->statement);
		}
		
		return $this->baseParts[(bool)$extract];
	}
	
	
   	//------------- Get statement ------------------------
	
	/**
	 * Cast statement object to string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		if (empty($this->partsAdd) && empty($this->partsReplace)) return $this->statement;
		
		if (!isset($this->cachedStatement)) $this->cachedStatement = $this->sqlSplitter->join($this->getParts());
		return $this->cachedStatement;
	}
	
	/**
	 * Return the statement with parsed in arguments.
	 * 
	 * @param array $args  Arguments to parse on place holders
	 * @return string
	 */
	public function parse($args)
	{
		if (func_num_args() > 1) $args = func_get_args();
		return $this->sqlSplitter->parse($this, $args);
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
					$parts[$key] = join(', ', array_merge(isset($partsAdd[DB::PREPEND]) ? $partsAdd[DB::PREPEND] : array(), !empty($parts[$key]) ? array($parts[$key]) : array(), isset($partsAdd[DB::APPEND]) ? $partsAdd[DB::APPEND] : array()));
				} elseif ($key === 'values') {
					$parts[$key] = (isset($partsAdd[DB::PREPEND]) ? ' (' . join('), (', $partsAdd[DB::PREPEND]) . ')' : '') . (isset($partsAdd[DB::PREPEND]) && !empty($parts[$key]) ? ', ' : '') . $parts[$key] . (isset($partsAdd[DB::APPEND]) && !empty($parts[$key]) ? ', ' : '') .  (isset($partsAdd[DB::APPEND]) ? ' (' . join('), (', $partsAdd[DB::APPEND]) . ')' : '');
				} elseif ($key === 'from' || $key === 'into' || $key === 'tables') {
					$parts[$key] = trim((isset($partsAdd[DB::PREPEND]) ? join(' ', $partsAdd[DB::PREPEND]) . ' ' : '') . (!empty($parts[$key]) ? '(' . $parts[$key] . ')' : '') . (isset($partsAdd[DB::APPEND]) ? ' ' . join(' ', $partsAdd[DB::APPEND]) : ''), ',');
				} elseif ($key === 'where' || $key === 'having') {
					$items = array_merge(isset($partsAdd[DB::PREPEND]) ? $partsAdd[DB::PREPEND] : array(), !empty($parts[$key]) ? array($parts[$key]) : array(), isset($partsAdd[DB::APPEND]) ? $partsAdd[DB::APPEND] : array());
					if (!empty($items)) $parts[$key] = '(' . join(') AND (', $items) . ')';
				} else {
					$parts[$key] = (isset($partsAdd[DB::PREPEND]) ? join(' ', $partsAdd[DB::PREPEND]) . ' ' : '') . (!empty($parts[$key]) ? $parts[$key] : '') . (isset($partsAdd[DB::APPEND]) ? ' ' . join(' ', $partsAdd[DB::APPEND]) : '');
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
		if (!empty($this->partsAppend[$subset][$key]) || !empty($this->partsPrepend[$subset][$key]) || !empty($this->partsReplace[$subset][$key])) return true;
		
		$parts = $subset == 0 ? array($this->getBaseParts(false)) : $this->getBaseParts(true);
		return isset($parts[$subset][$key]);
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
			if (!isset($parts[$subset][$key])) return null;
			
			$parts[0] =& $parts[$subset][$key];
			return $this->sqlSplitter->injectSubsets($parts);
		}
	}
	
	/**
	 * Get the columns used in the statement.
	 * 
	 * @param int $flags   DB::SPLIT_% option
	 * @param int $subset  Get the columns of a subquery (0=main query)
	 * @return array
	 */
	public function getColumns($flags=0, $subset=0)
	{
		if ($subset == 0) {
			$parts = $this->getParts();
		} else {
			$sets = $this->getParts(true);
			if (!isset($sets[$subset])) throw new Exception("Unable to get columns for subset $subset: Subset does not exist."); 
			$sets[0] =& $sets[$subset];
			$parts = $this->sqlSplitter->injectSubsets($sets);
		}
		
		if (!isset($parts['columns']) && !isset($parts['set'])) throw new Exception("It's not possible to extract columns of a " . $this->getQueryType() . " query.");

		return $this->sqlSplitter->splitColumns(isset($parts['columns']) ? $parts['columns'] : "SET {$parts['set']}", $flags);
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
	 * Add an expression to any part of the query.
	 * (fluent interface)
	 * 
	 * Use DB_SQLStatement::PREPEND in $flags to prepend a statement (append is default)
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $expression
	 * @param int    $flags      DB::REPLACE, DB::PREPEND or DB::APPEND + Addition options as binairy set.
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function part($key, $expression, $flags=DB::REPLACE, $subset=0)
	{
		$key = strtolower($key);
		
		if ($flags & DB::REPLACE) $this->partsReplace[$subset][$key] = $expression;
		  else $this->partsAdd[$subset][$key][$flags & DB::PREPEND ? DB::PREPEND : DB::APPEND][] = $expression;
		
		$this->clearCachedStatement();
		return $this;
	}
	
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
   		
   		if ($key == 'set' && is_array($column)) {
   			array_map(function ($col, &$value) use($flags) {$this->getColumnDBName($col, null, null, $flags) . '=' . $this->sqlSplitter->quote($value);});
   		} else {
   			$column = $this->getColumnDBName($column, null, null, $flags);
   		}
   		
		$this->part($key, is_array($column) ? join(', ', $column) : $column, $flags, $subset);
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
   		
   		if (!isset($join) && ~$flags & DB::REPLACE) $join = ',';
   		if (is_array($on)) $on = $this->getColumnDbName($on[0], null, null, $flags) . ' = ' . $this->getColumnDbName($on[1], $table, null, $flags);
   		  else $on = $this->sqlSplitter->quoteIdentifier($on, DB::QUOTE_LOOSE);
   		  
   		if ($flags & DB::PREPEND && ~$flags & DB::REPLACE) {
   			$this->part($key, $this->sqlSplitter->quoteIdentifier($table, $flags) . ' ' . $join, $flags, $subset);
   			if (!empty($on)) $this->part($key, "ON $on", $flags & ~DB::PREPEND, $subset);
   		} else {
			$this->part($key, $join . ' '. $this->sqlSplitter->quoteIdentifier($table, $flags) . (!empty($on) ? " ON $on" : ""), $flags, $subset);
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
   		$this->part('values', $values, $flags, $subset);
		return $this;
   	}
   	   	
	/**
	 * Add criteria as where or having statement as $column=$value.
	 * If $value == null and $compare == '=', $compare becomes 'IS NULL'.
	 * 
	 * @param mixed  $column    Column name, column number or expression with placeholders, can also be an array of columns ($column[0]=$value OR $column[1]=$value) or array(column=>value, ...)
	 * @param mixed  $value     Value or array of values ($column=$value[0] OR $column=$value[1])
	 * @param string $compare   Comparision operator: =, !=, >, <, =>, <=, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN, NOT IN, ALL and BETWEEN
	 * @param int    $flags     Addition options (language specific) as binairy set
	 * @param int    $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addCriteria($column, $value, $compare="=", $flags=0, $subset=0)
	{
		if (is_array($column) && is_string(key($column))) {
			$parts = null;
			
			foreach ($column as $col=>&$value) {
				$p = $this->sqlSplitter->buildWhere($this->getColumnDBName($col, null, null, $flags), $value, $compare);
				if (isset($p['where'])) $parts['where'][] = $p['where'];
				if (isset($p['having'])) $parts['having'][] = $p['having'];
			}
			
			if (isset($parts['where'])) $parts['where'] = join($flags & DB::GLUE_OR ? ' OR ' : ' AND ', $parts['where']);
			if (isset($parts['having'])) {
				if (count($parts['having']) > 1 && $flags & DB::GLUE_OR) throw new Exception("Criteria doing an '$compare' comparision can't by glued with OR, only with AND.");
				$parts['having'] = join(' AND ', $parts['having']);
			}
		} else {
			$parts = $this->sqlSplitter->buildWhere($this->getColumnDBName($column, null, null, $flags), $value, $compare);
		}
		
		if (isset($parts['having']) && $flags & DB::HAVING) throw new Exception("Criteria doing an '$compare' comparision can only be used as WHERE not as HAVING expression.");
		
		if ($subset === 0 && $this->getQueryType() === 'INSERT' && $this->hasPart('query', 0)) $subset = 1;
		if (isset($parts['where'])) $this->where($parts['where'], $flags & ~0x700 | DB::QUOTE_NONE, $subset);
		if (isset($parts['having'])) $this->having($parts['having'], $flags & ~0x700 | DB::QUOTE_NONE, $subset);
		
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
	public function where($expression, $flags=0, $subset=0)
	{
 		$this->part($flags & DB::HAVING ? 'having' : 'where', $this->sqlSplitter->quoteIdentifier($expression), $flags, $subset);
		return $this;
	}

	/**
	 * Add HAVING expression to query statement.
	 *
	 * @param string $expression  HAVING expression
	 * @param int    $flags       Addition options as binairy set
	 * @param int    $subset      Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function having($expression, $flags=0, $subset=0)
	{
 		$this->where($expression, $flags | DB::HAVING, $subset);
		return $this;
	}
	
	/**
	 * Add GROUP BY expression to query statement.
	 *
	 * @param string $expression  GROUP BY expression (string) or array with columns
	 * @param int    $flags       Addition options as binairy set
	 * @param int    $subset      Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function groupBy($expression, $flags=0, $subset=0)
	{
		$expression = $this->getColumnDbName($expression);
		if (is_array($expression)) $expression = join(', ', $expression);
		
 		$this->part('group by', $expression, $flags, $subset);
		return $this;
	}

	/**
	 * Add ORDER BY expression to query statement.
	 * NOTE: In contrary of addStatement(), the statement is prepended by default (use DB_Statment_SQL::APPEND to append)
	 *
	 * @param mixed $expression  ORDER BY expression (string) or array with columns
	 * @param int   $flags       Addition options as binairy set
	 * @param int   $subset      Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function orderBy($expression, $flags=0, $subset=0)
	{
		if ($flags & (DB::ASC | DB::DESC)) {
			if (is_scalar($expression)) {
				$expression .= $flags & DB::DESC ? ' DESC' : ' ASC';
			} else {
				foreach ($expression as &$col) $col .= $flags & DB::DESC ? ' DESC' : ' ASC';
			}
		}
		
		$expression = $this->getColumnDbName($expression);
		if (!is_scalar($expression)) $expression = join(', ', $expression);
		
 		if (!($flags & DB::APPEND)) $flags |= DB::PREPEND;
		$this->part('order by', $expression, $flags, $subset);
		return $this;
	}

	/**
	 * Set the limit for the number of rows returned when excecuted.
	 *
	 * @param int|string $rowcount  Number of rows of full limit statement
	 * @param int        $offset    Start at row
	 * @param int        $flags     Addition options as binairy set
	 * @param int        $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function limit($rowcount, $offset=null, $flags=0, $subset=0)
	{
		$this->part('limit', $rowcount . (isset($offset) ? " OFFSET $offset" : ""), $flags | DB::REPLACE, $subset);
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
		$this->setLimit($rowcount, $rowcount * ($page-1), $flags | DB::REPLACE, $subset);
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
		$table = $this->sqlSplitter->makeIdentifier($schema, $table, null, $flags);
		$this->addTable($table, $join, $on, $flags, $subset);
		if (isset($cols)) $this->addColumn($this->getColumnDBName($cols, $table, null, $flags), $flags, $subset);
		
		return $this;
	}
	
	/**
	 * Adds a table and optional columns to the query.
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
		if ($subset == 0 && $this->getQueryType() == 'INSERT') $subset = 1;
		return $this->addTableAndColumns($table, null, null, $cols, $schema, $flags, $subset);
	}

	/**
	 * Adds a table to the query.
	 * 
     * @param array|string $table   The table name or an associative array relating table name to correlation name.
     * @param array|string $cols    The columns to select from this table.
     * @param string       $schema  The schema name to specify, if any.
	 * @param int          $flags   Addition options as binairy set
	 * @return DB_SQLStatement
	 */
	public function into($table, $schema=null, $flags=0)
	{
		return $this->addTableAndColumns($table, null, null, null, $schema, $flags, 0);
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
		$this->emptyResult = null;
		
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
   		$this->emptyResult = $this->connection->query(new $class($this, $this->connection->parse($this->sqlSplitter->joinInject($parts), null)));
   		return $this->emptyResult;
   	}
	
	
	/**
     * Get a set of fields based on the columns of the query statement.
     *
     * @return DB_FieldSet
     */
   	public function getFields()
   	{
   		return $this->executeEmpty()->getFields();
   	}

	/**
     * Get a field based on the columns of the query statement.
     *
     * @param int|string $index  Field index or name
     * @param int        $flags  Optional DB::FOLLOW and DB::STRIP_OPERATOR
     * @return DB_Field
     */
   	public function getField($index, $flags=0)
   	{
   		if ($flags & DB::STRIP_OPERATOR) $this->sqlSplitter->stripOperator($index);
   		
   		$field = $this->executeEmpty()->getField($index);
   		
   		if (!isset($field) && ($flags & DB::FOLLOW)) {
   			list($table, $column) = $this->sqlSplitter->splitIdentifier($index);
   			$table = isset($table) && isset($this->connection) ? $this->getTable($table, $flags) : $this->getBaseTable();
   			
   			$field = $table ? $table->getField($index, $flags & ~DB::STRIP_OPERATOR) : new DB_Field($index);
   		}
   		
   		return $field;
   	}
   	
   	/**
     * Return the number of rows that the resultset would contain if the statement was executed.
     * For better readability use: $result->countRows(DB::ALL_ROWS).
     * 
     * @param int $flags  Optional DB::ALL_ROWS
     * @return int
     */
   	public function countRows($flags=0)
   	{
   	    $all = (boolean)$all;
   		if (!isset($this->countStatement[$all])) {
   			$parts = $this->getParts();
   			$this->countStatement[$flags & DB::ALL_ROWS] = $this->connection->parse($this->sqlSplitter->buildCountStatement(count($parts) == 1 ? reset($parts) : $this->getStatement(), $flags), false);
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
