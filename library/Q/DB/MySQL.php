<?php
namespace Q;

require_once 'Q/DB.php';
require_once 'Q/DB/MySQL/SQLSplitter.php';
require_once 'Q/DB/SQLStatement.php';

require_once 'Q/DB/MySQL/Result.php';
require_once 'Q/DB/MySQL/Result/Tree.php';
require_once 'Q/DB/MySQL/Result/NestedSet.php';

require_once 'Q/Cache.php';

/**
 * DB abstraction layer for MySQL.
 * 
 * @package    DB
 * @subpackage DB_MySQL
 */
class DB_MySQL extends DB
{
	const NESTED_LEFT = "nested:left";
	const NESTED_RIGHT = "nested:right";
	const NESTED_CHILDREN = "nested:children";
	
	/**
	 * Class of objects created by Q\DB_MySQL.
	 * This may be overwritten, but make sure to extend the correct class. This is assumed and not checked!
	 * 
	 * @var string
	 */
	public static $classes = array( 
	    'SQLSplitter'=>'Q\DB_MySQL_SQLSplitter',
	    'Statement'=>'Q\DB_SQLStatement',
		'Result'=>'Q\DB_MySQL_Result',
		'Result_Tree'=>'Q\DB_MySQL_Result_Tree',
		'Result_NestedSet'=>'Q\DB_MySQL_Result_NestedSet',
	    'native'=>'mysqli',
	);
	
	/**
	 * Field types for MySQL specific types.
	 * @var array
	 */
	public static $fieldtypes = array(
		MYSQLI_TYPE_DECIMAL=>'decimal',
		MYSQLI_TYPE_NEWDECIMAL=>'decimal',
		MYSQLI_TYPE_BIT=>'bit',
		MYSQLI_TYPE_TINY=>'integer',
		MYSQLI_TYPE_SHORT=>'integer',
		MYSQLI_TYPE_LONG=>'integer',
		MYSQLI_TYPE_FLOAT=>'double',
		MYSQLI_TYPE_DOUBLE=>'double',
		MYSQLI_TYPE_NULL=>'null',
		MYSQLI_TYPE_TIMESTAMP=>'datetime',
		MYSQLI_TYPE_LONGLONG=>'integer',
		MYSQLI_TYPE_INT24=>'integer',
		MYSQLI_TYPE_DATE=>'date',
		MYSQLI_TYPE_TIME=>'time',
		MYSQLI_TYPE_DATETIME=>'datetime',
		MYSQLI_TYPE_YEAR=>'year',
		MYSQLI_TYPE_NEWDATE=>'date',
		MYSQLI_TYPE_ENUM=>'enum',
		MYSQLI_TYPE_SET=>'set',
		MYSQLI_TYPE_TINY_BLOB=>'blob',
		MYSQLI_TYPE_MEDIUM_BLOB=>'blob',
		MYSQLI_TYPE_LONG_BLOB=>'blob',
		MYSQLI_TYPE_BLOB=>'blob',
		MYSQLI_TYPE_VAR_STRING=>'string',
		MYSQLI_TYPE_STRING=>'string',
		MYSQLI_TYPE_GEOMETRY=>'geometry',
		'varchar'=>'string',
		'timestamp'=>'datetime'
	);

	/**
	 * Unsigned for MySQL specific types.
	 * @var array
	 * 
	 * @todo Find out max per field type (and apply it)
	 */
	public static $fieldtypeMax = array(
        MYSQLI_TYPE_TINY=>255
	);
	
	
    /**
	 * Native mysql connection object
	 * @var mysqli
	 */
	protected $native;

