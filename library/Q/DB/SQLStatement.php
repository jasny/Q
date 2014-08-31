<?php
namespace Q;

require_once 'Q/DB/Statement.php';

/**
 * Abstraction layer for SQL query statements.
 * All editing statements support fluent interfaces.
 * 
 * @package DB
 */
class DB_SQLStatement
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
	 * The type of the query
	 * @var string
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
	 * Extracted subqueries
	 * @var DB_SQLStatement
	 */
	protected $subqueries;
	
	
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
			if (isset($classes['sqlSplitter'])) $this->sqlSplitter = new $classes['sqlSplitter'](); 
		} elseif ($source instanceof DB) {
		    $this->connection = $source;
		    if (isset($this->getConnection()->sqlSplitter)) $this->sqlSplitter = $this->getConnection()->sqlSplitter;
	    } elseif ($source instanceof DB_Table) {
	        $this->connection = $source->getConnection();
	        $this->basetable = $source;
	        if (isset($this->getConnection()->sqlSplitter)) $this->sqlSplitter = $this->getConnection()->sqlSplitter;
	    } elseif ($source instanceof self) {
	        $this->connection = $source->connection;
	        $this->basetable = $source->basetable;
	        if (isset($source->sqlSplitter)) $this->sqlSplitter = $source->sqlSplitter;
	    } elseif (isset($source)) {
	        throw new Exception('Source of statement can only be a Q\DB, Q\DB_Table, Q\DB_SQLStatement or driver name, not a ' . (is_object($source) ? get_class($source) : gettype($source)));
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
	function getSQLSplitter()
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
	public function getType()
	{
		if (!isset($this->queryType)) $this->queryType = $this->getSQLSplitter()->getQueryType($this->statement);
		return $this->queryType;
	}

	/**
	 * Resolving symantic mapping.
	 *
	 * @param string $expression
	 * @param int    $flags       Bitset of DB::FIELDNAME_% and DB::QUOTE_% options
	 * @return string
	 */
	public function resolveMapping($identifier, $flags=DB::FIELDNAME_COLUMN)
	{
		if ($flags & 0xf == 0) $flags |= DB::FIELDNAME_COLUMN;
		return $this->lookupField($identifier)->getName($flags);
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
	    if (empty($tables) && isset($expression)) $tables = $this->getSQLSplitter()->splitTables($expression, DB::SPLIT_ASSOC);
	    
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
		
		if (!isset($this->cachedStatement)) $this->cachedStatement = $this->getSQLSplitter()->join($this->getParts());
		return $this->cachedStatement;
	}

	/**
	 * Get a subquery (from base statement).
	 * 
	 * @param int $subset  Number of subquery (start with 1)
	 * @return DB_SQLStatement
	 */
	public function getSubquery($subset=1)
	{
		if (!isset($this->subqueries)) { 
			$statements = $this->getSQLSplitter()->extractSubsets($this->statement);
			$this->baseParts = $this->getSQLSplitter()->split($statements[0]);
			unset($statements[0]);
			
			foreach ($statements as $i=>$statement) $this->subqueries[$i] = new static($statement, $this);
		}
			
		if (!isset($this->subqueries[$subset])) throw new Exception("Unable to get subquery #$subset: Query only has " . count($this->subqueries) . (count($this->subqueries) == 1 ? " subquery." : " subqueries."));
		return $this->subqueries[$subset];
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
		return $this->getSQLSplitter()->parse($this, $args);
	}

	/**
	 * Apply the added and replacement parts to the parts of the base query.
	 * 
	 * @param int $subset  Get the parts of a subquery (0=main query)
	 * @return array
	 */
	public function getParts()
	{
		if (!isset($this->cachedParts)) {
			if (!isset($this->baseParts)) $this->baseParts = $this->getSQLSplitter()->split($this->statement);
			if (empty($this->partsAdd) && empty($this->partsReplace)) return $this->baseParts;
	
			$parts = $this->baseParts;
			if (!empty($this->partsReplace)) $parts = array_merge($parts, $this->partsReplace);
			if (!empty($this->partsAdd)) $parts = $this->getSQLSplitter()->addParts($parts, $this->partsAdd);
			if (key($parts) == 'select' && empty($parts['columns'])) $parts['columns'] = '*';
			
			$this->cachedParts =& $parts;
		}		
		
		return empty($this->subqueries) ? $this->cachedParts : $this->getSQLSplitter()->injectSubsets(array($this->cachedParts) + $this->subqueries);
	}

	/**
	 * Check if statement has a specific part.
	 * 
	 * @param string $key     The key identifying the part
	 * @param int    $subset  Get the parts of a subquery (0=main query)
	 * @return boolean
	 */
	public function hasPart($key)
	{
		if (!isset($this->baseParts)) $this->baseParts = $this->getSQLSplitter()->split($this->statement);
		return array_key_exists($key, $this->baseParts) || !empty($this->partsReplace[$key]) || !empty($this->partsAdd[$key]);
	}
	
	/**
     * Return a specific part of the statement.
     *
	 * @param mixed $key  The key identifying the part
     * @return string
     * 
     * @throws Exception if part doesn't exist.
     */
	public function getPart($key)
	{
		$parts = $this->getParts();
		if (!array_key_exists($key, $parts)) throw new Exception("Unable to get $key part for " . $this->getType() . " statement.");
		
		return $parts[$key];
	}
	
	/**
	 * Get the tables used in this statement.
	 * 
	 * {@internal Using cache is slightly inconvenient, however we don't want to split this each time when we need to lookup a fieldname}}
	 *
	 * @param int $flags  DB::SPLIT_% options
	 * @return DB_Table
	 */
	public function getTablenames($flags=0)
	{
		if (!isset($this->cachedTablenames)) $this->cachedTablenames = $this->getSQLSplitter()->splitTables($this->getParts(), $flags);
		return $this->cachedTablenames;
	}
	
	/**
	 * Get the columns used in the statement.
	 * 
	 * @param int $flags  DB::SPLIT_% and DB::UNQUOTE options
	 * @return array
	 */
	public function getColumns($flags=0)
	{
		return $this->getSQLSplitter()->splitColumns($this->getParts(), $flags);
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
		return $this->getSQLSplitter()->splitValues($this->getParts(), $flags);
	}
	
	/**
	 * Count the number of placeholders in the statement.
	 *
	 * @return int
	 */
	public function countPlaceholders()
	{
		return $this->getSQLSplitter()->countPlaceholders($this->getStatement());
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
	public function setPart($key, $expression, $flags=DB::REPLACE)
	{
		$key = strtolower($key);
		
		$this->clearCachedStatement();
		
		if ($flags & DB::REPLACE) $this->partsReplace[$key] = $expression;
		  else $this->partsAdd[$key][$flags & DB::PREPEND ? DB::PREPEND : DB::APPEND][] = $expression;
		
		if ($key == 'from' || $key == 'into' || $key == 'tables') {
			if (!$this->basetable) $this->guessBasetable($expression);
			unset($this->cachedTablenames);
		}
		
		return $this;
	}
	
   	/**
   	 * Add column to query statement.
   	 * 
   	 * Flags:
   	 *  Position:   DB::REPLACE, DB::PREPEND or DB::APPEND (default)
   	 *  Set values: DB::SET_VALUE (default) or DB::SET_EXPRESSION
   	 *  Quote expr: DB::QUOTE_%
	 *
	 * @param mixed $column  Column name or array with column names
	 * @param int   $flags   Options as bitset.
	 * @param int   $subset  Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addColumn($column, $flags=0)
   	{
   		$type = $this->getType();
   		$key = $type == 'UPDATE' || ($type == 'INSERT' && $this->hasPart('set')) ? 'set' : 'columns';
   		
   		if (is_array($column)) {
   			$splitter = $this->getSQLSplitter();
   			
   			if ($key == 'set') {
   				array_walk($column, $flags & DB::SET_EXPRESSION ?
   				  function(&$val, $key) use($flags, $splitter) {$val = $splitter->quoteIdentifier($key, $flags & ~DB::QUOTE_OPTIONS | DB::QUOTE_STRICT, array($this, 'resolveMapping')) . '=' . $splitter->mapIdentifiers($val, $flags, array($this, 'resolveMapping'));} :
   				  function(&$val, $key) use($flags, $splitter) {$val = $splitter->quoteIdentifier($key, $flags & ~DB::QUOTE_OPTIONS | DB::QUOTE_STRICT, array($this, 'resolveMapping')) . '=' . $splitter->quote($val);}
   				);
   			} else {
   				array_walk($column, function(&$val) use($flags, $splitter) {$val = $splitter->quoteIdentifier($val, $flags, array($this, 'resolveMapping'));});
   			}
   			
   			$column = join(', ', $column);
   		}
   		
		$this->setPart($key, $column, $flags);
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
	public function addTable($table, $join=null, $on=null, $flags=0)
   	{
   		switch ($this->getType()) {
   			case 'INSERT':	$key = 'into'; break;
   			case 'UPDATE':	$key = 'tables'; break;
   			default:		$key = 'from';
   		}
   		
   		if (!isset($join) && ~$flags & DB::REPLACE) $join = ',';

   		if (is_array($on)) $on = $this->getSQLSplitter()->quoteIdentifier($on[0], $flags & ~DB::QUOTE_OPTIONS | DB::QUOTE_STRICT, array($this, 'mapIdentifier')) . ' = ' . $this->quoteIdentifier($on[1], $flags & ~DB::QUOTE_OPTIONS | DB::QUOTE_STRICT, array($this, 'mapIdentifier'));
   		  elseif (isset($on) && $on !== false) $on = $this->getSQLSplitter()->quoteIdentifier($on, $flags & ~DB::QUOTE_OPTIONS | DB::QUOTE_LOOSE, array($this, 'mapIdentifier'));
   		  
   		if ($flags & DB::PREPEND && ~$flags & DB::REPLACE) {
   			$this->setPart($key, $this->getSQLSplitter()->quoteIdentifier($table, $flags) . ' ' . $join, $flags);
   			if (!empty($on)) $this->setPart($key, "ON $on", $flags & ~DB::PREPEND);
   		} else {
			$this->setPart($key, $join . ' '. $this->getSQLSplitter()->quoteIdentifier($table, $flags) . (!empty($on) ? " ON $on" : ""), $flags);
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
   	public function addValues($values, $flags=0)
   	{
   		if (is_array($values)) {
   			$splitter = $this->getSQLSplitter();
   			foreach ($values as &$value) $value = $splitter->quote($value, 'DEFAULT');
   			$values = join(', ', $values);
   		}
   		
   		$this->setPart('values', $values, $flags);
		return $this;
   	}
   	   	
	/**
	 * Add criteria as WHERE expression to query statement.
	 * If $column is an array with (column=>value) and value is null, that part is ignored. 
	 * 
	 * @param mixed  $column    Expression, column name, column number or expression with placeholders, can also be an array of columns ($column[0]=$value OR $column[1]=$value) or array(column=>value, ...)
	 * @param mixed  $value     Value or array of values ($column=$value[0] OR $column=$value[1]) or NULL if $column is a where expression
	 * @param string $compare   Comparision operator, oa: =, !=, >, <, =>, <=, IS NULL, IS NOT NULL, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN, NOT IN, ALL and BETWEEN
	 * @param int    $flags     DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int    $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function where($column, $value=null, $compare="=", $flags=0)
	{
		$compare = preg_replace('/^\s+|\s{2,}|\s+$/', '', $compare);
		
		if (!is_scalar($column) && is_string(key($column))) {
			$parts = null;
			
			foreach ($column as $col=>&$value) {
				if (!isset($value) && $compare != 'IS NULL' && $compare != 'IS NOT NULL') continue;
				
				$col = $this->getSQLSplitter()->quoteIdentifier($col, $flags, array($this, 'resolveMapping'));
				$p = $this->getSQLSplitter()->buildWhere($col, $value, $compare);
				if (isset($p['where'])) $parts['where'][] = $p['where'];
				if (isset($p['having'])) $parts['having'][] = $p['having'];
			}
			
			if (isset($parts['where'])) $parts['where'] = join($flags & DB::GLUE_OR ? ' OR ' : ' AND ', $parts['where']);
			if (isset($parts['having'])) {
				if (count($parts['having']) > 1 && $flags & DB::GLUE_OR) throw new Exception("Criteria doing an '$compare' comparision can't by glued with OR, only with AND.");
				$parts['having'] = join(' AND ', $parts['having']);
			}
		} elseif (isset($value)) {
			if (is_scalar($column)) {
				$column = $this->getSQLSplitter()->quoteIdentifier($column, $flags, array($this, 'resolveMapping'));
			} else {
				foreach ($column as &$col) $col = $this->getSQLSplitter()->quoteIdentifier($col, $flags, array($this, 'resolveMapping'));
			}
			$parts = $this->getSQLSplitter()->buildWhere($column, $value, $compare);
		} else {
			if (is_scalar($column)) {
				$parts['where'] = $this->getSQLSplitter()->quoteIdentifier($column, $flags, array($this, 'resolveMapping'));
			} else {
				foreach ($column as &$col) $col = $this->getSQLSplitter()->quoteIdentifier($col, $flags, array($this, 'resolveMapping'));
				$parts['where'] = join($flags & DB::GLUE_OR ? ' OR ' : ' AND ', $column);
			}
		}
		
		if (isset($parts['having']) && $flags & DB::HAVING) throw new Exception("Criteria doing an '$compare' comparision can only be used as WHERE, not as HAVING expression.");
		
		if (isset($parts['where'])) $this->setPart($flags & DB::HAVING ? 'having' : 'where', $parts['where'], $flags);
		if (isset($parts['having'])) $this->setPart('having', $parts['having'], $flags);
		
		return $this;
	}
	
	/**
	 * Add criteria as WHERE expression to query statement.
	 * If $column is an array with (column=>value) and value is null, that part is ignored. 
	 *
	 * {@internal Calls where() method with DB::HAVING flag}}
	 * 
	 * @param mixed  $column    Expression, column name, column number or expression with placeholders, can also be an array of columns ($column[0]=$value OR $column[1]=$value) or array(column=>value, ...)
	 * @param mixed  $value     Value or array of values ($column=$value[0] OR $column=$value[1]) or NULL if $column is a where expression
	 * @param string $compare   Comparision operator, oa: =, !=, >, <, =>, <=, IS NULL, IS NOT NULL, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN, NOT IN, ALL and BETWEEN
	 * @param int    $flags     DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int    $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public final function having($column, $value=null, $compare="=", $flags=0)
	{
 		$this->where($column, $value, $compare, $flags | DB::HAVING);
		return $this;
	}
	
	/**
	 * Add GROUP BY expression to query statement.
	 *
	 * @param string|array $column  GROUP BY expression (string) or array with columns
	 * @param int          $flags       DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int          $subset      Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function groupBy($column, $flags=0)
	{
		if (is_scalar($column)) {
			$column = $this->getSQLSplitter()->quoteIdentifier($column, $flags, array($this, 'resolveMapping'));
		} else {
			foreach ($column as &$col) $col = $this->getSQLSplitter()->quoteIdentifier($col, $flags, array($this, 'resolveMapping'));
			$column = join(', ', $column);
		}
		
 		$this->setPart('group by', $column, $flags);
		return $this;
	}

	/**
	 * Add ORDER BY expression to query statement.
	 * NOTE: In contrary of addStatement(), the statement is prepended by default (use DB_Statment_SQL::APPEND to append)
	 *
	 * @param mixed $column  ORDER BY expression (string) or array with columns
	 * @param int   $flags       DB::REPLACE, DB::PREPEND or DB::APPEND + DB::QUOTE_% + other options as bitset.
	 * @param int   $subset      Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function orderBy($column, $flags=0)
	{
		if (is_scalar($column)) {
			if ($flags & DB::DESC) $column .= ' DESC';
			  elseif ($flags & DB::ASC) $column .= ' ASC';
		} else {
			if ($flags & DB::DESC) {
				foreach ($column as &$col) $col .= ' DESC';
			} elseif ($flags & DB::ASC) {
				foreach ($column as &$col) $col .= ' ASC';
			}
			
			$column = join(', ', $column);
		}
				
 		if (!($flags & DB::APPEND)) $flags |= DB::PREPEND;
		$this->setPart('order by', $column, $flags);
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
	public function limit($rowcount, $offset=null, $flags=0)
	{
		$this->setPart('limit', $rowcount . (isset($offset) ? " OFFSET $offset" : ""), $flags | DB::REPLACE);
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
	public function page($page, $rowcount, $flags=0)
	{
		$this->setLimit($rowcount, $rowcount * ($page-1), $flags | DB::REPLACE);
		return $this;
	}
	
	
   	//------------- Finalize changes ------------------------
   	
	/**
	 * Check if statement has been modified.
	 * 
	 * @return boolean
	 */
	public function hasChanged()
	{
		if (!empty($this->partsAdd) || !empty($this->partsReplace)) return true;
		
		foreach ((array)$this->subqueries as $subquery) {
			if ($subquery->hasChanged()) return true;
		}
		
		return false;
	}
	
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
	 * Build a new statement with a different query type, based on this query
	 * 
	 * @param string $type  SELECT, INSERT, REPLACE, UPDATE or DELETE
	 * @return DB_SQLStatement
	 */
	public function convertTo($type)
	{
		return new static($this->getSQLSplitter()->convertStatement($this, $type), $this);
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
	public function getTable($index=null)
	{
		if (!isset($index)) {
	    	if (!isset($this->basetable) && !$this->guessBaseTable()) throw new Exception("Unable to determine a base table: Statement doesn't have any tables");
			if (is_string($this->bastable)) $this->basetable = $this->connection()->table($this->bastable);
	    	return $this->basetable;
		}

		$tables = $this->getTablenames(DB::SPLIT_ASSOC);
		if (!isset($tables[$index])) throw new Exception("Table '$index' is not used in the statement");
		
		return $tables[$index] == $this->basetable ? $this->basetable : $this->connection()->table($tables[$index]);
	}
   	
   	/**
   	 * Get a field from one of the tables used in the statement.
   	 * 
   	 * @param string $name  Field name
	 * @return DB_Field
	 * 
	 * @throws DB_UnkownFieldException if field can't be found or is found in multiple tables.
   	 */
   	public function lookupField($name)
   	{
   		list($table, $column) = $this->getSQLSplitter()->splitIdentifier($name);
   		if (isset($table)) return $this->getTable($table)->getField($column); 
   		
   		foreach ($this->getTablenames(DB::SPLIT_ASSOC) as $alias=>$table) {
   			$tg = $this->connection()->table($table);
   			if ($tg->hasField($column)) {
   				$found = $tg->getField($column);
   				$found_tables[] = $table;
   			}
   		}
   		
   		if (!isset($found)) throw new DB_UnkownFieldException("Did not find field '$column' in any of the tables used in the statement.");
   		if (count($found_tables) > 1) throw new DB_UnkownFieldException("Found field '$column' in multiple tables: " . join(',', $found_tables) . ". Please specify the table by using TABLE.COLUMN.");
   		
   		return $found;
   	}
   		
	
	//------------- Excecute ------------------------

	/**
	 * Execute the query statement.
	 * 
	 * @param array $args  Arguments to be parsed into the query on placeholders.
	 * @return DB_Result
	 */
	public function exec($args=null)
	{
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
   	    
   		if ($this->getType() !== 'SELECT') throw new Exception("Unable to get a result for a " . $this->getType() . " statement. " . $this->getStatement());

   		$parts = $this->getParts();
   		$parts['where'] = 'FALSE';
   		$parts['having'] = '';
   		
   		$this->emptyResult = $this->getConnection()->query($this->getSQLSplitter()->parse($this->getSQLSplitter()->join($parts), false));
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
     * 
     * @param int $flags  Optional DB::ALL_ROWS
     * @return int
     */
   	public function countRows($flags=0)
   	{
   	    $all = $flags & DB::ALL_ROWS;
   		if (!isset($this->countStatement[$all])) {
   			$parts = $this->getParts();
   			$this->countStatement[$all] = $this->getConnection()->parse($this->getSQLSplitter()->buildCountStatement(count($parts) == 1 ? reset($parts) : $this->getStatement(), $flags), false);
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
