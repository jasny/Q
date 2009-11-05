<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/DB.php';
require_once 'Q/DB/SQLSplitter.php';

/**
 * Break down a mysql query statement to different parts, which can be altered and joined again.
 * Supported types: SELECT, INSERT, REPLACE, UPDATE, DELETE, TRUNCATE.
 *
 * SELECT ... UNION syntax is *not* supported.
 * DELETE ... USING syntax is *not* supported.
 * Invalid query statements might give unexpected results. 
 * 
 * All methods of this class are static.
 * 
 * @package    DB
 * @subpackage DB_MySQL
 *   
 * {@internal
 *   This class highly depends on complicated PCRE regular expressions. So if your not really really really good at reading/writing these, don't touch this class.
 *   To prevent a regex getting in some crazy (or catastrophic) backtracking loop, use regexbuddy (http://www.regexbuddy.com) or some other step-by-step regex debugger.
 *   The performance of each function is really important, since these functions will be called a lot in 1 page and should be concidered abstraction overhead. The focus is on performance not readability of the code.
 * 
 *   Expression REGEX_VALUES matches all quoted strings, all backquoted identifiers and all words and all non-word chars upto the next keyword.
 *   It uses atomic groups to look for the next keyword after each quoted string and complete word, not after each char. Atomic groups are also neccesary to prevent catastrophic backtracking when the regex should fail.
 * 
 *   Expressions like '/\w+\s*(abc)?\s*\w+z/' should be prevented. If this regex would try to match "ef    ghi", the regex will first take all 3 spaces for the first \s*. When the regex fails it retries taking the
 *     first 2 spaces for the first \s* and the 3rd space for the second \s*, etc, etc. This causes the matching to take more than 3 times as long as '/\w+\s*(abc\s*)?\w+z/' would.
 *   This is the reason why trailing spaces are included with REGEX_VALUES and not automaticly trimmed.
 * }}
 * 
 * @todo It might be possible to use recursion instead of extracting subqueries, using \((SELECT\b)(?R)\). For query other that select, I should do (?:^\s++UPDATE ...|(?<!^)\s++SELECT ...) to match SELECT and not UPDATE statement in recursion.
 * @todo Implement splitValues to get values of INSERT INTO ... VALUES ... statement
 */
class DB_MySQL_SQLSplitter implements DB_SQLSplitter
{
	const REGEX_VALUES = '(?:\w++|`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|\s++|[^`"\'\w\s])*?';
	const REGEX_IDENTIFIER = '(?:(?:\w++|`[^`]*+`)(?:\.(?:\w++|`[^`]*+`)){0,2})';
	const REGEX_QUOTED = '(?:`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\')';
	
	//------------- Basics -----------------------
	
	/**
	 * Quote a value so it can be savely used in a query.
	 * 
	 * @param mixed  $value
	 * @param string $empty  Return $empty if $value is null
	 * @return string
	 */
	public static function quote($value, $empty='NULL')
	{
		if (is_null($value)) return $empty;
		if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
		if (is_int($value) || is_float($value)) return (string)$value;
		
		if (is_array($value)) {
			foreach ($value as &$v) $v = self::quote($v, $empty);
			return join(', ', $value);
		}
		
		return '"' . strtr($value, array('\\'=>'\\\\', "\0"=>'\\0', "\r"=>'\\r', "\n"=>'\\n', '"'=>'\\"')) . '"';
	}
	
	/**
	 * Quotes a string so it can be used as a table or column name.
	 * Dots are seen as seperator and are kept out of quotes.
	 * 
	 * Will not quote expressions without DB::QUOTE_STRICT. This means it is not secure without this option. 
	 * 
	 * @param string $identifier
	 * @param int    $flags       DB::QUOTE_%
	 * @return string
	 */
	public static function quoteIdentifier($identifier, $flags=DB::QUOTE_LOOSE)
	{
		// None
		if ($flags & DB::QUOTE_NONE) return $identifier;
		
		// Strict
		if ($flags & DB::QUOTE_STRICT) {
			if (strpos($identifier, '.') === false && strpos($identifier, '`') === false) return "`$identifier`";
			
			$quoted = preg_replace('/(`?)(.+?)\1(\.|$)/', '`\2`\3', trim($identifier));
			if (!preg_match('/^(?:`[^`]*`(\.|$))+$/', $quoted)) throw new SecurityException("Unable to quote '$identifier' safely");
			return $quoted;
		}
		
		// Loose
		$quoted = preg_replace_callback('/' . self::REGEX_QUOTED . '|\b(?:NULL|TRUE|FALSE|DEFAULT|DIV|AND|OR|XOR|IN|IS|BETWEEN|R?LIKE|REGEXP|SOUNDS\s+LIKE|MATCH|AS|CASE|WHEN|ASC|DESC|BINARY)\b|\bCOLLATE\s+\w++|\bUSING\s+\w++|(\d*[a-z_][^\s`\(\)\.&~|^<=>+\-\/*%!]*\b)(?!\s*\()/i', function($match) {return !empty($match[1]) ? "`{$match[1]}`" : $match[0];}, $identifier);
		if (preg_match('/CAST\s*\(/i', $quoted)) $quoted = self::quoteIdentifier_castCleanup($quoted);
		return $quoted;
	}
    
	/**
	 * Unquote up quoted types of CAST function.
	 * 
	 * @param string|array $match  Match or identifier
	 * @return string  
	 * @ignore
	 */
	protected static function quoteIdentifier_castCleanup($match)
	{
		if (is_array($match) && !isset($match[2])) return $match[0];
		if (!is_array($match)) $match = array(2=>$match);
		
		$match[2] = preg_replace_callback('/((?:' . self::REGEX_QUOTED . '|[^()`"\']++)*)(?:\(((?R)*)\))?/i', array(__CLASS__, 'quoteIdentifier_castCleanup'), $match[2]);
		if (!empty($match[1]) && preg_match('/\CAST\s*$/i', $match[1])) $match[2] = preg_replace('/(\bAS\b\s*)`([^`]++)`(\s*)$/i', '\1\2\3', $match[2]);
		
		return isset($match[0]) ? "{$match[1]}({$match[2]})" : $match[2]; 
	}
	
