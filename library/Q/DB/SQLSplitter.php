<?php
namespace Q;

/**
 * Break down a query statement to different parts, which can be altered and joined again.
 *
 * @package DB
 */
interface DB_SQLSplitter
{
	/**
	 * Quote a value so it can be savely used in a query.
	 * 
	 * @param mixed  $value
	 * @param string $empty  Return $empty if $value is null
	 * @return string
	 */
	public static function quote($value, $empty='NULL');
	
	/**
	 * Quotes a string so it can be safely used as a table or column name.
	 * 
	 * @param string $identifier
	 * @param int    $flags       Optional DB::QUOTE_STRICT
	 * @return string
	 */
	public static function quoteIdentifier($identifier, $flags=DB::QUOTE_LOOSE);
	
	/**
	 * Check if a identifier is valid as field name or table name.
	 *
	 * @param string  $name
	 * @param boolean $withtable  TRUE: group.name, FALSE: name, NULL: both
	 * @param boolean $withalias  Allow an alias (AS alias)
	 * @return boolean
	 */
	public static function validIdentifier($name, $withgroup=null, $withalias=false);

	/**
	 * Split a column name in table, column and alias OR table name in db, table and alias.
	 * Returns array(table, fieldname, alias) or array(db, table, alias)
	 *
	 * @param string $fieldname  Full fieldname
	 * @return array
	 */
	public static function splitIdentifier($name);
	
	/**
	 * Create a full fieldname OR create a full tablename
	 *
	 * @param string $group  Table name / DB name
	 * @param string $name   Field name / Table name
	 * @param string $alias
	 * @param int    $flags  DB::QUOTE_%
	 * @return boolean
	 */
	public static function makeIdentifier($group, $name, $alias=null, $flags=DB::QUOTE_LOOSE);
	
	/**
	 * Parse arguments into a statement on placeholders.
	 *
	 * @param mixed $statement  String or query object
	 * @param array $args       Arguments to parse into statement.
	 * @return string
	 */
	public static function parse($statement, $args);
	

	/**
	 * Get the type ofthe query statement.
	 *
	 * @param string $statement  Query statement
	 * @return string
	 */
	public static function getQueryType($statement);

	/**
	 * Convert a query statement to another type.
	 *
	 * @param string $statement  Query statement
	 * @param string $type       New query type
	 * @return string
	 */
	public static function convertStatement($statement, $type);	
	
	/**
	 * Split a query in different parts.
	 *
	 * @param string $statement  Query statement
	 * @return array
	 */
	public static function split($statement);
		
	/**
	 * Join parts and to create a query.
	 *
	 * @param array $parts
	 * @return string
	 */
	public static function join($parts);
	
	
	/**
	 * Extract subsets (subqueries) from main query and replace them with #subX.
	 * Returns array(main query, subquery1, [subquery2, ...])
	 *
	 * @param string $statement
	 * @return array
	 */
	public static function extractSubsets($statement);
	
	/**
	 * Inject extracted subsets back into main query.
	 *
	 * @param array $sets  array(main query, subquery1, [subquery2, ...]) or array(main parts, subparts1, ...)
	 */
	public static function injectSubsets($sets);

	
	/**
	 * Return the columns of a (partual) query.
	 * 
	 * @param string  $statement       Query statement or 'column, column, ...'
	 * @param boolean $splitFieldname  Split fieldname in array(table, field, alias)
	 * @param boolean $assoc           Remove '[AS] alias' (for SELECT) or 'to=' (for INSERT/UPDATE) and return as associated array
	 * @return array
	 */
	public static function splitColumns($statement, $splitFieldname=false, $assoc=false);
	

	/**
	 * Extract subqueries from sql query and split each subquery in different parts.
	 *
	 * @param   string $statement  Query statement
	 * @return  array
	 */
	public static function extractSplit($statement);

	/**
	 * Join parts and inject extracted subsets back into main sql query.
	 *
	 * @param  array $sets   array(main parts, parts subquery1 [, parts subquery2, ...])
	 * @return array
	 */
	public static function joinInject($parts);


	/**
	 * Build a WHERE statement.
	 * If $value == null and $compare == '=', $compare becomes 'IS NULL'.
	 * 
	 * NOTE: This function does not escape $column
	 * Returns array('where', 'having')
	 *
	 * @param mixed  $column   Column name or expression with placeholders, can also be an array of columns ($column[0]=$value OR $column[1]=$value)
	 * @param mixed  $value    Value or array of values ($column=$value[0] OR $column=$value[1])
	 * @param string $compare  Comparision operator oa. =, !=, >, <, >=, <=, LIKE, LIKE%, %LIKE%, REVERSE LIKE (value LIKE column), IN and BETWEEN
	 * @return array
	 */
	public static function buildWhere($column, $value, $compare="=");
}