	/**
	 * An sqlSplitter object, holding functions to split a query statement.
	 * @var DB_MySQL_SQLSplitter
	 */
	public $sqlSplitter;
	
	
	/**
	 * Open a new connection to MySQL database server.
	 * @static
	 *
	 * @param string $host      Hostname, hostname:port or DSN
	 * @param string $username
	 * @param string $password
	 * @param string $dbname
	 * @param string $port
	 * @param string $socket
	 * @param array  $settings  Additional settings
	 * @return DB_MySQL
	 */
	public function connect($host=null, $user=null, $password=null, $database=null, $port=null, $unix_socket=null, array $settings=array())
	{
	    if (isset($this) && $this instanceof self) throw new Exception("DB_MySQL instance is already created.");
	    
	    // Aliases
	    $hostname =& $host;
	    $db =& $database;
	    $dbname =& $database;
	    $username =& $user;
	    $pwd =& $password;
	    
	    if (is_array($host)) {
	        $dsn_settings = $host + DB::$defaultOptions;
	        if (isset($dsn_settings['dsn'])) {
	            $dsn_settings = extract_dsn($dsn_settings['dsn']) + $dsn_settings;
	            unset($dsn_settings['dsn']);
	        }
	        $host = null;
	        extract($dsn_settings, EXTR_IF_EXISTS);
	    } elseif (strpos($host, '=') !== false) {
		    $dsn = $host;
			$host = null;
			$dsn_settings = extract_dsn($dsn) + DB::$defaultOptions;
			extract($dsn_settings, EXTR_IF_EXISTS);
		} else {
			$dsn_settings = DB::$defaultOptions;
		}
		
		$matches = null;
		if (preg_match('/^(\w+):(\d+)$/', $host, $matches)) list(, $host, $port) = $matches;

		$class = self::$classes['native'];
		$native = new $class($host, $user, $password, $database, $port, $unix_socket);
		if (!$native) throw new DB_Exception("Connecting to mysql database failed: " . \mysqli::connect_error());
		
		$settings = compact('host', 'user', 'password', 'database', 'port', 'unix_socket') + $dsn_settings + $settings;

	    return new self($native, $settings);
	}

	/**
	 * Reconnect to the db server
	 */
	public function reconnect()
	{
		if (isset($this->native) && @$this->native->ping()) return;
		
		$native = new mysqli($this->settings['host'], $this->settings['user'], $this->settings['password'], $this->settings['dbname'], $this->settings['port'], $this->settings['unix_socket']);
		if (!$native) throw new DB_Exception("Connecting to mysql database failed: " . \mysqli::connect_error());
		$this->native = $native;
	}
	
	/**
	 * Close the db connection
	 */
	public function closeConnection()
	{
		@$this->native->close();
	}
	

	/**
	 * Class constructor
	 *
	 * @param mysqli $native
	 * @param array  $settings   Settings used to create connection
	 */
	public function __construct(\mysqli $native, $settings=array())
	{
		parent::__construct($native, $settings);
		
		$class = self::$classes['SQLSplitter'];
		$this->sqlSplitter = new $class();

		if (isset($this->log)) $this->log->write(array('statement'=>"Connected to {$settings['host']}.", (isset($settings['database']) ? "Using database '{$settings['database']}'." : '')), 'db-connect');
	}
	
	/**
	 * Class destructor: close db connection
	 */
	public function __destruct()
	{
		$this->close();
	}
	
	
	/**
	 * Return the connection string (without additional settings)
	 * 
	 * @return string
	 */
	public function getDSN()
	{
		return 'mysql:' . implode_assoc($this->settings);
	}

	/**
	 * Get the native mysqli object.
	 *
	 * @return mysqli
	 */
	public function getNative()
	{
		return $this->native;
	}
	