	/**
	 * Check if a identifier is valid as field name or table name
	 *
	 * @param string  $name
	 * @param boolean $withtable  TRUE: group.name, FALSE: name, NULL: both
	 * @param boolean $withalias  Allow an alias (AS alias)
	 * @return boolean
	 */
	public static function validIdentifier($name, $withgroup=null, $withalias=false)
	{	
		return (bool)preg_match('/^' . ($withgroup !== false ? '((?:`([^`]*)`|(\d*[a-z_]\w*))\.)' . ($withgroup ? '+' : '*') : '') . '(`([^`]*)`|(\d*[a-z_]\w*))' . ($withalias ? '(?:\s*(?:\bAS\b\s*)?(`([^`]*)`|(\d*[a-z_]\w*)))?' : '') . '$/i', trim($name));
	}
    
	/**
	 * Split a column name in table, column and alias OR table name in db, table and alias.
	 * Returns array(table, field, alias) / array(db, table, alias)
	 *
	 * @param string $name  Full field/table name
	 * @return array
	 */
	public static function splitIdentifier($name)
	{
		$matches = null;
		if (preg_match('/^(?:((?:`(?:[^`]*)`|(?:\d*[a-z_]\w*))(?:\.(?:`(?:[^`]*)`|(?:\d*[a-z_]\w*)))*)\.)?(`(?:[^`]*)`|(?:\d*[a-z_]\w*))(?:(?:\s*\bAS\b)?\s*(`(?:[^`]*)`|(?:\d*[a-z_]\w*)))?$/i', trim($name), $matches)) return array(str_replace('`', '', $matches[1]), trim($matches[2], '`'), isset($matches[3]) ? trim($matches[3], '`') : null);
		return array(null, trim($name, '`'), null);
	}
	
	/**
	 * Create a full field name OR create a full table name.
	 *
	 * @param string $group  Table name / DB name
	 * @param string $name   Field name / Table name
	 * @param string $alias
	 * @param int    $flags  DB::QUOTE_%
	 * @return boolean
	 */
	public static function makeIdentifier($group, $name, $alias=null, $flags=DB::QUOTE_LOOSE)
	{
		return (!empty($group) && ($flags & DB::QUOTE_STRICT || self::validIdentifier($name, false, true)) ? self::quoteIdentifier($group, $flags | DB::QUOTE_STRICT) . '.' : '') . self::quoteIdentifier($name, $flags) . (!empty($alias) ? ' AS ' . self::quoteIdentifier($alias, $flags | DB::QUOTE_STRICT) : '');
	}
	
	/**
	 * Parse arguments into a statement.
	 *
	 * @param mixed $statement  Query string or DB::Statement object
	 * @param array $args       Arguments to parse into statement on placeholders
	 * @return mixed
	 */
	public static function parse($statement, $args)
	{
		if (!isset($args)) {
			$fn = function($match) {return !empty($match[1]) || !empty($match[3]) ? '(NULL)' : $match[0];};
		} else {
			if (!is_array($args)) $args = array($args);
			  else ksort($args);
			$fn = function($match) use(&$args) {return !empty($match[3]) && array_key_exists($match[3], $args) ? ($match[2] === ':!' ? (isset($args[":{$match[3]}"]) ? $args[":{$match[3]}"] : $args[$match[3]]) : DB_MySQL_SQLSplitter::quote(isset($args[":{$match[3]}"]) ? $args[":{$match[3]}"] : $args[$match[3]])) : (!empty($match[1]) ? ($match[1] === '?!' ? array_shift($args) : DB_MySQL_SQLSplitter::quote(array_shift($args))) : $match[0]);};
		}
		
		$parsed = preg_replace_callback('/`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|(\?\!?)|(:\!?)(\w++)/', $fn, $statement);
		if (!($statement instanceof DB_Statement)) return $parsed;
		
	    $class = get_class($statement);
	    return new $class($statement, $parsed);
	}
	
	/**
	 * Count the number of (unnamed) placeholders in a statement.
	 *
	 * @param string $statement
	 * @return int
	 */
	public static function countPlaceholders($statement)
	{
		if (strpos($statement, '?') === false) return 0;
		
		$matches = null;
		preg_match_all('/`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|(\?)/', $statement, $matches, PREG_PATTERN_ORDER);
		return count(array_filter($matches[1]));
	}
		
	
	//------------- Split / Build query -----------------------

	/**
	 * Return the type of the query.
	 *
	 * @param string $sql  SQL query statement (or an array with parts)
	 * @return string
	 */
	public static function getQueryType($sql)
	{
		$matches = null;
		if (is_array($sql)) $sql = $sql[0];
		if (!preg_match('/^\s*(SELECT|INSERT|REPLACE|UPDATE|DELETE|TRUNCATE|CALL|DO|HANDLER|LOAD\s+(?:DATA|XML)\s+INFILE|(?:ALTER|CREATE|DROP|RENAME)\s+(?:DATABASE|TABLE|VIEW|FUNCTION|PROCEDURE|TRIGGER|INDEX)|PREPARE|EXECUTE|DEALLOCATE\s+PREPARE|DESCRIBE|EXPLAIN|HELP|USE|LOCK\s+TABLES|UNLOCK\s+TABLES|SET|SHOW|START\s+TRANSACTION|BEGIN|COMMIT|ROLLBACK|SAVEPOINT|RELEASE SAVEPOINT|CACHE\s+INDEX|FLUSH|KILL|LOAD|RESET|PURGE\s+BINARY\s+LOGS|START\s+SLAVE|STOP\s+SLAVE)\b/si', $sql, $matches)) return null;
		
		$type = strtoupper(preg_replace('/\s++/', ' ', $matches[1]));
		if ($type === 'BEGIN') $type = 'START TRANSACTION';
		
		return $type;
	}

	/**
	 * Split a query in different parts.
	 * If a part is not set whitin the SQL query, the part is an empty string.
	 *
	 * @param string $sql  SQL query statement
	 * @return array
	 */
	public static function split($sql)
	{
		$type = self::getQueryType($sql);
		switch ($type) {
			case 'SELECT':	 return self::splitSelectQuery($sql);
			case 'INSERT':
			case 'REPLACE':	 return self::splitInsertQuery($sql);
			case 'UPDATE':	 return self::splitUpdateQuery($sql);
			case 'DELETE':   return self::splitDeleteQuery($sql);
			case 'TRUNCATE': return self::splitTruncateQuery($sql);
			case 'SET':      return self::splitSetQuery($sql);
		}
		
		throw new Exception("Unable to split " . (!empty($type) ? "$type " : "") . "query. $sql");
	}

