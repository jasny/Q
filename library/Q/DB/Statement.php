<?php
namespace Q;

/**
 * Interface for any query statement object
 * 
 * @package DB
 */
interface DB_Statement
{
	/**
	 * Class constructor
	 *
	 * @param string $statement  Query statement
	 * @param mixed  $source     Q\DB, Q\DB_Table, Q\DB_Statement or driver name (string)
	 */
	public function __construct($statement, $source=null);
		
	/**
     * Return the statement without any added or replaced parts.
     *
     * @return DB_Statement
     */
   	public function getBaseStatement();

   	/**
	 * Cast statement object to string.
	 * 
	 * @param Passed arguments are parsed into prepared statement.
	 * @return string
	 */
	public function __toString();
   	
	/**
	 * Return the statement with parsed in arguments.
	 * 
	 * @param array $args  Arguments to parse on place holders
	 * @return string
	 */
	public function parse($args);
		
	/**
	 * Count the number of placeholders in the statement.
	 *
	 * @return int
	 */
	public function countPlaceholders();
	
	
   	//------------- Add/Set specific part ------------------------
	
	/**
	 * Add criteria as where or having statement as $column=$value.
	 * If $value == null and $compare == '=', $compare becomes 'IS NULL'.
	 * 
	 * NOTE: This function does not escape the column name.
	 *
	 * @param mixed  $column   Column name, column number or array of column names ($column[0]=$value OR $column[1]=$value)
	 * @param mixed  $value    Value or array of values ($column=$value[0] OR $column=$value[1])
	 * @param string $compare  Comparision operator: =, !=, >, <, =>, <=, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN, NOT IN, ALL and BETWEEN
	 * @param int    $flags    Addition options as binairy set
	 * @param mixed  $subset   Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function addCriteria($column, $value, $compare="=", $flags=0, $subset=null);

	/**
	 * Set the result of of the query statement.
	 *
	 * @param string $column  ORDER BY statement
	 * @param int    $flags   Addition options as binairy set
	 * @param mixed  $subset  Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function orderBy($column, $flags=0, $subset=null);

	/**
	 * Set the limit for the number of rows returned when excecuted.
	 *
	 * @param int   $rowcount  Number of rows
	 * @param int   $offset    Start at row
	 * @param int   $flags     Addition options as binairy set
	 * @param mixed $subset    Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function limit($rowcount, $offset=0, $flags=0, $subset=null);
	
	
   	//------------- Finalize changes ------------------------
   	
	/**
     * Remove all the changes.
     * 
	 * @return DB_Statement
     */
   	public function revert();

	/**
	 * Build a new query statement committing all changes.
	 * 
	 * @return DB_Statement
	 */
	public function commit();
	

	//------------- Execute ------------------------
	
	/**
	 * Execute the query statement.
	 * 
	 * @param array $args  Arguments to be parsed into the query on placeholders
	 * @return DB_Result
	 */
	public function execute($args=null);
	
	/**
     * Get a set of fields based on the columns of the query statement.
     *
     * @return DB_FieldList
     */
   	public function getFields();

   	/**
     * Execute the statement and return the number of rows.
     * 
     * @param boolean $all  Don't limit
     * @return int
     */
   	public function countRows($all=false);
   	
	/**
	 * Create a new record using the fields of the result of this statement.
	 * 
	 * @return DB_Record
	 */
	public function newRecord();
}