	/**
	 * Actually execute a query.
	 * Logs statement and result if a logger is set.
	 *
	 * @return mysqli_result
	 */
	public function nativeQuery($statement)
	{
	    if (!isset($this->log)) return $this->native->query($statement);
	    
	    $sec = microtime(true);
		$result = $this->native->query($statement);
		$time = microtime(true) - $sec; unset($sec);
		
		$count = is_object($result) ? $result->num_rows : $this->native->affected_rows;
		$errno = $this->native->errno;
		$error = $this->native->error;
		
		$rows = array();
		if (isset($this->logSettings['rows'])) {
		    if (function_exists('mysqli_fetch_all')) $rows = $this->native->fetch_all(MYSQLI_NUM);
              else while (($row = $this->native->fetch_row())) $rows[] = $row; break;
		}

		$args = call_user_func_array('compact', $this->logColumns);
		$this->log->write($args, 'db-query');
		
		return $result;
	}
	
	
	/**
	 * Retrieve the version of the DB server
	 * @return string
	 */
	public function getServerVersion()
	{
		$this->native->server_info;
	}
	
	/**
	 * Get the database (schema) name.
	 * 
	 * @return string
	 */	
	public function getDBName()
	{
		$result = $this->nativeQuery('SELECT DATABASE()');
		if (!$result) throw new DB_QueryException("Show tables query failed: " . $this->native->error);
		list($db) = $result->fetch_row();
		return $db;
    }
	
	/**
	 * Get a list of all the tables in the DB.
	 * 
	 * @return array
	 */
	public function getTableNames()
	{
		$result = $this->nativeQuery('SHOW TABLES');
		if (!$result) throw new DB_QueryException("Show tables query failed: " . $this->native->error);
		if ($result->num_rows == 0) return null;
		
		$tables = array();
		while (($row = $result->fetch_row())) $tables[] = $row[0];
		return $tables;
	}
		
	/**
	 * Get a list of all the fields in a table.
	 *
	 * @param string $table
	 * @return array
	 */
	public function getFieldNames($table)
	{
		$result = $this->nativeQuery('SHOW FIELDS FROM ' . $this->sqlSplitter->quoteIdentifier($table));
		if (!$result) throw new DB_QueryException("Show fields query for table '$table' failed: " . $this->native->error);
		if ($result->num_rows == 0) return null;
		
		$fieldnames = array();
		while(($row = $result->fetch_assoc())) $fieldnames[] = $row['Field'];
        return $fieldnames;
	}
	
	/**
	 * Get status information about a table.
	 *
	 * @param string $table
	 * @return array
	 */
	public function getTableInfo($table)
	{
		$result = $this->nativeQuery('SHOW TABLE STATUS LIKE ' . $this->sqlSplitter->quote($table));
		if (!$result) throw new DB_QueryException("Show table status query for table '$table' failed: " . $this->native->error);
		if ($result->num_rows == 0) return null;
		
		return array_change_key_case($result->fetch_assoc(), CASE_LOWER);
    }
	