	/**
	 * Join parts to create a query.
	 * The parts are joined in the order in which they appear in the array.
	 * 
	 * CAUTION: The parts are joined blindly (no validation), so shit in shit out
	 *
	 * @param array $parts
	 * @return string
	 */
	public static function join($parts)
	{
		$sql_parts = array();
		
		foreach ($parts as $key=>&$part) {
			if (!empty($part)) {
				if (is_array($part)) $part = join(", ", $part);
				$sql_parts[] .= (is_int($key) || $key === 'columns' || $key === 'query' || $key === 'tables' ? '' : strtoupper($key) . " ") . $part;
			}
		}

		return join(' ', $sql_parts);
	}

	/**
	 * Convert a query statement to another type.
	 *
	 * @todo Currently only works for SELECT to DELETE and vise versa
	 * 
	 * @param string $sql   SQL query statement (or an array with parts)
	 * @param string $type  New query type
	 * @return string
	 */
	public static function convertStatement($sql, $type)
	{
		$type = strtoupper($type);
		
	}
		
	
	//------------- Extract subsets --------------------
	
	
	/**
	 * Extract subqueries from sql query (on for SELECT queries) and replace them with #subX in the main query.
	 * Returns array(main query, subquery1, [subquery2, ...])
	 *
	 * @param  string $sql
	 * @param  array  $sets  Do not use!
	 * @return array
	 */
	public static function extractSubsets($sql, &$sets=null)
	{
		$ret_offset = isset($sets);
		$sets = (array)$sets;
		
		// There are certainly no subqueries
		if (stripos($sql, 'SELECT', 6) === false) {
			$offset = array_push($sets, $sql) - 1;
			return $ret_offset ? $offset : $sets;
		}

		// Extract any subqueries
		$offset = array_push($sets, null) - 1;
		
		if (self::getQueryType($sql) === 'INSERT') {
			$parts = self::split($sql);
			if (isset($parts['query'])) {
				self::extractSubsets($parts['query'], $sets);
				$parts['query'] = '#sub' . ($offset+1);
				$sql = self::join($parts);
			}
		}
		
		if (preg_match('/\(\s*SELECT\b/si', $sql)) {
			do {
				$matches = null;
				preg_match('/(?:`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|\((\s*SELECT\b.*\).*)|\w++|[^`"\'\w])*$/si', $sql, $matches, PREG_OFFSET_CAPTURE);
				if (isset($matches[1])) $sql = substr($sql, 0, $matches[1][1]) . preg_replace_callback('/(?:`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|([^`"\'()]+)|\((?R)\))*/si', function($match) use(&$sets) {return '#sub' . DB_MySQL_SQLSplitter::extractSubsets($match[0], $sets);}, substr($sql, $matches[1][1]), 1);
			} while (isset($matches[1]));
		}
		
		$sets[$offset] = $sql;
		return $ret_offset ? $offset : $sets;
	}
	
	/**
	 * Inject extracted subsets back into main sql query.
	 *
	 * @param array $sets   array(main query, subquery1, [subquery2, ...]) or array(main parts, subparts, ...)
	 * @return string|array
	 */
	public static function injectSubsets($sets)
	{
		if (count($sets) == 1) return $sets[0];
		
		$main = $sets[0];
		unset($sets[0]);
		
		if (is_array($main)) {
			$ret =& $main;
		} else {
			$main[0] = $main;
			$ret =& $main[0];
		}
		
		foreach ($sets as &$set) {
			if (is_array($set)) $set = self::join($set);
		}
		
		$fn = function($match) use(&$sets) {return $match[1] . $sets[$match[2]];};
		$count = -1;
		while ($count) $main = preg_replace_callback('/^(' . self::REGEX_VALUES . ')\#sub(\d+)/', $fn, $main, 0xFFFF, $count);
		
		return $ret;
	}
	
	
	/**
	 * Extract childqueries for tree data from sql query (only for SELECT queries) and replace them with NULL in the main query.
	 * Returns array(main query, array(subquery1, parent field, child field), [array(subquery2, parent field, child field), ...])
	 *
	 * @param string $sql
	 * @return array
	 */
	public static function extractTree($sql)
	{
		// There are certainly no childqueries
		if (!preg_match('/^SELECT\b/i', $sql) || !preg_match('/\b(?:VALUES|ROWS)\s*\(\s*SELECT\b/i', $sql)) return array($sql);
		if (!preg_match('/^(' . self::REGEX_VALUES . ')(?:\b(?:VALUES|ROWS)\s*(\(\s*SELECT\b.*))$/si', $sql)) return array($sql);
		
		// Extract any childqueries
		$parts = self::splitSelectQuery($sql);
		$columns = self::splitColumns($parts['columns']);

		$tree = null;
		$matches = null;
		
		foreach ($columns as $i=>$column) {
			if (preg_match('/^(?:VALUES|(ROWS))\s*+\((SELECT\b\s*+' . self::REGEX_VALUES . ')(?:\bCASCADE\s++ON\b\s*+(' . self::REGEX_IDENTIFIER . ')\s*+\=\s*+(' . self::REGEX_IDENTIFIER . '))?\s*+\)\s*+(?:AS\b\s*+(' . self::REGEX_IDENTIFIER . '))?$/si', trim($column), $matches)) {
				if (!isset($tree)) $tree = array(null);
				
				if (!empty($matches[3]) && !empty($matches[4])) {
					$alias = !empty($matches[5]) ? $matches[5] : `tree:col$i`;
					$columns[$i] = $matches[4] .  " AS $alias";
					
					$child_parts = self::splitSelectQuery($matches[2]);
					$child_parts['columns'] .= ", " . $matches[3] . " AS `tree:join`";
					$child_parts['where'] = (!empty($child_parts['where']) ? '(' . $child_parts['where'] . ') AND ' : '') . $matches[3] . " IN (?)";
					$child_parts['order by'] = $matches[3] . (!empty($child_parts['order by']) ? ", " . $child_parts['order by'] : '');
					$tree[] = array(unquote($alias, '`'), self::join($child_parts), $matches[1] ? DB::FETCH_ORDERED : DB::FETCH_VALUE, true);
				} else {
					$columns[$i] = 'NULL' . (!empty($matches[5]) ? ' AS ' . $matches[5] : '');
					trigger_error("Incorrect tree query statement: Child query should end with 'CASCADE ON `parent_field` = `child_field`'. " . $column, E_USER_WARNING);
				}
			}
		}
		
		if (!isset($tree)) return array($sql);

		$parts['columns'] = join(', ', $columns);
		$tree[0] = self::join($parts);
		
		return $tree;
	}	
	
