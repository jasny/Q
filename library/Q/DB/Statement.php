<?php
namespace Q;

/**
 * Abstraction layer for query statements.
 * All editing statements support fluent interfaces.
 * 
 * @package DB
 */
class DB_Statement
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
	 * Class constructor
	 *
	 * @param Q\DB|Q\DB_Table $source
	 * @param string            $statement
	 */
	public function __construct($source, $statement)
	{
	    if ($source instanceof DB) {
		    $this->link = $source;
	    } elseif ($source instanceof DB_Table) {
	        $this->link = $source->getLink();
	        $this->basetable = $source;
	    } elseif ($source instanceof self) {
	        $this->link = $source->getLink();
	        $this->basetable = $source->getBaseTable();
	    } elseif (isset($source)) {
	        throw new Exception("Parent of statement can only be a Q\DB or Q\DB_Table, not a " . (is_object($source) ? get_class($source) : gettype($source)));
	    }
	    
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
	
	
	//------------- Get query type ------------------------
   	
	/**
     * Return the type of the query statement.
     *
     * @return string
     */
	public function getQueryType()
	{
		return null;
	}

	
   	//------------- Get parts ------------------------
	
	/**
     * Return all the parts of the base statement.
     *
	 * @param boolean $extract  Extract subsets from main statement and split each subset seperatly
     * @return string
     */
	public function getParts($extract=false)
	{
		trigger_error("Splitting a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return null;
	}

	/**
     * Return a specific part of the base statement.
     *
	 * @param mixed $key     The key identifying the part
	 * @param int   $subset  Specify to wich subset the change applies
     * @return string
     */
	public function seekPart($key, $subset=null)
	{
		trigger_error("Splitting a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return null;
	}
		
	/**
     * Return the column names of the base statement
     *
	 * @param          Passed arguments are parsed into prepared statement.
     * @return string
     */
	public function getColumns($subset=null)
	{
		trigger_error("Splitting a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return null;
	}
	
	/**
	 * Apply the added and replacement parts to the parts of the base query.
	 * 
	 * @return array
	 */
	public function buildParts()
	{
		trigger_error("Splitting a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return null;
	}
	
	
	//------------- Get statement ------------------------
	
	/**
     * Return the statement without any appended parts.
     *
	 * @param Passed arguments are parsed into prepared statement.
     * @return string
     */
   	public function getBaseStatement()
   	{
		if (func_num_args() == 0) return $this->statement;
		
		$args = func_get_args();
		return $this->link->parse($this->statement, $args);
   	}

   	/**
     * Return a subquery without any added or replaced parts.
     *
	 * @param int $subset  Specify to wich subset the change applies
	 * @param Additional arguments are parsed into subquery statement.
     * @return string
     */
	public function getBaseSubset($subset)
	{
		if ($subset) {
			trigger_error("Splitting a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
			return null;
		}
		
		if (func_num_args() == 0) return $this->statement;
		
		$args = func_get_args();
		return $this->link->parse($this->statement, $args);
	}

	/**
	 * Return the query statement.
	 * 
	 * @param Passed arguments are parsed into prepared statement.
	 * @return string
	 */
	function getStatement()
	{
		if (func_num_args() == 0) return $this->statement;
		
		$args = func_get_args();
		return $this->link->parse($this->statement, $args);
	}
	
	/**
	 * Count the number of placeholders in the statement.
	 *
	 * @return int
	 */
	public function countPlaceholders()
	{
		return $this->link->getQuerySplitter()->countPlaceholders($this->getStatement());
	}

	
   	//------------- Add/Set part ------------------------
	
	/**
	 * Add a statement to any part of the query.
	 * Use DB_SQLStatement::ADD_PREPEND in $options to prepend a statement (append is default)
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $statement
	 * @param int    $options    Additional options as binairy set.
	 * @param int    $subset     Specify to wich subset the change applies (0=main query)
	 * @return DB_Statement
	 */
	public function addToPart($key, $statement, $options=0, $subset=null)
	{
		trigger_error("Editing a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return $this;
	}

	/**
	 * Replace any part of the query
	 *
	 * @param mixed  $key        The key identifying the part
	 * @param string $statement
	 * @param int    $options    Addition options as binairy set
	 * @param int    $subset     Specify to wich subset the change applies (0=main query)
	 * @return DB_Statement
	 */
	public function replacePart($key, $statement, $options=0, $subset=null)
	{
		trigger_error("Editing a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return $this;
	}

	
   	//------------- Add/Set specific part ------------------------
	
   	/**
   	 * Add column to query statement.
   	 * NOTE: This function does not escape the column name.
	 *
	 * @param mixed $column   Column name or array of column names
	 * @param int   $options  Addition options as binairy set
	 * @param mixed $subset   Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
   	 */
   	public function addColumn($column, $options=0, $subset=null)
	{
		trigger_error("Editing a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return $this;
	}

   	/**
   	 * Add a row of values to an insert query statement.
   	 * 
	 * @param mixed $values   Statement (string) or array of values
	 * @param int   $options  Addition options as binairy set
	 * @param mixed $subset   Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
   	 */
   	public function addValues($values, $options=0, $subset=null)
	{
		trigger_error("Editing a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return $this;
	}
   	
	/**
	 * Add criteria as where or having statement as $column=$value.
	 * If $value == null and $compare == '=', $compare becomes 'IS NULL'.
	 * 
	 * NOTE: This function does not escape the column name.
	 *
	 * @param mixed  $column   Column name, column number or array of column names ($column[0]=$value OR $column[1]=$value)
	 * @param mixed  $value    Value or array of values ($column=$value[0] OR $column=$value[1])
	 * @param string $compare  Comparision operator: =, !=, >, <, =>, <=, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN, NOT IN, ALL and BETWEEN
	 * @param int    $options  Addition options as binairy set
	 * @param mixed  $subset   Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function addCriteria($column, $value, $compare="=", $options=0, $subset=null)
	{
		trigger_error("Editing a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return $this;
	}

	/**
	 * Add ORDER BY statement to query statement.
	 *
	 * @param string $statement  ORDER BY statement
	 * @param int    $options    Addition options as binairy set
	 * @param mixed  $subset     Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function addOrderBy($statement, $options=0, $subset=null)
	{
		trigger_error("Editing a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return $this;
	}

	/**
	 * Set the limit for the number of rows returned when excecuted.
	 *
	 * @param int   $rowcount  Number of rows
	 * @param int   $offset    Start at row
	 * @param int   $options   Addition options as binairy set
	 * @param mixed $subset    Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function setLimit($rowcount, $offset=0, $options=0, $subset=null)
	{
		trigger_error("Editing a query statement is not implemented" . (isset($this->link) ? " for DB driver '" . get_class($this->link) . "'." : '.'), E_USER_WARNING);
		return $this;
	}
	
	
   	//------------- Finalize changes ------------------------
   	
	/**
     * Remove all the changes
     * 
	 * @return DB_Statement
     */
   	public function revert()
   	{
   		return $this;
   	}

	/**
	 * Commit all changes to base query
	 * 
	 * @return DB_Statement
	 */
	public function commit()
	{
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
		$new = new $class($this->link, $this->buildStatement());
		$new->baseTable = $this->basetable;
		
		return $new;
	}
	
	/**
	 * Clear cached statement
	 *
	 * @return DB_Statement
	 */
	public function refresh()
	{
		$this->emptyResult = null;
	    
		return $this;
	}
	

	//------------- Execute ------------------------
	
	/**
	 * Execute the query statement.
	 * 
	 * @param array $args  Arguments to be parsed into the query on placeholders
	 * @return DB_Result
	 */
	function execute($args=array())
	{
   	    if (!isset($this->link)) throw new Exception("Unable te execute statement: Statement object isn't linked to a database connection."); 

		// Parse arguments
		if (func_num_args() > 2) {
			$args = func_get_args();
			array_shift($args);
		}
   	    
		return $this->link->query($this, $args);
	}
	

	/**
     * Excecute query with filling placeholders with 0.
     * {@internal When extending try to make statement result an empty set.}}
     *
     * @return DB_Result
     */
   	protected function executeEmpty()
   	{
   	    if (!isset($this->link)) throw new Exception("Unable te execute statement: Statement object isn't linked to a database connection."); 
   	    
   		if (!isset($this->emptyResult)) $this->emptyResult = $this->execute(false);
   		return $this->emptyResult;
   	}
   	
	/**
	 * Return the position of a field, based on the fieldname
	 * 
	 * @param string $index
	 * @return int
	 */
   	public function getFieldIndex($index)
   	{
   		return $this->executeEmpty()->getFieldIndex($index);
   	}
   	
	/**
     * Returns the fieldnames for all columns
     *
     * @param int $format  DB::FIELDNAME_* constant
     * @return array
     */
   	public function getFieldNames($format=DB::FIELDNAME_COL)
   	{
   		return $this->executeEmpty()->getFieldNames($format);
   	}
   	   	
	/**
     * Execute the statement and return a specific field
     *
     * @param mixed $index  Fieldname or index
     * @return DB_Field
     */
   	public function getField($index)
   	{
   		return $this->executeEmpty()->getField($index);
   	}   	

	/**
     * Get a set of fields (DB_Field) based on the columns of the query statement
     *
     * @return array
     */
   	public function getFields()
   	{
   		return $this->executeEmpty()->getFields();
   	}

   	/**
     * Execute the statement and return the number of rows.
     * 
     * @param boolean $all  Don't limit
     * @return int
     */
   	public function countRows($all=false)
   	{
   	    if (!isset($this->link)) throw new Exception("Unable te execute statement: Statement object isn't linked to a database connection."); 
   	    return $this->excecute()->countRows($all);
   	}

   	
	/**
	 * Load a record using this statement.
	 * @internal This works, but please overwrite it for a method that isn't wasting as much.
	 *
	 * @param mixed $criteria    Value for column 0, array(column=>value)
	 * @param int   $resulttype  A DB::FETCH_% constant
	 * @return DB_Record
	 * 
	 * @throws Q\DB_Constraint_Exception when statement would return multiple records
	 */
	public function load($criteria=null, $resulttype=DB::FETCH_RECORD)
	{
		if (is_array($criteria)) list($column, $value) = each($criteria);
		 else {$column = 0; $value = $criteria;}
		
		$result = $this->execute();

		if (isset($criteria)) {
			$records = $result->seekRows($column, $value, $resulttype);
			
			if (!isset($records)) return null;
			if (sizeof($records) > 1) throw new DB_Constraint_Exception("Unable to load record: Statement with criteria '$column'='$value' returned multiple records.");
			return $records[0];
			
		} else {
			if ($result->countRows() > 1) throw new DB_Constraint_Exception("Unable to load record: Statement returned multiple records.");
			return $result->fetchRow($resulttype);
		}
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
	
	
	/**
	 * Cast statement object to string
	 *
	 * @return string
	 */
	public function __toString()
	{
	    return $this->getStatement();
	}
}

?>