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
	protected $link;
	
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
	protected $queryType = array();
	
	
	/**
	 * The parts of the split base statement.
	 * @var array
	 */
	protected $baseParts;
	
	/**
	 * The column names ot the base statement.
	 * @var array
	 */
	protected $baseColumns=array();

	
	/**
	 * The parts to add to base statement, prepending the existing part.
	 * @var array
	 */
	protected $partsPrepend;

	/**
	 * The parts to add to base statement, appending the existing part.
	 * @var array
	 */
	protected $partsAppend;
	
	/**
	 * The parts to replace parts of to base statement.
	 * @var array
	 */
	protected $partsReplace;
		
		
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
	protected $cachedColumns=array();

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
	 * @param mixed  $source     Q\DB, Q\DB_Table, Q\DB_SQLStatement or driver name (string)
	 * @param string $statement  Query statement
	 */
	public function __construct($source, $statement)
	{
		if (is_string($source)) {
			if (!isset(DB::$drivers[$source])) throw new Exception("Unable to create SQL statement: Unknown driver '$source'");
			$class = DB::$drivers[$source];
			if (!load_class($class)) throw new Exception("Unable to create SQL statement: Class '$class' for driver '$source' does not exist.");
			
			$refl = new ReflectionClass($class);
			$classes = $refl->getStaticPropertyValue('classes');
			if (isset($classes['sqlSplitter'])) $this->sqlSplitter = new $classes['sqlSplitter'](); 
		} elseif ($source instanceof DB) {
		    $this->link = $source;
		    if (isset($this->link->sqlSplitter)) $this->sqlSplitter = $this->link->sqlSplitter;
	    } elseif ($source instanceof DB_Table) {
	        $this->link = $source->getLink();
	        $this->basetable = $source;
	        if (isset($this->link->sqlSplitter)) $this->sqlSplitter = $this->link->sqlSplitter;
	    } elseif ($source instanceof self) {
	        $this->link = $source->getLink();
	        $this->basetable = $source->getBaseTable();
	        if (isset($source->sqlSplitter)) $this->sqlSplitter = $source->sqlSplitter;
	    } elseif (isset($source)) {
	        throw new Exception("Parent of statement can only be a Q\DB or Q\DB_Table, not a " . (is_object($source) ? get_class($source) : gettype($source)));
	    }

		if (!isset($this->sqlSplitter)) trigger_error("The driver '" . (isset($this->link) ? get_class($this->link) : $source) . "' doesn't support query splitting. This will cause issues when you try to modify the statement.", E_USER_NOTICE);
		$this->statement = $statement;
	}
	
	/**
	 * Get the database connection.
	 * 
	 * @return DB
	 */
	function getLink()
	{
		return $this->link;
	}
	
	/**
	 * Get the table definition, responsible for this result
	 *
	 * @return Table
	 */
	public function getBaseTable()
	{
	    if (isset($this->basetable) && is_string($this->basetable)) $this->basetable = $this->link->table($this->basetable);
	    return $this->basetable;
	}
	
	/**
	 * Get database name for column.
	 * Mapped fieldname (starting with '#') will be resolved.
	 *
	 * @param mixed  $column   Column name or column index, multiple columns may be specified as array
	 * @param string $table    Default table for column
	 * @param int    $options  Options about how to quote $column
	 * @return string
	 * 
	 * @todo HIGH PRIO! Make SQLStatement::getColumnDbName() work for column indexes
	 * @todo HIGH PRIO! Implement support for $table, currently base table is alway used as default.
	 */
	public function getColumnDBName($column, $table=null, $options=0)
	{
   		if (is_array($column)) return array_map(array(__CLASS__, __FUNCTION__), $column);
		
   		if ($column[0] !== '#') return $column;
		
		if (!$this->getBaseTable()) throw new DB_Exception("Unable to add criteria for column '$column'. Unable to resolve symantic data mapping, because statement does not have a base table. (It is not created by a DB_Table object)");
		$col_db = $this->getBaseTable()->getFieldProperty($column, 'name_db');
		if (empty($col_db)) throw new DB_Exception("Unable to add criteria for column '$column', no field with that mapping for table definition '" . $this->baseTable->getName() . "'.");
		
		return $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTablename(), $col_db);
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
		if (empty($this->partsPrepend) && empty($this->partsAppend) && empty($this->partsReplace)) return $this->getBaseStatement();
	
		if (!isset($this->cachedStatement)) $this->cachedStatement = empty($this->partsAdd) && empty($this->partsReplace) ? $this->statement : $this->sqlSplitter->joinInject($this->getParts());
		if (func_num_args() == 0) return $this->cachedStatement;
		
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
		if (empty($this->partsPrepend) && empty($this->partsAppend) && empty($this->partsReplace)) return $this->getBaseParts($extract);
		if (isset($this->cachedParts[$extract])) return $this->cachedParts[$extract];
		
		$use_subsets = $extract || sizeof($this->partsAdd) > (int)isset($this->partsAdd[0]) || sizeof($this->partsReplace) > (int)isset($this->partsReplace[0]);
		if ($use_subsets) $sets_parts = $this->getBaseParts(true);
		  else $sets_parts = array($this->getBaseParts(false));
		
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
	 * Use DB_SQLStatement::ADD_PREPEND in $options to prepend a statement (append is default)
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $statement
	 * @param int    $options    Addition options as binairy set.
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addToPart($key, $statement, $options=0, $subset=0)
	{
		$var = $options & DB::ADD_REPLACE ? 'partsReplace' : ($options & DB::ADD_PREPEND ? 'partsPrepend' : 'partsAppend');
		$part =& $this->$var[$subset][strtolower($key)];
		$part[] = $statement;
		
		$this->clearCachedStatement();
		if ($key == 'columns' || $key == 'set') $this->cachedColumns = null;
		if ($key == 'values') $this->cachedValues = null;
		
		return $this;
	}

	/**
	 * Replace any part of the query
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $statement
	 * @param int    $options    Addition options as binairy set.
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function replacePart($key, $statement, $options=0, $subset=0)
	{
		$this->addToPart($key, $statement, $options | DB::ADD_REPLACE, $subset);
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
	 * @param int   $subset   Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addColumn($column, $options=0, $subset=0)
   	{
   		$type = $this->getQueryType($subset);
   		$key = $type == 'UPDATE' || ($type == 'INSERT' && $this->hasPart('set', $subset)) ? 'set' : 'columns';
   		
   		$column = $this->getColumnDBName($column, null, $options);
		$this->addToPart($key, is_array($column) ? join(', ', $column) : $column, $options, $subset);
		return $this;
   	}

	/**
	 * Add a join statement to the from part.
	 *
	 * @param mixed  $table    tablename or "tablename ON querytable.column = tablename.column"
	 * @param string $join     join type: INNER JOIN, LEFT JOIN, etc
	 * @param string $on       "querytable.column = $table.column" or array(querytable.column, $table.column); 
	 * @param int    $options  Addition options as binairy set
	 * @param int    $subset   Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addTable($table, $join=null, $on=null, $options=0, $subset=0)
   	{
   		switch ($this->getQueryType($subset)) {
   			case 'INSERT':	$key = 'into'; break;
   			case 'UPDATE':	$key = 'tables'; break;
   			default:		$key = 'from';
   		}
   		
   		if (is_array($on)) $on = $this->getColumnDbName($on[0]) . ' = ' . $this->getColumnDbName($on[1], $table);
   		  
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
	 * @param mixed $subset   Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
   	 */
   	public function addValues($values, $options=0, $subset=0)
   	{
   		if (is_array($values)) {
   			foreach ($values as $i=>$value) $values[$i] = $this->sqlSplitter->quote($value, 'DEFAULT');
   			$values = join(', ', $values);
   		}
   		$this->addToPart('values', $values, $options, $subset);
		return $this;
   	}
   	   	
	/**
	 * Add criteria as where or having statement as $column=$value.
	 * If $value == null and $compare == '=', $compare becomes 'IS NULL'.
	 * 
	 * @param mixed  $column    Column name, column number or expression with placeholders, can also be an array of columns ($column[0]=$value OR $column[1]=$value)
	 * @param mixed  $value     Value or array of values ($column=$value[0] OR $column=$value[1])
	 * @param string $compare   Comparision operator: =, !=, >, <, =>, <=, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN, NOT IN, ALL and BETWEEN
	 * @param int    $options   Addition options (language specific) as binairy set
	 * @param int    $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addCriteria($column, $value, $compare="=", $options=0, $subset=0)
	{
		$parts = $this->sqlSplitter->buildWhere($this->getColumnDbName($column), $value, $compare);
		if (isset($parts['having']) && $options & DB::ADD_HAVING) throw new Exception("Criteria doing an '$compare' comparision can only be used as WHERE not as HAVING expression.");
		
		if ($subset === 0 && $object->getQueryType() === 'INSERT' && $this->hasPart('query', 0)) $subset = 1;
		$this->addWhere($parts['where'], $options, $subset);
		$this->addHaving($parts['having'], $options, $subset);
		
		return $this;
	}
	
	/**
	 * Add WHERE expression to query statement.
	 *
	 * @param string $statement  WHERE expression
	 * @param int    $options    Addition options as binairy set
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addWhere($statement, $options=0, $subset=0)
	{
 		$this->addToPart($options & DB::ADD_HAVING ? 'having' : 'where', $statement, $options, $subset);
		return $this;
	}

	/**
	 * Add HAVING expression to query statement.
	 *
	 * @param string $statement  HAVING expression
	 * @param int    $options    Addition options as binairy set
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function addHaving($statement, $options=0, $subset=0)
	{
 		$this->addWhere($statement, $options | DB::ADD_HAVING, $subset);
		return $this;
	}
	
	/**
	 * Add GROUP BY expression to query statement.
	 *
	 * @param string $statement  GROUP BY statement (string) or array with columns
	 * @param int    $options    Addition options as binairy set
	 * @param int    $subset     Specify to which subquery the change applies (0=main query)
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
	 * Alias of Q\DB_SQLStatement::addOrderBy().
	 *
	 * @param mixed  $statement  ORDER BY statement (string) or array with columns
	 * @param int    $options    Addition options as binairy set
	 * @param mixed  $subset     Specify to which subquery the change applies
	 * @return DB_SQLStatement
	 */
	final public function setOrder($column, $options=0, $subset=null)
	{
		$this->addOrderBy($column, $options, $subset);
		return $this;
	}
	
	/**
	 * Add ORDER BY statement to query statement.
	 * NOTE: In contrary of addStatement(), the statement is prepended by default (use DB_Statment_SQL::ADD_APPEND to append)
	 *
	 * @param mixed $statement  ORDER BY statement (string) or array with columns
	 * @param int   $options    Addition options as binairy set
	 * @param int   $subset     Specify to which subquery the change applies (0=main query)
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
	 * @param int        $offset    Start at row
	 * @param int        $options   Addition options as binairy set
	 * @param int        $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function setLimit($rowcount, $offset=null, $options=0, $subset=0)
	{
		$this->replacePart('limit', $rowcount . (isset($offset) ? " OFFSET $offset" : ""), $options, $subset);
		return $this;
	}

	/**
	 * Set the limit by specifying the page.
	 *
	 * @param int $page      Page numer, starts with page 1
	 * @param int $rowcount  Number of rows per page
	 * @param int $options   Addition options as binairy set
	 * @param int $subset    Specify to which subquery the change applies (0=main query)
	 * @return DB_SQLStatement
	 */
	public function setPage($page, $rowcount, $options=0, $subset=0)
	{
		$this->setLimit($rowcount, $rowcount * ($page-1), $options, $subset);
		return $this;
	}

	
	//------------- Build statement - Add table ----------------
	
	/**
	 * Adds a table and optional columns to the query.
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param array|string $cols   The columns to select from this table.
     * @param string       $join   join type: INNER JOIN, LEFT JOIN, etc
     * @param array|string $on     "querytable.column = tablename.column"
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	protected function addTableAndColumns($table, $join=null, $on=null, $cols='*', $schema=null, $options=0, $subset=0)
	{
		$table = isset($schema) ? $this->sqlSplitter->makeIdentifier($schema, $table) : $this->sqlSplitter->quoteIdentifier($table);
		$this->addTable($table, $join, $on);
		
		foreach ((array)$cols as $col) {
			$this->addColumn($this->sqlSplitter->makeIdentifier($table, $col));
		}

		return $this;
	}
	
	/**
	 * Adds a FROM table and optional columns to the query.
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param array|string $cols   The columns to select from this table.
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function from($table, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->addTableAndColumns($table, null, null, $cols, $schema);
	}

	/**
	 * Alias of Q\DB::joinInner()
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param array|string $on     "querytable.column = tablename.column"
     * @param array|string $cols   The columns to select from this table.
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	final public function join($table, $on, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->joinInner($table, $on, $cols, $schema);
	}
	
	/**
	 * Adds an INNER JOIN table and columns to the query.
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param string       $on     "querytable.column = tablename.column"
     * @param array|string $cols   The columns to select from this table.
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinInner($table, $on, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'INNER JOIN', $on, $cols, $schema);
	}	

	/**
	 * Adds an LEFT JOIN table and columns to the query.
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param string       $on     "querytable.column = tablename.column"
     * @param array|string $cols   The columns to select from this table.
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinLeft($table, $on, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'LEFT JOIN', $on, $cols, $schema);
	}	

	/**
	 * Adds an RIGHT JOIN table and columns to the query.
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param string       $on     "querytable.column = tablename.column"
     * @param array|string $cols   The columns to select from this table.
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinRight($table, $on, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'RIGHT JOIN', $on, $cols, $schema);
	}
	
	/**
	 * Adds an FULL JOIN table and columns to the query.
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param string       $on     "querytable.column = tablename.column"
     * @param array|string $cols   The columns to select from this table.
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinFull($table, $on, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'FULL JOIN', $on, $cols, $schema);
	}
	
	/**
	 * Adds an CROSS JOIN table and columns to the query.
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param string       $on     "querytable.column = tablename.column"
     * @param array|string $cols   The columns to select from this table.
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinCross($table, $on, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'CROSS JOIN', $on, $cols, $schema);
	}
	
	/**
	 * Adds an NATURAL JOIN table and columns to the query.
	 * 
     * @param array|string $table  The table name or an associative array relating table name to correlation name.
     * @param string       $on     "querytable.column = tablename.column"
     * @param array|string $cols   The columns to select from this table.
     * @param string       $schema The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinNatural($table, $on, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->addTableAndColumns($table, 'NATURAL JOIN', $on, $cols, $schema);
	}

	
	/**
	 * Alias of Q\DB::joinInnerUsing()
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	final public function joinUsing($table, $on_column, $cols='*', $schema=null, $options=0, $subset=0)
	{
		return $this->joinInnerUsing($table, $on_column, $cols, $schema);
	}
	
	/**
	 * Adds an INNER JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	final public function joinInnerUsing($table, $on_column, $cols='*', $schema=null, $options=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table);
		return $this->addTableAndColumns($table, 'INNER JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}	

	/**
	 * Adds an LEFT JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinLeftUsing($table, $on_column, $cols='*', $schema=null, $options=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table);
		return $this->addTableAndColumns($table, 'LEFT JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}	

	/**
	 * Adds an RIGHT JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinRightUsing($table, $on_column, $cols='*', $schema=null, $options=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table);
		return $this->addTableAndColumns($table, 'RIGHT JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}
	
	/**
	 * Adds an FULL JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinFullUsing($table, $on_column, $cols='*', $schema=null, $options=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table);
		return $this->addTableAndColumns($table, 'FULL JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}
	
	/**
	 * Adds an CROSS JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinCrossUsing($table, $on_column, $cols='*', $schema=null, $options=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table);
		return $this->addTableAndColumns($table, 'CROSS JOIN', $this->sqlSplitter->makeIdentifier($this->getBaseTable()->getTableName(), $on_column) . ' = ' . $this->sqlSplitter->makeIdentifier($table, $on_column), $cols);
	}
	
	/**
	 * Adds an NATURAL JOIN table ON column=column and columns to the query.
	 * 
     * @param array|string $table      The table name or an associative array relating table name to correlation name.
     * @param string       $on_column  Column name to using in join
     * @param array|string $cols       The columns to select from this table.
     * @param string       $schema     The schema name to specify, if any.
	 * @return DB_SQLStatement
	 */
	public function joinNaturalUsing($table, $on_column, $cols='*', $schema=null, $options=0, $subset=0)
	{
		if (isset($schema)) $table = $this->sqlSplitter->makeIdentifier($schema, $table);
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
	 * Build a new query statement committing all changes
	 * 
	 * @return DB_Statement
	 */
	public function commitToNew()
	{
		$class = get_class($this);
		return new $class($this, $this->buildStatement());
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
		$args = func_get_args();
		return call_user_func_array(array($this, 'execute'), $args);
	}

	/**
	 * Execute the query statement.
	 * 
	 * @param array $args  Arguments to be parsed into the query on placeholders.
	 * @return DB_Result
	 */
	public function execute($args=null)
	{
   	    if (!isset($this->link)) throw new Exception("Unable to execute statement: Statement object isn't linked to a database connection."); 

		// Parse arguments
		if (func_num_args() > 2) {
			$args = func_get_args();
			array_shift($args);
		}
   	    
		return $this->link->query($this, $args);
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

   	    if (!isset($this->link)) throw new Exception("Unable to execute statement: Statement object isn't linked to a database connection."); 
   		
   		$parts = $this->getParts();
   		
   		if ($qt === 'INSERT') {
   			$matches = null;
   			if (sizeof($parts) > 1 && preg_match('/^\#sub(\d+)$/', trim($parts['query']), $matches)) $parts[0] = $parts[(int)$matches[1]];
   			 else $parts[0] = $this->sqlSplitter->split($parts['query']);
   		}
   		
   		$parts[0]['where'] = 'FALSE';
   		$parts[0]['having'] = '';
   		
   		$class = get_class($this);
   		$this->emptyResult = $this->link->query(new $class($this, $this->link->parse($this->sqlSplitter->joinInject($parts), false)));
   		return $this->emptyResult;
   	}
	
	/**
	 * Return the position of a field, based on the fieldname.
	 * 
	 * @param string $index
	 * @return int
	 */
   	public function getFieldIndex($index)
   	{
   		return $this->executeEmpty()->getFieldIndex($index);
   	}
   	
	/**
     * Returns the fieldnames for all columns.
     *
     * @param int $format  DB::FIELDNAME_* constant
     * @return array
     */
   	public function getFieldNames($format=DB::FIELDNAME_COL)
   	{
   		return $this->executeEmpty()->getFieldNames($format);
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
     * Execute the statement and return a specific field.
     *
     * @param mixed $index  Fieldname or index
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
     * @param boolean $all  Don't use limit
     * @return int
     */
   	public function countRows($all=false)
   	{
   	    $all = (boolean)$all;
   		if (!isset($this->countStatement[$all])) {
   			$parts = $this->getParts();
   			$this->countStatement[$all] = $this->link->parse($this->sqlSplitter->buildCountStatement(count($parts) == 1 ? reset($parts) : $this->getStatement(), $all), false);
   			if (!isset($this->countStatement[$all])) throw new DB_Exception("Unable to count rows for " . $this->getQueryType() . " query:\n" . $this->getStatement());
   		}
   		
   		return $this->link->query($this->countStatement[$all])->fetchValue();
   	}
   	
   	
	/**
	 * Load a record using this statement.
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
				if (!is_array($criteria)) $criteria = array($this->sqlSplitter->quoteIdentifier(reset($this->getFieldnames(DB::FIELDNAME_DB)), true)=>$criteria);
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
	 * Create a new record using the fields of the result of this statement.
	 * 
	 * @return DB_Record
	 */
	function newRecord()
	{
		return $this->executeEmpty()->newRecord();
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
}