    /**
     * Extract subqueries from sql query and split each subquery in different parts.
     *
     * @param string $statement  Query statement
     * @return array
     */
    public static function extractSplit($sql)
    {
        $sets = self::extractSubsets($sql);
        if (!isset($sets)) return null;

        return array_map(array(__CLASS__, 'split'), $sets);
    }

    /**
     * Join parts and inject extracted subsets back into main sql query.
     *
     * @param array $sets  array(main parts, parts subquery1 [, parts subquery2, ...])
     * @return array
     */
    public static function joinInject($parts)
    {
        $sets = array_map(array(__CLASS__, 'join'), $parts);
        return count($sets) == 1 ? reset($sets) : self::injectSubsets($sets);
    }    

    
	//------------- Split specific type --------------------
	
	/**
	 * Split select query in different parts.
	 * NOTE: Splitting a query with a subquery is considerably slower.
	 *
	 * @param string $sql  SQL SELECT query statement
	 * @return array
	 */
	protected static function splitSelectQuery($sql)
	{
		if (preg_match('/\(\s*SELECT\b/i', $sql)) {
			$sets = self::extractSubsets($sql);
			$sql = $sets[0];
		}

		$parts = null;
		if (!preg_match('/^\s*' .
		  '(SELECT\b(?:\s+(?:ALL|DISTINCT|DISTINCTROW|HIGH_PRIORITY|STRAIGHT_JOIN|SQL_SMALL_RESULT|SQL_BIG_RESULT|SQL_BUFFER_RESULT|SQL_CACHE|SQL_NO_CACHE|SQL_CALC_FOUND_ROWS)\b)*)\s*(' . self::REGEX_VALUES . ')' .
		  '(?:' .
		  '\bFROM\b\s*(' . self::REGEX_VALUES . ')' .
		  '(?:\bWHERE\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:\bGROUP\s+BY\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:\bHAVING\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:\bORDER\s+BY\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:\bLIMIT\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(\b(?:PROCEDURE|INTO|FOR\s+UPDATE|LOCK\s+IN\s+SHARE\s*MODE|CASCADE\s*ON)\b.*?)?' .
		  ')?' .
		  '(?:;|$)/si', $sql, $parts)) throw new Exception('Unable to split SELECT query, invalid syntax:\n' . $sql);

		
		array_shift($parts);
		$parts = array_combine(array(0, 'columns', 'from', 'where', 'group by', 'having', 'order by', 'limit', 100), $parts + array_fill(0, 9, ''));
		if (!isset($sets) || count($sets) == 1) return $parts;
		
		$sets[0] =& $parts;
		return self::injectSubsets($sets);
	}

	/**
	 * Split insert/replace query in different parts.
	 *
	 * @param string $sql  SQL INSERT query statement
	 * @return array
	 */
	protected static function splitInsertQuery($sql)
	{
		$parts = null;
		if (preg_match('/\bVALUES\b/i', $sql) && preg_match('/^\s*' .
		  '((?:INSERT|REPLACE)\b(?:\s+(?:LOW_PRIORITY|DELAYED|HIGH_PRIORITY|IGNORE)\b)*)\s+INTO\b\s*(' . self::REGEX_VALUES . ')' .
		  '(\(\s*' . self::REGEX_VALUES . '\)\s*)?' .
		  '\bVALUES\s*(\(\s*' . self::REGEX_VALUES . '\)\s*(?:,\s*\(' . self::REGEX_VALUES . '\)\s*)*)' .
		  '(?:\bON\s+DUPLICATE\s+KEY\s+UPDATE\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:;|$)/si', $sql, $parts))
		{
			$keys = array(0, 'into', 'columns', 'values', 'on duplicate key update');
		}
		
		elseif (preg_match('/\bSET\b/i', $sql) && preg_match('/^\s*' .
		  '((?:INSERT|REPLACE)\b(?:\s+(?:LOW_PRIORITY|DELAYED|HIGH_PRIORITY|IGNORE)\b)*)\s+INTO\b\s*(' . self::REGEX_VALUES . ')' .
		  '\bSET\b\s*(' . self::REGEX_VALUES . ')' .
		  '(?:\bON\s+DUPLICATE\s+KEY\s+UPDATE\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:;|$)/si', $sql, $parts))
		{
		 	$keys = array(0, 'into', 'set', 'on duplicate key update');
		}

		elseif (preg_match('/\bSELECT\b|\#sub\d+/i', $sql) && preg_match('/^\s*' .
		  '((?:INSERT|REPLACE)\b(?:\s+(?:LOW_PRIORITY|DELAYED|HIGH_PRIORITY|IGNORE)\b)*)\s+INTO\b\s*(' . self::REGEX_VALUES . ')' .
		  '(\(\s*' . self::REGEX_VALUES . '\)\s*)?' .
		  '(\bSELECT\b\s*' . self::REGEX_VALUES . '|\#sub\d+\s*)' .
		  '(?:\bON\s+DUPLICATE\s+KEY\s+UPDATE\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:;|$)/si', $sql, $parts))
		{
			$keys = array(0, 'into', 'columns', 'query', 'on duplicate key update');
		}

		else 
		{
		 	throw new Exception("Unable to split INSERT/REPLACE query, invalid syntax:\n" . $sql);
		}
		
		array_shift($parts);
		return array_combine($keys, $parts + array_fill(0, sizeof($keys), ''));
	}

	/**
	 * Split update query in different parts
	 *
	 * @param string $sql  SQL UPDATE query statement
	 * @return array
	 */
	protected static function splitUpdateQuery($sql)
	{
		if (preg_match('/\(\s*SELECT\b/i', $sql)) {
			$sets = self::extractSubsets($sql);
			$sql = $sets[0];
		}
		
		$parts = null;
		if (!preg_match('/^\s*' .
		  '(UPDATE\b(?:\s+(?:LOW_PRIORITY|DELAYED|HIGH_PRIORITY|IGNORE)\b)*)\s*(' . self::REGEX_VALUES . ')' .
		  '\bSET\b\s*(' . self::REGEX_VALUES . ')' .
		  '(?:\bWHERE\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:\bLIMIT\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:;|$)/si', $sql, $parts)) throw new Exception("Unable to split UPDATE query, invalid syntax:\n" . $sql);

		array_shift($parts);
		$parts = array_combine(array(0, 'tables', 'set', 'where', 'limit'), $parts + array_fill(0, 5, ''));
		if (!isset($sets)) return $parts;
		
		$sets[0] =& $parts;
		return self::injectSubsets($sets);
	}

