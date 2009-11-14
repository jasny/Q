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
	 * @var DB_Table
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
	 * The parts of the split base statement extracted in sets
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
	 * @var array
	 */
	protected $cachedParts;
	
	/**
	 * Extracted table names
	 * @var array
	 */
	protected $cachedTablenames;
	
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
			if (isset($classes['sqlSplitter'])) $this->sqlSplitter() = new $classes['sqlSplitter'](); 
		} elseif ($source instanceof DB) {
		    $this->getConnection() = $source;
		    if (isset($this->getConnection()->sqlSplitter)) $this->sqlSplitter() = $this->getConnection()->sqlSplitter;
	    } elseif ($source instanceof DB_Table) {
	        $this->getConnection() = $source->getConnection();
	        $this->basetable = $source;
	        if (isset($this->getConnection()->sqlSplitter)) $this->sqlSplitter() = $this->getConnection()->sqlSplitter;
	    } elseif ($source instanceof self) {
	        $this->getConnection() = $source->getConnection();
	        $this->basetable = $source->getBaseTable();
	        if (isset($source->sqlSplitter)) $this->sqlSplitter() = $source->sqlSplitter;
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
	public function getConnection()
	{
		if (!isset($this->connection)) throw new DB_Exception("Connection of statement object is not set.");
		return $this->connection;
	}
	
	/**
	 * Get the SQL splitter.
	 * 
	 * @return DB_SQLSplitter
	 */
	function sqlSplitter()
	{
		if (!isset($this->sqlSplitter)) throw new DB_Exception("Unable to modify statement: SQL splitter is not set.");
		return $this->sqlSplitter;
	}
	
	
	//------------- Get query info ------------------------
   	
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
		    $sets = $this->sqlSplitter()->extractSubsets($this->statement);
		    if (!isset($sets[$subset])) throw new Exception("Unable to get query type of subset $subset: Statement doesn't have $subset subqueries.");
		    $statement = $sets[$subset];
		} else {
		    $statement = $this->statement;
		}
		
		$this->queryType[$subset] = $this->sqlSplitter()->getQueryType($statement);
		return $this->queryType[$subset];
	}

	/**
	 * Resolve symantic mapping for field name.
	 * This gets done automatically, so you don't have to call this method explicitly.
	 *
	 * @param string|DB_Table $table
	 * @param string|DB_Field $column  Field name or index
	 * @param string          $alias
	 * @param int             $flags   Bitset of DB::FIELDNAME_% and DB::QUOTE_% options
	 * @return string
	 */
	public function resolveColumn($table, $column, $alias=null, $flags=0)
	{
		if (~$flags & 0x700) $flags | DB::QUOTE_LOOSE;
		
   		if ($column instanceof DB_Field || (is_string($column) && $column[0] !== '#')) { 
   			$field = $column;
   		} elseif (is_int($column) || strncmp($column, '#col:', 5) == 0) {
   			if (!is_int($column)) $column = substr($column, 5);
   			$field = isset($table) ? $this->getTable($table)->getField($nr) : $this->getField($nr)->getName($flags);
   		} else {
			$field = $this->getTable($table)->getField($column);
   		}
		
		return $this->sqlSplitter()->makeIdentifier($table, $field, $alias, $flags);
	}

	/**
	 * Guess the base table if it has not been explicitly set.
	 * 
	 * @param string $expression
	 * @return boolean
	 */
	protected function guessBaseTable($expression=null)
	{
    	if (isset($this->basetable)) return true;
    	
    	$tables = $this->sqlSplitter->splitTables($this->getBaseStatement(), DB::SPLIT_ASSOC);
	    if (empty($tables) && isset($expression)) $tables = $this->sqlSplitter()->splitTables($expression, DB::SPLIT_ASSOC);
	    
	    if (!empty($tables)) $this->basetable = reset($tables);
		return isset($this->basetable);
	}
	
   	//------------- Get statement ------------------------

	/**
     * Return the statement without any added or replaced parts.
     *
     * @return DB_SQLStatement
     */
   	public function getBaseStatement()
   	{
   		return new static($this->statement, $this);
   	}

	/**
	 * Cast statement object to string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		if (empty($this->partsAdd) && empty($this->partsReplace)) return $this->statement;
		
		if (!isset($this->cachedStatement)) $this->cachedStatement = $this->sqlSplitter()->join($this->getParts());
		return $this->cachedStatement;
	}

	/**
	 * Get a subquery.
	 * 
	 * @param $subset
	 * @return DB_SQLStatement
	 */
	public function getSubquery($subset)
	{
		return new static($this->sqlSplitter()->join($this->getParts($subset)), $this);
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
		return $this->sqlSplitter()->parse($this, $args);
	}

	/**
	 * Extract and split base statement.
	 */
   	protected function splitBaseStatement()
   	{
   		$this->baseParts = array_map(array($this->sqlSplitter(), 'split'),  $this->sqlSplitter()->extract($this->statement));
   	}
   	
	/**
	 * Apply the added and replacement parts to the parts of the base query.
	 * 
	 * @param int $subset  Get the parts of a subquery (0=main query)
	 * @return array
	 */
	public function getParts($subset=0)
	{
		if (isset($this->cachedParts[$subset])) return $this->cachedParts[$subset];
		
		if (!isset($this->baseParts)) $this->splitBaseStatement();
		if (!isset($this->baseParts[$subset])) throw new Exception("Unable to use subset $subset: " . (count($this->baseParts) == 1 ? "Statement does not have any subqueries" : "Statement only has " . (count($this->baseParts)-1) . (count($this->baseParts) == 2 ? " subquery" : " subqueries")));
		
		if (empty($this->partsAdd) && empty($this->partsReplace)) return $this->baseParts[$subset];

		// Only subsets with a higher number can be a subquery of requested subset, so work from high to low
		for ($i=count($this->baseParts); $i >= 0; $i--) {
			$parts =& $this->baseParts[$i];
			
			if (!empty($this->partsReplace[$i])) $parts = array_merge($parts, $this->partsReplace[$i]);
			if (!empty($this->partsAdd[$i])) $parts = $this->sqlSplitter()->addParts($parts, $add);
			if (key($parts) == 'select' && empty($parts['columns'])) $parts['columns'] = '*';
			
			$this->cachedParts[$i] =& $parts;
			$this->sqlSplitter()->injectSubsets(&$this->cachedParts);
		}
		
		return $this->cachedParts[$subset];
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
		if (!isset($this->baseParts)) $this->splitBaseStatement();
		return array_key_exists($key, $this->baseParts[$subset]) || !empty($this->partsReplace[$subset][$key]) || !empty($this->partsAdd[$subset][$key]);
	}
	
	/**
     * Return a specific part of the statement.
     *
	 * @param mixed $key     The key identifying the part
	 * @param int   $subset  Get the part of a subquery (0=main query)
     * @return string
     */
	public function getPart($key, $subset=0)
	{
		$this->getParts($subset);
		if (!array_key_exists($key, $this->cachedParts[$subset]))
		
		return $this->cachedParts[$subset][$key];
	}
	
	/**
	 * Get the tables used in this statement.
	 * 
	 * {@internal Using cache is slightly inconvenient, however we don't want to split this each time when we need to lookup a fieldname}}
	 *
	 * @param string|int $subset  Get the tables of a subquery (0=main query)
	 * @param int        $flags   DB::SPLIT_% options
	 * @return DB_Table
	 */
	public function getTablenames($subset=0, $flags=0)
	{
		if (!isset($this->cachedTablenames[$subset])) $this->cachedTablenames[$subset] = $this->sqlSplitter()->splitTables($this->getParts($subset), $flags);
		return $this->cachedTablenames[$subset];
	}
	
	/**
	 * Get the columns used in the statement.
	 * 
	 * @param int $flags   DB::SPLIT_% and DB::UNQUOTE options
	 * @param int $subset  Get the columns of a subquery (0=main query)
	 * @return array
	 */
	public function getColumns($flags=0, $subset=0)
	{
		return $this->sqlSplitter()->splitColumns($this->getParts($subset), $flags);
	}

	/**
	 * Get the values used in the statement.
	 * Only for INSERT INTO ... VALUES ... query.
	 * 
	 * @param int $flags  Optional DB::UNQUOTE
	 * @return array
	 */
	public function getValues($flags=0)
	{
		return $this->sqlSplitter()->splitValues($this->getParts(), $flags);
	}
	
	/**
	 * Count the number of placeholders in the statement.
	 *
	 * @return int
	 */
	public function countPlaceholders()
	{
		return $this->sqlSplitter()->countPlaceholders($this->getStatement());
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
	 * @param int    $flags      DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function part($key, $expression, $flags=DB::REPLACE, $subset=0)
	{
		$key = strtolower($key);
		if ($this->sqlSplitter()->holdsIdentifiers($key)) $this->sqlSplitter()->resolveExpression($expression, array($this, 'resolveColumn'), $flags);
		
		if ($flags & DB::REPLACE) $this->partsReplace[$subset][$key] = $expression;
		  else $this->partsAdd[$subset][$key][$flags & DB::PREPEND ? DB::PREPEND : DB::APPEND][] = $expression;
		
		$this->clearCachedStatement();
		
		if ($key == 'from' || $key == 'into' || $key == 'tables') {
			if (!$this->basetable) $this->guessBasetable($expression);
			unset($this->cachedTablenames[$subset]);
		}
		
		return $this;
	}
	
   	/**
   	 * Add column to query statement.
   	 * 
   	 * NOTE: This function does not escape $column and does not quote values
	 *
	 * @param mixed $column   Column name or array with column names
	 * @param int   $flags    DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int   $subset   Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addColumn($column, $flags=0, $subset=0)
   	{
   		$type = $this->getQueryType($subset);
   		$key = $type == 'UPDATE' || ($type == 'INSERT' && $this->hasPart('set', $subset)) ? 'set' : 'columns';
   		
   		if ($key == 'set' && is_array($column)) {
   			array_map(function ($col, &$value) use($flags) {$this->getColumnDBName($col, null, null, $flags) . '=' . $this->sqlSplitter()->quote($value);});
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
	 * @param int    $flags    DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
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
   		  else $on = $this->sqlSplitter()->quoteIdentifier($on, DB::QUOTE_LOOSE);
   		  
   		if ($flags & DB::PREPEND && ~$flags & DB::REPLACE) {
   			$this->part($key, $this->sqlSplitter()->quoteIdentifier($table, $flags) . ' ' . $join, $flags, $subset);
   			if (!empty($on)) $this->part($key, "ON $on", $flags & ~DB::PREPEND, $subset);
   		} else {
			$this->part($key, $join . ' '. $this->sqlSplitter()->quoteIdentifier($table, $flags) . (!empty($on) ? " ON $on" : ""), $flags, $subset);
   		}

		return $this;
   	}
	
   	/**
   	 * Add a row of values to an "INSERT ... VALUES (...)" query statement.
   	 * 
	 * @param mixed $values   Statement (string) or array of values
	 * @param int   $flags    Options as bitset
	 * @param mixed $subset   Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addValues($values, $flags=0, $subset=0)
   	{
   		if (is_array($values)) {
   			foreach ($values as $i=>$value) $values[$i] = $this->sqlSplitter()->quote($value, 'DEFAULT');
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
	 * @param int    $flags     DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int    $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addCriteria($column, $value, $compare="=", $flags=0, $subset=0)
	{
		if (is_array($column) && is_string(key($column))) {
			$parts = null;
			
			foreach ($column as $col=>&$value) {
				$p = $this->sqlSplitter()->buildWhere($this->getColumnDBName($col, null, null, $flags), $value, $compare);
				if (isset($p['where'])) $parts['where'][] = $p['where'];
				if (isset($p['having'])) $parts['having'][] = $p['having'];
			}
			
			if (isset($parts['where'])) $parts['where'] = join($flags & DB::GLUE_OR ? ' OR ' : ' AND ', $parts['where']);
			if (isset($parts['having'])) {
				if (count($parts['having']) > 1 && $flags & DB::GLUE_OR) throw new Exception("Criteria doing an '$compare' comparision can't by glued with OR, only with AND.");
				$parts['having'] = join(' AND ', $parts['having']);
			}
		} else {
			$parts = $this->sqlSplitter()->buildWhere($this->getColumnDBName($column, null, null, $flags), $value, $compare);
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
	 * @param int    $flags      DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function where($expression, $flags=0, $subset=0)
	{
 		$this->part($flags & DB::HAVING ? 'having' : 'where', $expression, $flags, $subset);
		return $this;
	}

	/**
	 * Add HAVING expression to query statement.
	 *
	 * @param string $expression  HAVING expression
	 * @param int    $flags       DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
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
	 * @param int    $flags       DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
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
	 * @param int   $flags       DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
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
	 * @param int        $flags     DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
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
	 * @param int $flags     DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
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
     * @param array|string $table   Table name
     * @param array|string $cols    The columns to select from this table.
     * @param string       $join    join type: INNER JOIN, LEFT JOIN, etc
     * @param array|string $on      "querytable.column = tablename.column"
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	protected function addTableAndColumns($table, $join=null, $on=null, $cols=null, $flags=0, $subset=0)
	{
		$table = $this->sqlSplitter()->makeIdentifier($table, null, $flags);
		$this->addTable($table, $join, $on, $flags, $subset);
		if (isset($cols)) $this->addColumn($this->getColumnDBName($cols, $table, null, $flags), $flags, $subset);
		
		return $this;
	}
	
	/**
	 * Adds a table and optional columns to the query.
	 * 
     * @param array|string $table   Table name
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function from($table, $cols=null, $flags=0, $subset=0)
	{
		if ($subset == 0 && $this->getQueryType() == 'INSERT') $subset = 1;
		return $this->addTableAndColumns($table, null, null, $cols, $flags, $subset);
	}

	/**
	 * Adds a table to the query.
	 * 
     * @param array|string $table   Table name
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @return DB_SQLStatement
	 */
	public function into($table, $flags=0)
	{
		return $this->addTableAndColumns($table, null, null, null, $flags, 0);
	}
	
	/**
	 * Alias of Q\DB::joinInner()
	 * 
     * @param array|string $table   Table name
     * @param array|string $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	final public function join($table, $on, $cols=null, $flags=0, $subset=0)
	{
		return $this->joinInner($table, $on, $cols, $flags, $subset);
	}
	
	/**
	 * Adds an INNER JOIN table and columns to the query.
	 * 
     * @param array|string $table   Table name
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinInner($table, $on, $cols=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'INNER JOIN', $on, $cols, $schema);
	}	

	/**
	 * Adds an LEFT JOIN table and columns to the query.
	 * 
     * @param array|string $table   Table name
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinLeft($table, $on, $cols=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'LEFT JOIN', $on, $cols, $flags, $subset);
	}	

	/**
	 * Adds an RIGHT JOIN table and columns to the query.
	 * 
     * @param array|string $table   Table name
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinRight($table, $on, $cols=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'RIGHT JOIN', $on, $cols, $flags, $subset);
	}
	
	/**
	 * Adds an FULL JOIN table and columns to the query.
	 * 
     * @param array|string $table   Table name
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinFull($table, $on, $cols=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'FULL JOIN', $on, $cols, $flags, $subset);
	}
	
	/**
	 * Adds an CROSS JOIN table and columns to the query.
	 * 
     * @param array|string $table   Table name
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinCross($table, $on, $cols=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'CROSS JOIN', $on, $cols, $flags, $subset);
	}
	
	/**
	 * Adds an NATURAL JOIN table and columns to the query.
	 * 
     * @param array|string $table   Table name
     * @param string       $on      "querytable.column = tablename.column"
     * @param array|string $cols    The columns to select from this table.
	 * @param int          $flags   DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function joinNatural($table, $on, $cols=null, $flags=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'NATURAL JOIN', $on, $cols, $flags, $subset);
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
	
	
	//------------- Get metadata ------------------------
	
	/**
	 * Get a table interface of one the tables used in this statement.
	 *
	 * @param string $index   Table name/alias or NULL to get base table
	 * @param int    $subset  Get the table of a subquery (0=main query)
	 * @return DB_Table
	 */
	public function getTable($index=null, $subset=0)
	{
		if (!isset($index)) {
	    	if (!isset($this->basetable) && !$this->guessBaseTable()) throw new Exception("Unable to determine a base table: Statement doesn't have any tables");
			if (is_string($this->bastable)) $this->basetable = $this->connection()->table($this->bastable);
	    	return $this->basetable;
		}

		$tables = $this->getTablenames($subset, DB::SPLIT_ASSOC);
		if (!isset($tables[$index])) throw new Exception("Table '$name' is not used in the statement");
		
		return $tables[$index] == $this->basetable ? $this->basetable : $this->connection()->table($tables[$index]);
	}
   	
   	/**
   	 * Get a field from one of the tables used in the statement.
   	 * 
   	 * @param string $name    Field name
	 * @param int    $subset  Get the tables of a subquery (0=main query)
	 * @return DB_Field
   	 */
   	public function lookupField($name, $subset=0)
   	{
   		if (!$this->sqlSplitter()->validIdentifier($column)) throw new Exception("Did not find field '$column' in any of the tables used in the statement");;
   		
   		list($table, $column) = $this->sqlSplitter()->splitIdentifier($name);
   		if (isset($table) || $this->getTable()->hasField($column)) return $this->getTable($table)->getField($column);

   		foreach ($this->getTablenames($subset, DB::SPLIT_ASSOC) as $alias=>$table) {
   			$ti = $this->connection()->table($table);
   			if ($tbl->hasField($column)) {
   				if (isset($field)) throw new Exception("Found field '$column' in table '$found_alias' as well as in '$alias': Specify the table name");
   				$field = $ti->getField($column);
   				$found_alias = $alias;
   			} 
   		}
   		
   		if (!isset($field)) throw new Exception("Did not find field '$column' in any of the tables used in the statement");
   		return $field;
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
   	    if (!isset($this->getConnection())) throw new Exception("Unable to execute statement: Statement object isn't connectioned to a database connection."); 

		if (func_num_args() > 1) $args = func_get_args();
		return $this->getConnection()->query($this, $args);
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

   	    if (!isset($this->getConnection())) throw new Exception("Unable to execute statement: Statement object isn't connectioned to a database connection."); 
   		
   		$parts = $this->getParts();
   		
   		if ($qt === 'INSERT') {
   			$matches = null;
   			if (sizeof($parts) > 1 && preg_match('/^\#sub(\d+)$/', trim($parts['query']), $matches)) $parts[0] = $parts[(int)$matches[1]];
   			 else $parts[0] = $this->sqlSplitter()->split($parts['query']);
   		}
   		
   		$parts[0]['where'] = 'FALSE';
   		$parts[0]['having'] = '';
   		
   		$class = get_class($this);
   		$this->emptyResult = $this->getConnection()->query(new $class($this, $this->getConnection()->parse($this->sqlSplitter()->joinInject($parts), null)));
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
     * @param string|int $index  Field name or index
     * @return DB_Field
     */
   	public function getField($index)
   	{
   		return $this->executeEmpty()->getField($index);
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
   			$this->countStatement[$flags & DB::ALL_ROWS] = $this->getConnection()->parse($this->sqlSplitter()->buildCountStatement(count($parts) == 1 ? reset($parts) : $this->getStatement(), $flags), false);
   		}
   		
   		return $this->getConnection()->query($this->countStatement[$all])->fetchValue();
   	}
   	
	/**
	 * Create a new record using the fields of the result of this statement.
	 * 
	 * @return DB_Record
	 */
	public function newRecord()
	{
		return $this->executeEmpty()->newRecord();
	}
}
