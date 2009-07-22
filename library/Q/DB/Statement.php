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
	 * @param DB|DB_Table $source
	 * @param string      $statement
	 */
	public function __construct($source, $statement);
	
	
	/**
     * Return the statement without any appended parts.
     *
	 * @param Passed arguments are parsed into prepared statement.
     * @return string
     */
   	public function getBaseStatement();

	/**
     * Return a subquery without any added or replaced parts
     *
	 * @param int $subset  Specify to wich subset the change applies (0=main query)
	 * @param Additional arguments are parsed into subquery statement.
     * @return string
     */
	public function getBaseSubset($subset);
	
	/**
	 * Return the query statement.
	 * 
	 * @param Passed arguments are parsed into prepared statement.
	 * @return string
	 */
	function getStatement();
	
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
	 * @param int    $options  Addition options as binairy set
	 * @param mixed  $subset   Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function addCriteria($column, $value, $compare="=", $options=0, $subset=null);

	/**
	 * Set the result of of the query statement.
	 *
	 * @param string $column   ORDER BY statement
	 * @param int    $options  Addition options as binairy set
	 * @param mixed  $subset   Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function setOrder($column, $options=0, $subset=null);

	/**
	 * Set the limit for the number of rows returned when excecuted.
	 *
	 * @param int   $rowcount  Number of rows
	 * @param int   $offset    Start at row
	 * @param int   $options   Addition options as binairy set
	 * @param mixed $subset    Specify to which subset (subquery, node) the change applies
	 * @return DB_Statement
	 */
	public function setLimit($rowcount, $offset=0, $options=0, $subset=null);
	
	
   	//------------- Finalize changes ------------------------
   	
	/**
     * Remove all the changes
     * 
	 * @return DB_Statement
     */
   	public function revert();

	/**
	 * Commit all changes to base query
	 * 
	 * @return DB_Statement
	 */
	public function commit();

	/**
	 * Build a new query statement committing all changes
	 * 
	 * @return DB_Statement
	 */
	public function commitToNew();
	
	/**
	 * Clear cached statement
	 *
	 * @return DB_Statement
	 */
	public function refresh();
	

	//------------- Execute ------------------------
	
	/**
	 * Execute the query statement.
	 * 
	 * @param array $args  Arguments to be parsed into the query on placeholders
	 * @return DB_Result
	 */
	public function execute($args=null);
	
	/**
     * Returns the fieldnames for all columns
     *
     * @param int $format  DB::FIELDNAME_* constant
     * @return array
     */
   	public function getFieldNames($format=DB::FIELDNAME_COL);
   	   	
	/**
     * Get a set of fields (DB_Field) based on the columns of the query statement
     *
     * @return array
     */
   	public function getFields();

	/**
     * Execute the statement and return a specific field
     *
     * @param mixed $index  Fieldname or index
     * @return DB_Field
     */
   	public function getField($index);

   	/**
     * Execute the statement and return the number of rows.
     * 
     * @param boolean $all  Don't limit
     * @return int
     */
   	public function countRows($all=false);

   	
	/**
	 * Load a record using this statement.
	 * {@internal This works, but please overwrite it for a method that isn't wasting as much.}}
	 *
	 * @param mixed $criteria    Value for column 0 or array(column=>value)
	 * @param int   $resulttype  A DB::FETCH_% constant
	 * @return DB_Record
	 * 
	 * @throws Q\DB_Constraint_Exception when statement would return multiple records
	 */
	public function load($criteria=null, $resulttype=DB::FETCH_RECORD);

	/**
	 * Create a new record using the fields of the result of this statement.
	 * 
	 * @return DB_Record
	 */
	public function newRecord();
	
	
	/**
	 * Cast statement object to string
	 *
	 * @return string
	 */
	public function __toString();
}