	/**
	 * Split delete query in different parts
	 *
	 * @param string $sql  SQL DELETE query statement
	 * @return array
	 */
	protected static function splitDeleteQuery($sql)
	{
		if (preg_match('/\(\s*SELECT\b/i', $sql)) {
			$sets = self::extractSubsets($sql);
			$sql = $sets[0];
		}
		
		$parts = null;
		if (!preg_match('/^\s*' .
		  '(DELETE\b(?:\s+(?:LOW_PRIORITY|QUICK|IGNORE)\b)*)\s*' .
		  '(' . self::REGEX_VALUES . ')?' .
		  '\bFROM\b\s*(' . self::REGEX_VALUES . ')?' .
		  '(?:\bWHERE\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:\bORDER\s+BY\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:\bLIMIT\b\s*(' . self::REGEX_VALUES . '))?' .
		  '(?:;|$)/si', $sql, $parts)) throw new Exception("Unable to split DELETE query, invalid syntax:\n" . $sql);

		array_shift($parts);
		$parts = array_combine(array(0, 'columns', 'from', 'where', 'order by', 'limit'), $parts + array_fill(0 , 6, ''));
		if (!isset($sets)) return $parts;
		
		$sets[0] =& $parts;
		return self::injectSubsets($sets);
	}
	
	/**
	 * Split delete query in different parts
	 *
	 * @param string $sql  SQL DELETE query statement
	 * @return array
	 */
	protected static function splitTruncateQuery($sql)
	{
		$parts = null;
		if (!preg_match('/^\s*' .
		  '(TRUNCATE\b(?:\s+TABLE\b)?)\s*' .
		  '(' . self::REGEX_VALUES . ')' .
		  '(?:;|$)/si', $sql, $parts)) throw new Exception("Unable to split TRUNCATE query, invalid syntax: $sql");

		array_shift($parts);
		return array_combine(array(0, 'tables'), $parts);
	}
	
	/**
	 * Split set query in different parts
	 *
	 * @param string $sql  SQL SET query statement
	 * @return array
	 */
	protected static function splitSetQuery($sql)
	{
		$parts = null;
		if (!preg_match('/^\s*' .
		  'SET\b\s*' .
		  '(' . self::REGEX_VALUES . ')' .
		  '(?:;|$)/si', $sql, $parts)) throw new Exception("Unable to split SET query, invalid syntax: $sql");

		array_shift($parts);
		return array_combine(array('set'), $parts);
	}
	
	
	//------------- Split columns --------------------
	