	/**
	 * Get properties for the table and all fields from the database
	 *
	 * @param string $table
	 * @return array
	 */
	protected function fetchMetaData($table)
	{
	    // Get table information (only stuff that doesn't change on data change)
		$result = $this->nativeQuery('SHOW TABLE STATUS LIKE ' . $this->sqlSplitter->quote($table));
		if (!$result) throw new DB_QueryException("Show table status query for table '$table' failed: " . $this->native->error);
		if ($result->num_rows == 0) return null;
		
		$row = $result->fetch_assoc();
		$tbl_props['name'] = $row['Name'];
		$tbl_props['engine'] = $row['Engine'];
		$tbl_props['row_format'] = $row['Row_format'];
		$tbl_props['max_data_length'] = $row['Max_data_length'];
		$tbl_props['create_time'] = $row['Create_time'];
		$tbl_props['collation'] = $row['Collation'];
		
		// Get basic field information using 'show fields' command
		$result = $this->nativeQuery('SHOW FIELDS FROM ' . $this->sqlSplitter->quoteIdentifier($table));
		if (!$result) throw new DB_QueryException("Show fields query for table '$table' failed: " . $this->native->error);
		
		$properties = array();
		$primaries = array();
		while(($row = $result->fetch_assoc())) {
			$properties[$row['name']] = $this->convertFieldMeta($table, &$row);
		}
		
		// Get foreign key information using the information_schema (MySQL 5.0+ only)
		if ($this->native->server_version >= 50000) {
			list($db_name, $table_name) = $this->sqlSplitter->splitIdentifier($table);
			
			$result = $this->nativeQuery('SELECT `COLUMN_NAME`, IF(`REFERENCED_TABLE_SCHEMA` = DATABASE(), `REFERENCED_TABLE_NAME`, CONCAT(`REFERENCED_TABLE_SCHEMA`, ".", `REFERENCED_TABLE_NAME`)), `REFERENCED_COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` WHERE `TABLE_SCHEMA` = ' . (isset($db_name) ? $this->sqlSplitter->quote($db_name) : 'DATABASE()') . ' AND `TABLE_NAME` = ' . $this->sqlSplitter->quote($table_name) . ' AND `REFERENCED_COLUMN_NAME` IS NOT NULL');
			if (!$result) throw new DB_QueryException("Query in INFORMATION_SCHEMA.CONSTRAINTS for table '$table' failed: " . $this->error);
			while(($row = $result->fetch_row())) {
				$properties[$row[0]]['datatype'] = 'lookupkey';
				$properties[$row[0]]['foreign_table'] = $row[1];
				$properties[$row[0]]['foreign_column'] = $row[2];
			}
		}

		// Set additional properties for table
		if (count($primaries) === 1) $properties[reset($primaries)]['role'] = 'id';
		
		// Add the table properties last to keep field order
		$properties['#table'] =& $tbl_props;
		
		return $properties;
	}
	
	/**
	 * Convert meta data of a field to DB_Field properties
	 * 
	 * @param string $table
	 * @param array  $props
	 */
	protected function convertFieldMeta($table, $props)
	{
		$props = array_change_key_case($props);
		
		if (isset($table)) $props['table'] = $table;
		
		$props['name'] = $props['field'];
		unset($props['field']);

		$props['required'] = $props['null'] !== 'YES' && $props['extra'] !== 'auto_increment';
		
		if ($props['key'] === 'PRI') {
		    $props['is_primary'] = true;
		    if ($props['extra']==='auto_increment') $props['role'] = 'id';
		      else $primaries[] = $props['name'];
		}

		$props['native_type'] = $props['type'];
		if ($props['type'] === 'tinyint(1)') $props['type'] = 'boolean';
		  else $props['type'] = preg_replace('/^(?:tiny|medium|big|long(?:long)?)(?=\w)/i', '', $props['type']);
		
		$matches = null;
		if (preg_match("/^(.*?)\\((.+?)\\)/", $props['type'], $matches)) {
			$props['type'] = $matches[1];
			switch (true) {
				case strstr($matches[2], ','): $props['values'] = $matches[2]; break;
				case ctype_digit($matches[2]): $props['maxlength'] = $matches[2]; break;
				case is_numeric($matches[2]): list($len, $props['decimals']) = explode('.', $matches[2]); $props['maxlength'] = $len + $props['decimals'] + 1; break;
			}
		}
		
        if (isset(DB_MySQL::$fieldtypes[$props['type']])) $props['type'] = self::$fieldtypes[$props['type']];
        return $props;
	}
	
	
	/**
	 * Quote a value so it can be savely used in a query.
	 * 
	 * @param mixed  $value
	 * @param string $type   Force SQL type (not supported)
	 * @param string $empty  Return $empty if $value is null
	 * @return string
	 * 
	 * @todo Fix the use of $type for Q\DB_MySQL::quote()
	 */
	public function quote($value, $type=null, $empty='NULL')
	{
		return $this->sqlSplitter->quote($value, $empty);
	}
	