	/**
	 * Return the columns of a (partual) query.
	 * 
	 * @param string  $sql    SQL query or 'column, column, ...'
	 * @param int     $flags  DB::SPLIT_% option
	 * @return array
	 */
	public static function splitColumns($sql, $flags=0)
	{
		$type = self::getQueryType($sql);
		
		if ($type) {
			$parts = self::split($sql);
			
			if (isset($parts['columns'])) $sql = preg_replace('/^\s*\((.*)\)\s*$/', '\1', $parts['columns']);
			  elseif (isset($parts['set'])) $sql = $parts['set'];
			  else throw new Exception("It's not possible to extract columns of a $type query. $sql");
		}
		
		// Simple split on comma
		if ($flags & (DB::SPLIT_IDENTIFIER | DB::SPLIT_ASSOC) == 0) {
			preg_match_all('/(?:`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|\((?:[^()]++|(?R))*\)|[^`"\'(),]++)++/', $sql, $matches, PREG_PATTERN_ORDER);
			return $matches[0];
		}
		
		// Extract (tablename+fieldname or expression) and alias
		$matches = null;
		
		if (!isset($parts['set'])) {
			preg_match_all('/\s*(?P<fullname>' .
			  '(?:(?P<table>(?:(?:`[^`]*+`|\w++)\.)*(?:`[^`]*+`|\w++))\.)?(?P<field>`[^`]*+`|\w++)\s*+|' .
			  '(?:`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'|\((?:[^()]++|(?R))*\)|\s++|\w++(?<!\bAS)|[^`"\'\w\s(),])+' .
			  ')(?:(?:\bAS\s*)?(?P<alias>`[^`]*+`|\b\w++)\s*+)?(?=,|$|\))' .
			  '/si', $sql, $matches, PREG_PATTERN_ORDER);
		} else {
			preg_match_all('/\s*((?P<alias>(?:(?:`[^`]*+`|\w++)\.)*(?:`[^`]*+`|\w++)|@+\w++)\s*+=\s*+)?' .
			  '(?P<fullname>' . 
			  '(?:(?P<table>(?:(?:`[^`]*+`|\w++)\.)*(?:`[^`]*+`|\w++))\.)?(?P<field>`[^`]*+`|\w++)\s*+|' .
			  '(?:`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'|\((?:[^()]++|(?R))*\)|\s++|\w++|[^`"\'\w\s(),])+' .
			  ')(?=,|$|\))' .
			  '/si', $sql, $matches, PREG_PATTERN_ORDER);
		}

		if ($flags & DB::SPLIT_ASSOC) {
    		$alias = array();
            for ($i=0; $i<sizeof($matches[0]); $i++) $alias[$i] = !empty($matches['alias'][$i]) ? str_replace('`', '', trim($matches['alias'][$i])) : (!empty($matches['field'][$i]) ? str_replace('`', '', trim($matches['field'][$i])) : trim($matches['fullname'][$i]));
		}
		
		if (~$flags & DB::SPLIT_IDENTIFIER) return array_combine($alias, array_map('trim', $matches['fullname']));
		
	 	$values = array();
		for ($i=0; $i<sizeof($matches[0]); $i++) $values[$i] = array(!empty($matches['table'][$i]) ? str_replace('`', '', trim($matches['table'][$i])) : null, !empty($matches['field'][$i]) ? str_replace('`', '', trim($matches['field'][$i])) : trim($matches['fullname'][$i]), !empty($matches['alias'][$i]) ? str_replace('`', '', trim($matches['alias'][$i])) : null);
		
		return isset($alias) ? array_combine($alias, $values) : $values;
    }

	/**
	 * Return tables from a query or join expression.
	 *
	 * @param string  $sql             SQL query or FROM part
	 * @param boolean $splitTablename  Split tablename in array(db, table, alias)
	 * @param boolean $assoc           Remove 'AS ...' and return as associated array
	 * @return array
	 * 
	 * @todo Make splitTables work for subqueries
	 */
	public static function splitTables($sql, $splitTablename=false, $assoc=false)
	{
	    $type = self::getQueryType($sql);
        if ($type) {
	        $parts = self::split($sql);
	        if (array_key_exists('from', $parts)) $sql = $parts['from'];
	          elseif (array_key_exists('tables', $parts)) $sql = $parts['tables'];
	          else throw new Exception("Unable to get tables from $type query. $sql");
        }
        
        if (empty($sql)) return null;
        
	    $matches = null;
		preg_match_all('/(?:,\s*|(?:(?:NATURAL\s+)?(?:(?:LEFT|RIGHT)\s+)?(?:(?:INNER|CROSS|OUTER)\s+)?(?:STRAIGHT_)?JOIN\s*+))?+' .
		  '(?P<table>\(\s*+(?:[^()]++|(?R))*\)\s*+|(?P<fullname>(?:(?P<db>`[^`]++`|\w++)\.)?(?P<name>`[^`]++`|\b\w++)\s*+)(?:(?P<alias>\bAS\s*+(?:`[^`]++`|\b\w++)|`[^`]++`|\b\w++(?<!\bON)(?<!\bNATURAL)(?<!\bLEFT)(?<!\bRIGHT)(?<!\bINNER)(?<!\bCROSS)(?<!\bOUTER)(?<!\bSTRAIGHT_JOIN)(?<!\bJOIN))\s*+)?)' .
		  '(?:ON\b\s*+((?:(?:`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*"|\'(?:[^\'\\\\]++|\\\\.)*\'|\s++|\w++(?<!\bNATURAL)(?<!\bLEFT)(?<!\bRIGHT)(?<!\bINNER)(?<!\bCROSS)(?<!\bOUTER)(?<!\bSTRAIGHT_JOIN)(?<!\bJOIN)|[^`"\'\w\s()\,]|\\(\s*+(?:[^()]++|(?R))*\\)))+))?' .
		  '/si', $sql, $matches, PREG_PATTERN_ORDER);

        if (empty($matches)) return array();

        $subtables = array();
        for ($i=0,$m=count($matches[0]); $i<$m; $i++) {
            if ($matches['table'][$i][0] === '(') {
                $subtables = array_merge($subtables, self::splitTables(substr(trim($matches['table'][$i]), 1, -1), $splitTablename, $assoc));
                unset($matches[0][$i], $matches['table'][$i], $matches['fullname'][$i], $matches['db'][$i], $matches['name'][$i], $matches['alias'][$i]);
            }
        }
        
        if ($assoc) {
            $alias = array();
            foreach ($matches[0] as $i=>&$v) $alias[$i] = !empty($matches['alias'][$i]) ? preg_replace('/^(?:AS\s*)?\b(`?)(.*?)\\$1\s*$/', '$2', $matches['alias'][$i]) : trim($matches['name'][$i], ' `');
        }
         
        if (!$splitTablename) {
            $tables = $assoc ? array_combine($alias, array_map('trim', $matches['fullname'])) : array_map('trim', $matches['table']);
        } else {
            $tables = array();
            foreach ($matches[0] as $i=>&$v) $tables[$i] = array(!empty($matches['db'][$i]) ? trim($matches['db'][$i], ' `') : null, !empty($matches['name'][$i]) ? trim($matches['name'][$i], ' `') : null, !empty($matches['alias'][$i]) ? preg_replace('/^(?:AS\b\s*)?(`?)(.*?)\\1\s*$/', '$2', $matches['alias'][$i]) : null);
            if ($assoc) $tables = array_combine($alias, $tables);
        }
        
        if (!empty($subtables)) $tables = array_merge($subtables, $tables);
        return $tables;
    }
		
	/**
	 * Split a single part from a join expression
	 *
	 * @param string $sql
	 * @return array
     *
     * @todo Implement DB_SQLSplitter_MySQL::splitJoin()
	 */
    public static function splitJoin($sql)
    {
        if (self::getQueryType($sql)) {
	        $parts = self::split($sql);
	        $sql = isset($parts['from']) ? $parts['from'] : (isset($parts['tables']) ? $parts['tables'] : null);

	        if (empty($sql)) return null;
        }
        
        $parts = null;
        preg_match_all('/(^\s*|,\s*|(?:(NATURAL\s+)?((?:LEFT|RIGHT)\s+)?((?:INNER|CROSS|OUTER)\s+)?(STRAIGHT_)?JOIN\s*+))' .
          '(\b(?:`[^`]++`|\w++)(?:\.(?:`[^`]++`|\w++))?\s*+)' .
          '(?:(\bAS\s*+\b(?:`[^`]++`|\w++)|`[^`]++`|\w++(?<!\b(?:ON|NATURAL|LEFT|RIGHT|INNER|CROSS|OUTER|STRAIGHT_JOIN|JOIN)))\b\s*+)?' .
          '(ON\b\s*+((?:(?:[\,\.]|`[^`]*+`|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|[^`"\'\w\s\.\,]|\w++(?<!\b(?:NATURAL|LEFT|RIGHT|INNER|CROSS|OUTER|STRAIGHT_JOIN|JOIN)))\s*+)+))?/s',
          $sql, $parts, PREG_PATTERN_ORDER);
    }

	/**
	 * Split limit in array(limit, offset)
	 *
	 * @param string $sql  SQL query or limit part
	 * @return array
	 */
	public static function splitLimit($sql)
	{
		$type = self::getQueryType($sql);
		if (isset($type)) {
			$parts = self::split($sql);
			$sql = $parts['limit'];
		}
		if ($sql === null || $sql === '') return array(null, null);
	
		$matches = null;
		if (ctype_digit($sql)) return array($sql, null);
		if (preg_match('/^(\d+)\s+OFFSET\s+(\d+)/', $sql, $matches)) return array($matches[1], $matches[2]);
		if (preg_match('/\d+\s*,\s*(\d+)/', $sql, $matches)) return array($matches[2], $matches[1]);
		
		return null;
	}

    
	//------------ Build expression -----------------
	
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
	public static function buildWhere($column, $value, $compare="=")
	{
		// Prepare
		$compare = empty($compare) ? '=' : trim(strtoupper($compare));
		
		// Handle some simple and common cases, just to improve performance
		if (is_string($column)) {
			if (self::countPlaceholders($column) != 0) {
				return array('where'=>self::parse($column, $value));
			} else if (isset($value) && !is_array($value) && ($compare==='=' || $compare==='!=' || $compare==='>' || $compare==='<' || $compare==='>=' || $compare==='<=')) {
				return array('where'=>"$column $compare " . self::quote($value));
			} elseif ($compare === 'IS NULL' || $compare === 'IS NOT NULL') {
			    if (isset($value) && $value !== '' && (int)$value == 0) $compare = $compare === 'IS NULL' ? 'IS NOT NULL' : 'IS NULL';
				return array('where'=>"$column $compare");
			}
		}
		
		// Prepare
		$column = (array)$column;
		
		if (isset($value)) $value = (array)$value;
		  elseif ($compare === '=') $compare = 'IS NULL';

		if ($compare === 'ANY' || ($compare === '=' && sizeof($value)>1)) $compare = 'IN';
		 
		// Only use the non-null values with between, autoconvert to >= or <=
		if (($compare==="BETWEEN" || $compare==="NOT BETWEEN") && (isset($value[0]) xor isset($value[1]))) {
			$compare = ($compare==="BETWEEN" xor isset($value[0])) ? '<=' : '>=';
			$value = isset($value[0]) ? array($value[0]) : array($value[1]);
		}
		
		// Quote value. (For LIKE: Apply % for %LIKE% to value)
		$matches = null;
		if (preg_match('/^(\%?)(?:REVERSE\s+)?LIKE(\%?)$/', $compare, $matches)) {
			if (isset($value)) foreach ($value as $key=>$val) $value[$key] = self::quote((isset($matches[1]) ? $matches[1] : "") . addcslashes($val, '%_') . (isset($matches[2]) ? $matches[2] : ""));
			$compare = trim($compare, "%");
		} elseif (isset($value)) {
			foreach ($value as $key=>$val) $value[$key] = self::quote($val);
		}

		// Apply reverse -> value LIKE column, instead of column LIKE value
		if (substr($compare, 0, 8) === 'REVERSE ') {
			$tmp = $column;
			$column = $value;
			$value = $tmp;
			$compare = trim(substr($compare, 8));
		}

		// Compare as in any
		if ($compare === "IN" || $compare === "NOT IN" || $compare === "ALL") $value = array_unique($value);
		
		// Create where expression for each column (using if, instead of switch for performance)
		$where = null;
		$having = null;
		
		if ($compare === "ALL") {
			if (!isset($value)) throw new Exception("Unable to add '$compare' criteria: \$value is not set");
			if (!empty($value)) {
			    foreach ($column as &$col) {
    				$having[] = "COUNT(DISTINCT $col) = " . sizeof($value);
	    			$where[] = "$col IN (" . join(", ", $value) . ")";
		    	}
			}
		
		} elseif ($compare === "IN" || $compare === "NOT IN") {
			if (!isset($value)) throw new Exception("Unable to add '$compare' criteria: \$value is not set");
			if (!empty($value)) {
			    foreach ($column as &$col) $where[] = "$col $compare (" . join(", ", $value) . ")";
			}
		
		} elseif ($compare === "BETWEEN" || $compare === "NOT BETWEEN") {
			if (sizeof($value) != 2) throw new Exception("Unable to add '$compare' criteria: \$value should have exactly 2 items, but has " . sizeof($value) . " items");
			foreach ($column as &$col) $where[] = "$col $compare " . $value[0] .  " AND " . $value[1];

		} elseif ($compare === "IS NULL" || $compare === "IS NOT NULL") {
		    if (isset($value) && $value !== '' && (int)$value == 0) $compare = $compare === 'IS NULL' ? 'IS NOT NULL' : 'IS NULL';
		    if (!empty($value)) {
		        foreach ($column as &$col) $where[] = "$col $compare";
		    }
			
		} else {
			if (!isset($value)) throw new Exception("Unable to add '$compare' criteria: \$value is not set");
			if (!empty($value)) {
			    foreach ($column as &$col) foreach ($value as $val) $where[] = "$col $compare $val";
			}
		}

		// Return where expression
		return array('where'=>isset($where) ? join(" OR ", $where) : null, 'having'=>isset($having) ? join(" OR ", $having) : null);
	}
	
	
	//------------ Build statement -----------------
	
	/**
	 * Create a select statement for a table
	 *
	 * @param string  $table     Table
	 * @param mixed   $columns   Array with fieldnames or fieldlist (string); NULL means all fields.
	 * @param mixed   $criteria  False, where string or array(field=>value, ...)
	 * @return string
	 */
	public static function buildSelectStatement($table=null, $columns=null, $criteria=null)
	{
		if (!isset($columns) && isset($table)) {
			$columns = '*';
		} elseif (!is_scalar($columns)) {
			foreach ($columns as $alias=>&$col) $col = self::makeIdentifier($table, $col, is_int($alias) ? null : $alias);
			$columns = join(', ', $columns);
		}
		
		if ($criteria === false) {
			$criteria = 'FALSE';
		} elseif (!is_scalar($criteria)) {
			array_walk($criteria, function($key, $value) {return DB_MySQL_SQLSplitter::makeIdentifier($table, $key) . ' = ' . DB_MySQL_SQLSplitter::quote($value);});
			$criteria = join(' AND ', $criteria);
		}
		
		return "SELECT $columns" . (isset($table) ? " FROM " . self::quoteIdentifier($table) : '') . (isset($criteria) ? " WHERE $criteria" : '');
	}
	
	/**
	 * Create a statement to insert/update rows.
	 * 
	 * @param string  $table
	 * @param array   $primairy_key  Array with fields of primary key
	 * @param array   $columns
	 * @param array   $rows          As array(array(value, value, ...), array(value, value, ...), ...)
	 * @param boolean $overwrite     Add ON DUPLICATE KEY UPDATE
	 * @return string
	 */
	public static function buildStoreStatement($table, $primairy_key=null, $columns=null, $rows=null, $overwrite=true)
	{
		$sql_fields = array();
		$sql_update = array();
		$sql_rows = array();

		if (empty($rows)) throw new Exception("Unable to build store statement: No rows.");
		
		foreach ($fieldnames as $fieldname) {
			$fieldq = self::quoteIdentifier($fieldname);
			$sql_fields[] = $fieldq;
			$sql_update[] = in_array($fieldname, (array)$primairy_key) ? "$fieldq=IFNULL($fieldq, VALUES($fieldq))" : "$fieldq=VALUES($fieldq)";
		}

		foreach ($rows as $row) {
			$sql_row = array();
			foreach ($row as $value) $sql_row[] = self::quote($value, 'DEFAULT');
			$sql_rows[] = "(" . join(", " , $sql_row) . ")";
		}

		return "INSERT INTO" . (isset($table) ? ' ' . self::quoteIdentifier($table) : null) . (!empty($sql_fields) ? " (" . join(", ", $sql_fields) . ")" : null) . (!empty($sql_rows) ? " VALUES " . join(', ', $sql_rows) : null) . ($overwrite && !empty($sql_update) ? " ON DUPLICATE KEY UPDATE " . join(", ", $sql_update) : null);
	}
	
	/**
	 * Create query to update rows of a table.
	 * 
	 * @param string $table
	 * @param array  $columns   Assasioted array as (fielname=>value, ...)
	 * @param array  $criteria  As array(field=>value, ...)
	 * @return string
	 */
	public static function buildUpdateStatement($table=null, $columns=null, $criteria=null)
	{
		foreach ($criteria as $key=>$value) {
		    $where = (isset($where) ? "$where AND " : '') . self::quoteIdentifier($key) . ' = ' . self::quote($value);
		}
	    
		$sql_set = array();
		foreach ($values as $fieldname=>$value) {
			$sql_set[] = self::quoteIdentifier($fieldname) . '=' . self::quote($value);
		}

		return "UPDATE" . (isset($table) ? ' ' . self::quoteIdentifier($table) : null) . (!empty($sql_set) ? " SET " . join(', ', $sql_set) : null) . (isset($where) ? " WHERE $where" : null);
	}
	
	/**
	 * Create query to delete rows from a table.
	 * 
	 * @param string $table     Tablename
	 * @param array  $criteria  As array(field=>value, ...)
	 * @return string
	 */
	public static function buildDeleteStatement($table=null, $criteria=array())
	{
		foreach ($criteria as $field=>$value) {
	        $where = (!isset($where) ? 'WHERE ' : "$where AND ") . self::quoteIdentifier($field) . ' = ' . self::quote($value);
	    }
	    
		return "DELETE" . (isset($table) ? self::quoteIdentifier($table) . ".* FROM " . self::quoteIdentifier($table) : '') . " $where";
	}

	/**
	 * Create query to delete all rows from a table.
	 * 
	 * @param string $table  Tablename
	 * @return string
	 */
	public static function buildTruncateStatement($table=null)
	{
	    return "TRUNCATE" . (isset($table) ? ' ' . self::quoteIdentifier($table) : null);
	}

	/**
	 * Build query to count the number of rows
	 * 
	 * @param mixed $statement  Statement or table
     * @param bool  $all        Don't use limit
     * @return string
	 */
	public static function buildCountStatement($statement, $all=false)
	{
		if (!($statement instanceof DB_Table)) $type = self::getQueryType($statement);
		
		if (isset($type)) {
			$parts = is_array($statement) ? $statement : self::split($statement);
			if ($type == 'insert' && isset($parts['query'])) $parts = self::split($parts['query']);

   			if (!isset($parts['from']) && !isset($parts['into']) && !isset($parts['tables'])) return null; # Unable to determine a from, so no rowcount query possible
   			$table = isset($parts['from']) ? $parts['from'] : (isset($parts['into']) ? $parts['into'] : $parts['tables']);
		} else {
			$table = $statement;
		}
	
		if ($all && isset($parts['limit'])) {
			unset($parts['limit']);
			$statement = $parts;
		}
   		
		if (isset($parts)) {
			if (!empty($parts['having'])) return "SELECT COUNT(*) FROM (" . (is_array($statement) ? self::join($statement) : $statement) . ")";
	   	
			$distinct = null;
			$column = preg_match('/DISTINCT\b.*?(?=\,|$)/si', $parts['columns'], $distinct) ? "COUNT(" . $distinct[0] . ")" : !empty($parts['group by']) ? "COUNT(DISTINCT " . $parts['group by'] . ")" : "COUNT(*)";
	   		if (isset($parts['limit'])) {
	   			list($limit, $offset) = self::splitLimit($parts['limit']);
	   			if (isset($limit)) $column = "LEAST($column, $limit " . (isset($offset) ? ", ($column) - $offset" : '') . ")";
	   		}
		}
   		
   		return self::join(array(0=>'SELECT', 'columns'=>$column, 'from'=>$table, 'where'=>isset($parts['where']) ? $parts['where'] : ''));
	}
	
	
	//------------- Convert statement to specific type --------------------
    
    /**
     * Convert query to a select statement.
     * 
     * @param string $sql
     * @return string
     */
    protected static function convertToSelectQuery($sql)
    {
    	$type = self::getQueryType($sql);
		switch ($type) {
			case 'SELECT':
				return $sql;
			
			case 'INSERT':
			case 'REPLACE':
				$parts = self::split($sql);
				if (!isset($parts['query'])) throw new Exception("Unable to convert $type statement into a SELECT statement: Statement does not contain a SELECT part");
				return $parts['query'];
			
			case 'UPDATE':
				$parts = self::split($sql);
				$cols = self::splitColumns($parts, false, true);
				return self::join(array(0=>'SELECT', array_keys($cols)));
				
		}
		
		throw new Exception("Unable to convert a $type statement into a SELECT statement");
    }

    /**
     * Convert query to a insert/replace statement.
     * 
     * @param string $newtype
     * @param string $sql
     * @return string
     */
    protected static function convertToInsertQuery($newtype, $sql)
    {
    	$type = self::getQueryType($sql);
		switch ($type) {
			case 'SELECT' :
				return "$newtype INTO $sql";
				
			case 'INSERT':
			case 'REPLACE':
				return preg_replace('/^\s*' . $newtype. '\b/i', $type, $sql);
		}
		
		throw new Exception("Unable to convert a $type statement into a $newtype statement");
    }
}