	/**
	 * Quotes a string so it can be safely used as schema, table or field name.
	 * Dots are seen as seperator and are kept out of quotes.
	 * 
	 * Will not quote expressions without DB::QUOTE_STRICT. This means it is not secure without this option.
	 * 
	 * @param string $identifier
	 * @param int    $flags       DB::QUOTE_LOOSE or DB::QUOTE_STRICT
	 * @return string
	 */
	public function quoteIdentifier($identifier, $flags=DB::QUOTE_STRICT)
	{
	    return $this->sqlSplitter->quoteIdentifier($identifier, $flags);
	}
		
	/**
	 * Check if a identifier is valid as field name or table name
	 *
	 * @param string  $name
	 * @param boolean $withgroup  TRUE: group.name, FALSE: name, NULL: both
	 * @param boolean $withalias  Allow an alias (AS alias)
	 * @return boolean
	 */
	public function validIdentifier($name, $withgroup=null, $withalias=false)
	{	
		return $this->sqlSplitter->validIdentifier($name, $withgroup, $withalias);
	}

	/**
	 * Split a column name in table, column and alias OR table name in db, table and alias
	 *
	 * @param string $name  Full fieldname
	 * @return array
	 */
	public function splitIdentifier($name)
	{
		return $this->sqlSplitter->splitIdentifier($name);
	}
	
	/**
	 * Create a full fieldname OR create a full tablename
	 *
	 * @param string $group  Table name / DB name
	 * @param string $name   Field name / Table name
	 * @param string $alias
	 * @return boolean
	 */
	public function makeIdentifier($group, $name, $alias=null)
	{
		return $this->sqlSplitter->makeIdentifier($group, $name, $alias);
	}
	
	/**
	 * Parse arguments into a statement
	 *
	 * @param mixed $statement  String or query object
	 * @param array $args       Arguments to parse into statement on placeholders
	 * @return string
	 */
	public function parse($statement, $args)
	{
		return $this->sqlSplitter->parse($statement, $args);
	}	
	
	/**
	 * Prepare a statement for execution.
	 * {@internal 1st argument might be the source (Q\Table), in that case the 2nd argument is the statement.}}
	 *
	 * @param string $statement
	 * @return DB_SQLStatement
	 */
	public function statement($statement) 
	{
		// Get statement out of object
		if ($statement instanceof DB_Table) {
		    $source = $statement;
		    $statement = func_get_arg(1);
		} elseif (is_object($statement)) {
			$source = $statement->getBaseTable();
			$statement = $statement->getStatement();
		}
		
		$class = self::$classes['Statement'];
		return new $class(isset($source) ? $source : $this, $statement);
	}

	/**
	 * Resolve semantic mapping for fields in criteria.
	 * 
	 * @param string  $table
	 * @param array   $criteria
	 * @param boolean $keyvalue  Assume key/value pairs
	 */
	protected function resolveCriteria($table, &$criteria, $keyvalue=false)
	{
		if (!isset($criteria)) return;

        if (!$keyvalue && (!is_array($criteria) || !is_string(key($criteria)))) {
            $pk = $this->table($table)->getPrimaryKey();
            if (empty($pk)) throw new Exception("Unable to select record for $table: Unable to determine a WHERE statement. The table might have no primary key.");
            
            $criteria = (array)$criteria;
	    	if (count($pk) != count($criteria)) throw new Exception("Unable to select record for $table: " . count($criteria) . " values specified, while primary key from table consists of " . count($keys) . " keys (" . implode(', ', $keys) . ").");
	    	
	    	$criteria = array_combine((array)$pk->getName(DB::FIELDNAME_DB | DB::FIELDNAME_IDENTIFIER), $criteria);
	    } else {
	    	foreach ($criteria as $field=>&$value) {
				if ($field[0] === '#') $keys[$i] = (string)$this->table($table)->$field;
	        }
	        
	        // Most cases this is not needed, so merge afterwards
	        if (isset($keys)) {
	        	$keys =+ array_keys($criteria);
	        	sort($keys);
	        	$criteria = array_combine($keys, $criteria);
	        }
	    }
	}
	
	/**
	 * Create a select statement for a table
	 *
	 * @param string $table     Tablename
	 * @param mixed  $fields    Array with fieldnames, fieldlist (string) or SELECT statement (string). NULL means all fields.
	 * @param mixed  $criteria  The value for the primairy key (int/string or array(value, ...)) or array(field=>value, ...)
	 * @param string $where     Additional criteria as string
	 * @return DB_SQLStatement
	 */
	public function select($table=null, $fields=null, $criteria=null, $where=null)
	{
	    $this->resolveCriteria($table, $criteria);
	    
		if (is_array($fields)) {
    		foreach ($fields as &$field) {
    			if (is_string($field) && $field[0] === '#') $field = $table->$field;
    		}
		}
		
		return new self::$classes['Statement']($source, $this->sqlSplitter->buildSelectStatement($table, $fields, $criteria, $where));
	}
	
	/**
	 * Build an insert/update query statement.
	 *
	 * @param string $table   Tablename
	 * @param array  $values  Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @param Give additional arguments (arrays) to insert/update multiple rows. $value should be array(fieldname, ...) instead. U can also use Q\DB::args(values, $rows).
	 * @return DB_SQLStatement
	 * 
	 * @throws Q\DB_Constraint_Exception when no rows are given.
	 */
	public function store($table=null, $values=null)
	{
		$parent = $table instanceof DB_Table && $table->getConnection() === $this ? $table : $this;
		if ($table instanceof DB_Table) $table = $table->getTableName(); 
		
	    // Get the fieldnames and rows (values)
		if (func_num_args() > 2) {
			$fieldnames = $values;
			
		    $arg3 = func_get_arg(2);
		    if (is_object($arg3) && !empty($arg3->{'#arg'})) {
		        $rows = $arg3->value;
		    } else {
			    $rows = func_get_args();
			    $rows = array_splice($rows, 2);
		    }
		} else {
			$fieldnames = is_string(key($values)) ? array_keys($values) : null;
			$rows = array($values);
		}

		if (empty($rows)) throw new DB_Constraint_Exception("No rows to store.");
		
		// Create statement
		$pk = (array)$this->getPrimaryKey($table);
		if ($fieldnames === null) $fieldnames = $this->getFieldNames($table);

		return new self::$classes['Statement']($parent, $this->sqlSplitter->buildStoreStatement($table, $pk, $fieldnames, $rows));
	}
	
	/**
	 * Build a update query statement
	 *
	 * @param string $table   Tablename
	 * @param mixed  $id      The value for a primairy (or as array(value, ..) if multiple key fields) or array(field=>value, ...)
	 * @param array  $values  Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @return DB_SQLStatement
	 */
	public function update($table=null, $id=null, $values=null)
	{
		$parent = $table instanceof DB_Table && $table->getConnection() === $this ? $table : $this;
		if ($table instanceof DB_Table) $table = $table->getTableName(); 
		
	    if (!is_array($id) || !is_string(key($id))) $id = array_combine((array)$this->getPrimaryKey($table), (array)$id);

		foreach ($values as $fieldname=>$value) {
		    if ($fieldname[0] == '#') {
			    $values[(string)$this->table($table)->$fieldname] = $value;
			    unset($values[$fieldname]);
		    }
		}
	    
		return new self::$classes['Statement']($parent, $this->sqlSplitter->buildUpdateStatement($table, $id, $values));
	}

	/**
	 * Build a delete query statement.
	 *
	 * @param string $table  Tablename
	 * @param mixed  $id     The value for a primairy (or as array(value, ..) if multiple key fields) or array(field=>value, ...)
	 * @return DB_SQLStatement
	 */
	public function delete($table=null, $id=null)
	{
		$parent = $table instanceof DB_Table && $table->getConnection() === $this ? $table : $this;
		if ($table instanceof DB_Table) $table = $table->getTableName(); 

        $statement = $this->sqlSplitter->buildDeleteStatement($table, $id);
		
	    return new self::$classes['Statement']($parent, $statement);
	}
	
	/**
	 * Build a delete query statement.
	 *
	 * @param string $table  Tablename
	 * @param mixed  $id     The value for a primairy (or as array(value, ..) if multiple key fields) or array(field=>value, ...)
	 * @return DB_SQLStatement
	 */
	public function truncate($table=null, $id=null)
	{
		$parent = $table instanceof DB_Table && $table->getConnection() === $this ? $table : $this;
		if ($table instanceof DB_Table) $table = $table->getTableName(); 

	    $statement = $this->sqlSplitter->buildTruncateStatement($table);
	    return new self::$classes['Statement']($parent, $statement);
	}	
	
	/**
	 * Start database transaction.
	 */
	public function beginTransaction()
	{
		$this->nativeQuery("START TRANSACTION");
	}

	/**
	 * Commit changes made in the current transaction.
	 */
	public function commit()
	{
		$this->nativeQuery("COMMIT");
	}

	/**
	 * Discard changes made in the current transaction.
	 */
	public function rollBack()
	{
		$this->nativeQuery("ROLLBACK");
	}
	
	
	/**
	 * Load a record (from global space).
	 * 
	 * @throws Exception
	 */
	public function load($id, $resulttype=DB::FETCH_RECORD)
	{
		throw new Exception("Unable to load record '$id': MySQL holds records in tables, so use DB::i()->table(..)->load()");
	}
	
	/**
	 * Excecute query.
	 * Returns DB_MySQL_Result for 'SELECT', 'SHOW', etc queries, returns new id for 'INSERT' query, returns TRUE for other
	 * 
	 * @param mixed $statement  String or a query statement object
	 * @param array $args       Arguments to be parsed into the query on placeholders
	 * @return DB_MySQL_Result
	 */
	public function query($statement, $args=array())
	{
		// Get statement out of object
		if (is_object($statement)) {
			$source = $statement->getBaseTable();
			$statement = $statement->getStatement();
		}
		
		// Parse arguments
		if (func_num_args() > 2) {
			$args = func_get_args();
			array_shift($args);
		}
		if (!empty($args)) $statement = $this->parse($statement, $args);
		
		// Extract (child) subqueries for cascading data
		$tree = $this->sqlSplitter->extractTree($statement);

		// Execute query statement
		$result = $this->nativeQuery($tree[0]);
		if (!$result) {
			// TODO: Throw constraint exception on constraint error
			throw new DB_QueryException($this->native->error, $tree[0], $this->native->errno);
		}
		
		// Return value if query did not return a mysql_result object
		if (!is_object($result)) return $this->native->insert_id ? $this->native->insert_id : $result;

		// Create result object
		$class = count($tree) > 1 ? self::$classes['Result_Tree'] : self::$classes['Result'];
		while ($field = $result->fetch_field()) {
		    if ($field->name === self::NESTED_LEFT) {
		        $class = self::$classes['Result_NestedSet'];
		        break;
		    }
		}
		
		load_class($class);
		$ob = new $class(isset($source) ? $source : $this, $result, $statement);
		
		// If statement has any childqueries, create tree result
		if (count($tree) > 1) {
			for ($i=1, $m=count($tree); $i<$m; $i++) {
				// array(index, statement, format, filter(bool))
				$child_result = $tree[$i][3] ? $this->query($tree[$i][1], $ob->getColumn($tree[$i][0])) : $this->query($tree[$i][1]);
				$ob->addChild($ob->getFieldIndex($tree[$i][0]), $child_result, $tree[$i][2]);
			}
		}
		
		// Pass base table definition if any and return result
		return $ob;
	}
	
	
	/**
	 * Gets the number of affected rows in a previous MySQL operation.
	 * 
	 * @return int
	 */
	public function affectedRows()
	{
		return $this->native->affected_rows;
	}
}

if (class_exists('Q\ClassConfig')) ClassConfig::extractBin('Q\DB_MySQL');
