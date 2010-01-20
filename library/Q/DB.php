<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/SecurityException.php';
require_once 'Q/DB/Exception.php';
require_once 'Q/DB/LimitException.php';

require_once 'Q/Multiton.php';
require_once 'Q/DB/Table.php';

require_once 'Q/Cache.php';
require_once 'Q/Config.php';

/**
 * A database abstraction layer, adding functionality as well as abstraction.
 *
 * To create a connection use Q\DB::connect(SETTINGS)->makeInstance() or set 'db' configuration settings.
 * @example Q\DB::connect('mysql', 'localhost', 'mydbuser', 'mypwd')->makeInstance();
 * @example Q\DB::connect('mysql:host=localhost;user=mydbuser;password=mypwd')->makeInstance();
 * 
 * To use additional meta data, use the setting 'config'. The settings can be split out using a dot.
 * @example Q\DB::connect('mysql:host=localhost;config=yaml:path/to/dbconfig')->makeInstance();
 * @example Q\DB::connect('mysql:host=localhost;config.driver=yaml;config.path=path/to/dbconfig')->makeInstance();
 * 
 * For debugging purposes it can be useful to log database queries (to file, output, FireBug).
 * For this you can use the setting 'log' and 'log-columns'.
 * @example Q\DB::connect('mysql:host=localhost;log="firephp + file:/tmp/myqueries.log"')->makeInstance();
 * 
 * {@internal A child class should have a $classes property (see Q\DB_MySQL as example).}} 
 * 
 * @package DB
 */
abstract class DB implements Multiton
{
	/** Fetch as ordered array */
	const FETCH_ORDERED = 3;
	/** Fetch as associative array */
	const FETCH_ASSOC = 2;
	/** Fetch as array with both ordered and associative keys */
	const FETCH_FULLARRAY = 4;
	/** Fetch grouping values per table */
	const FETCH_PERTABLE = 32;
	/** Fetch single value */
	const FETCH_VALUE = 33;
	/** Fetch as active record */
	const FETCH_RECORD = 34;
	/** Fetch as document */
	const FETCH_DOCUMENT = 35;
	/** Fetch as associative array with roles as keys */ 
	const FETCH_ROLES = 36;
	/** Fetch as stdClass object */
	const FETCH_OBJECT = 5;
	/** Option; Don't fetch children with tree result */
	const FETCH_NON_RECURSIVE = 0x100;
	
	/** Get name as known in result/table */
	const FIELDNAME_NAME = 1;
	/** Get column name */
	const FIELDNAME_COLUMN = 2;
	/** Get table and column name */
	const FIELDNAME_FULL = 3;
	/** Get table and column as in database */
	const FIELDNAME_DB = 4;
	/** Option; Add alias */
	const WITH_ALIAS = 0x10;
	/** Option; Return fieldlist as string */
	const FIELDLIST = 0x20;
	
	/** Prepend to part */
	const PREPEND = 1;
	/** Append to part */
	const APPEND = 2;
	/** Replace part */
	const REPLACE = 4;
	
	/** Use having instead of where */
	const HAVING = 0x40;
	/** Don't overwrite values if record already exists */
	const NO_OVERWRITE = 0x80;
	
	/** Don't quote identifiers at all */
	const QUOTE_NONE = 0x100;
	/** Quote identifiers inside expressions */
	const QUOTE_LOOSE = 0x200;
	/** Quote string as field/table name */
	const QUOTE_STRICT = 0x400;
	/** Don't map identifiers */
	const DONT_MAP = 0x8000;
	
	/** Quote value as value when adding a column in a '[UPDATE|INSERT] SET ...' query */
	const SET_VALUE = 0;
	/** Quote value as expression when adding a column in a '[UPDATE|INSERT] SET ...' query */
	const SET_EXPRESSION = 0x800;
	
	/** Unquote values */
	const UNQUOTE = 0x1000;
	/** Cast values */
	const CAST = 0x2000;
	
	/** Glue with OR (exp OR exp OR exp) */
	const GLUE_AND = 0;
	/** Glue with AND (exp AND exp AND exp) */
	const GLUE_OR = 0x4000;
	
	/** Split fieldname in array(table, field, alias) */
	const SPLIT_IDENTIFIER = 1;
	/** Remove '[AS] alias' (for SELECT) or 'to=' (for INSERT/UPDATE) and return as associated array */
	const SPLIT_ASSOC = 2;
	
	/** Sort ascending */
	const ASC = 1;
	/** Sort descending */
	const DESC = 2;
	
	/** Get value as record */
	const ORM = 1;
	/** Get value to used in store query */
	const FOR_SAVE = 2;
	
    /** Recalculate, don't get from cache */
    const RECALC = 1;
    
    /** Get/count all rows, don't limit */
    const ALL_ROWS = 1;
    
    
	/**
	 * Drivers with classname
	 * @var array
	 */
	static public $drivers = array(
		'mysql'=>'Q\DB_MySQL'
	);

	/**
	 * Field types with classname
	 * @var array
	 */
	static public $recordtypes = array(
	    null=>'Q\DB_Record',
	);
	
	/**
	 * Field types with classname
	 * @var array
	 */
	static public $fieldtypes = array(
	  null=>'Q\DB_Field',
	  'lookup'=>'Q\DB_Field_ForRecord',
	  'recordset'=>'Q\DB_Field_ForRecordset',
	);
	
	
	/**
	 * Registerd instances
	 * @var Q\DB[]
	 */
	static protected $instances = array();

	/**
	 * Default configuration options
	 * @var array
	 */
	static public $defaultOptions = array();	
	
	
	/**
	 * Properties that should be cast to a specific type.
	 * @var array
	 */
	static public $forceTypeTableProperties = array();
    
	/**
	 * Properties that should be cast to a specific type.
	 * @var array
	 */
	static public $forceTypeFieldProperties = array(
	    'role'=>'array'
    );
	
	/**
	 * Default properties for tables
	 * @var array
	 */
	static public $defaultTableProperties = array();
	
	/**
	 * Default field properties
	 * @var array
	 */
	static public $defaultFieldProperties = array(
	    'name' => array(
	      '{$table.parent}_id' => array('datatype'=>'lookupkey', 'role'=>array('parentkey'), 'auto:role:parentkey'=>true, 'foreign_table'=>'$table.parent', 'foreign_field'=>'id', 'orm'=>'$table.parent'),
	      '/^(.*)_id$/' => array('datatype'=>'lookupkey', 'foreign_table'=>'$1', 'foreign_field'=>'id', 'orm'=>'$1'),
	    ),
	    
		'type'=> array(
    	  'bit' => array('numeric'=>true),
    	  'tinyint' => array('numeric'=>true),
    	  'smallint' => array('numeric'=>true),
    	  'mediumint' => array('numeric'=>true),
    	  'int' => array('numeric'=>true),
    	  'integer' => array('numeric'=>true),
    	  'bigint' => array('numeric'=>true),
    	  'float' => array('numeric'=>true),
    	  'double' => array('numeric'=>true),
    	  'double precision' => array('numeric'=>true),
    	  'real' => array('numeric'=>true),
    	  'decimal' => array('numeric'=>true),
    	  'dec' => array('numeric'=>true),
    	  'fixed' => array('numeric'=>true),

	      'children' => array('fieldtype'=>'recordset', 'multiple'=>true),
	    ),
    	
    	'datatype' => array(
    	  'currency' => array('unit'=>"�", 'decimals'=>2, 'numeric'=>true),
    	  'percentage' => array('unit'=>"%", 'suffix'=>"%", 'numeric'=>true),
	      'lookupkey' => array('fieldtype'=>'lookup'), 
	      'junction' => array('multiple'=>true, 'junction_table'=>'{$table}_{$foreign_table}'),
	      'children' => array('multiple'=>true)
	    ),
	    
	    'role' => array(
	      'id' => array('hidden'=>true),
	      'parentkey' => array('hidden'=>true),
        ),
    );

	/**
	 * Function to apply the default table properties.
	 * @var string
	 */
	static private $fnApplyTableDefaults;

	/**
	 * Hash to see if default table properties have changed.
	 * @var array
	 */
	static private $dtpUsedForFunction;
	
	/**
	 * Dynamicly generated function to apply the default field properties.
	 * @var string
	 */
	static private $fnApplyFieldDefaults;

	/**
	 * Default field properties, which ware used in generating function.
	 * @var array
	 */
	static private $dfpUsedForFunction;
	
	/**
	 * Cache for generated methods.
	 * @var Cache
	 */
	static public $functionCache = true;
	

	/**
	 * Native DB connection object.
	 * @var object|resource
	 */
	protected $native;

	/**
	 * Settings used to create connection.
	 * @var array
	 */
	protected $settings;	

	/**
	 * Config collection for table and field properties.
	 * @var Config
	 */
	protected $metadataConfig;

	/**
	 * Cache for metadata.
	 * @var Cache
	 */
	public $metadataCache;

	/**
	 * Cached table objects.
	 * @var array
	 */
	protected $tables=array();
	
	
	/**
	 * Log queries and other actions.
	 * Using DB logging is bad for performance, so for debugging purpose only.
	 * 
	 * @var Q\Logger
	 */
	public $log;

	/**
	 * Columns supplied for logging.
	 * Possible columns: 'statement', 'count', 'time', 'rows', 'errno', 'error'.
	 * 
	 * Note: Using 'rows' is extremely bad for performance, only use when needed.
	 * 
	 * @var array
	 */
	public $logColumns=array('statement', 'count', 'time', 'errno', 'error');

	
	// -----
	
	/**
	 * Open a new connection to a database server.
	 * If DSN is given, it will be passed to the connect method of the driver class, otherwise all additional arguments are passed.
	 *
	 * @param string|array $dsn  DSN, driver or array('driver'=>DRIVER, 'host'=>HOST, ...)
	 * @param Additional arguments are passed to driver-class::connect()
	 * @return DB
	 */
	public function connect($dsn=null)
	{
	    if (isset($this) && $this instanceof self) throw new Exception("DB instance is already created.");
	    
	    if (is_array($dsn)) {
	        if (isset($dsn['driver'])) $dsn = $dsn['driver'];
	          elseif (isset($dsn['dsn'])) $dsn = $dsn['dsn'];
	    }
	    
	    if (!isset($dsn)) {
	        if (isset(self::$defaultOptions['driver'])) $dsn = $dsn['driver'];
	          else throw new Exception("No driver specified in DB settings.");
	    }
		
	    list($driver) = explode(':', $dsn, 2);
		if (!isset(self::$drivers[$driver])) throw new Exception("Unable to connect to database: Unknown driver '$driver'");
	    
		$class = self::$drivers[$driver];
		if (!load_class($class)) throw new Exception("Unable to create $class object: Class for driver '$driver' does not exist.");
		
		$args = func_get_args();
		if ($args[0] == $driver) array_shift($args);

		return call_user_func_array(array($class, 'connect'), $args);
	}
	
	/**
	 * Reconnect the db connection.
	 */
	abstract public function reconnect();
	
	
	// -----
	
	/**
	 * Get default instance.
	 * Returns a Mock object if interface doesn't exist.
	 * 
	 * @return DB
	 */
	static public final function i()
	{
        if (isset(self::$instances['i'])) return self::$instances['i'];
        return self::getInstance('i');
	}

	/**
	 * Magic method to return specific instance.
	 * Returns a Mock object if interface doesn't exist.
	 *
	 * @param string $name
	 * @param array  $args
	 * @return DB
	 */
	static public function __callstatic($name, $args)
	{
        if (isset(self::$instances[$name])) return self::$instances[$name];
        return self::getInstance($name);
	}	
	
	/**
     * Get specific named instance.
     * Returns a Mock object if instance doesn't exist.
     * 
     * @param string $name
     * @return object|Mock
     */
    public static function getInstance($name)
    {
		if (!isset(self::$instances[$name])) {
		    if (!class_exists('Q\Config') || Config::i() instanceof Mock || !($dsn = Config::i()->get('db' . ($name != 'i' ? ".{$name}" : '')))) {
		    	load_class('Q\Mock');
		    	return new Mock(__CLASS__, $name, 'connect');
		    }
	        self::$instances[$name] = self::connect($dsn);
		}
		
		return self::$instances[$name];
	}	
	
	/**
	 * Register instance.
	 * 
	 * @param string $name
	 */
	public final function asInstance($name)
	{
	    self::$instances[$name] = $this;
	}
	
	
	// -----
	
	/**
	 * Class constructor.
	 *
	 * @param object|resource $native
	 * @param array           $settings  Settings used to create connection
	 */
	public function __construct($native, $settings=array())
	{
		$this->native = $native;
		
		if (isset($settings)) $this->settings = $settings;

		$config = array_chunk_assoc($this->settings, 'config');
		$this->metadata = $config instanceof Config ? $config : Config::with($config, array('mapkey'=>array('table_def'=>"'#table'", 'field'=>'@name', 'alias'=>"'#alias:'.@name")));

		if (isset($settings['metadata-cache'])) $this->metadataCache = $settings['metadata-cache'];
		
		if (isset($settings['log']) && load_class('Q\Log')) $this->log = $settings['log'] instanceof Logger ? $settings['log'] : Log::with($settings['log']);
		  elseif (class_exists('Q\Log') && Log::db()->exists()) $this->log = Log::db();
		if (isset($settings['log-columns'])) $this->logColumns = is_string($settings['log-columns']) ? split_set(',', $settings['log-columns']) : (array)$settings['log-columns'];
	}
	
	/**
	 * Class destructor: close db connection.
	 */
	public function __destruct()
	{
		$this->closeConnection();
	}
	
	/**
	 * Close database connection.
	 */
	abstract public function closeConnection();
	
	/**
	 * Return the connection string (without additional settings).
	 * 
	 * @return string
	 */
	abstract public function getDSN();

	/**
	 * Alias of Q\DB::getNative().
	 * 
	 * @return object|resource
	 */
	final public function getConnection()
	{
		return $this->getNative();
	}
	
	/**
	 * Get the native object or resource.
	 *
	 * @return object|resource
	 */
	public function getNative()
	{
		return $this->native;
	}

	/**
	 * Get the settings used to create connection.
	 * 
	 * @return array
	 */
	public function getSettings()
	{
		return $this->settings;
	}	
	
	/**
	 * Set the result type for Q\DB::fetchAll() and Q\DB::fetchRow()  
	 * 
	 * @param int $resulttype
	 */
	public function setFetchMode($resulttype)
	{
		$this->fetchMode = $resulttype;
	}
	
	// -----

	/**
	 * Retrieve the version of the DB server.
	 * @return string
	 */
	abstract public function getServerVersion();
	
	/**
	 * Get the database (schema) name.
	 * 
	 * @return string
	 */
	abstract public function getDBName();
	
	/**
	 * Get a list of all the tables in the DB.
	 * 
	 * @return array
	 */
	abstract public function getTableNames();
	
	/**
	 * Get the field names of a table.
	 *
	 * @param string $table
	 * @return array
	 */
	abstract public function getFieldNames($table);

	/**
	 * Return the fieldname(s) of the primairy key.
	 *
	 * @param string $table
	 * @param bool   $autoIncrementOnly   Only return fields with the autoincrement feature
	 * @param bool   $asIdentifier        Add table and quote
	 * @return string|array
	 */
	abstract public function getPrimaryKey($table, $autoIncrementOnly=false, $asIdentifier=false);
		
	/**
	 * Get status information about a table.
	 *
	 * @param string $table
	 * @return array
	 */
	abstract public function getTableInfo($table);	
	
	/**
	 * Get properties for $table from the database.
	 * {@internal Make sure the field order is correct. Add '#table' as last element.}}
	 *
	 * @param string $table
	 * @return array
	 */
	abstract protected function fetchMetadata($table);

	/**
	 * Get properties of table.
	 *
	 * @param string $table
	 * @param int    $flags   Optional Fs::RECALC
	 * @return array
	 */
	public function &getMetadata($table, $flags=0)
	{
		if ($this->metadataCache) {
			if (!($this->metadataCache instanceof Cache)) {
        		load_class('Q\Cache');
				$this->metadataCache = Cache::with($this->cache);
			}
			if (~$flags & self::RECALC) $properties = $this->metadataCache->get($table);
			if (isset($properties)) return $properties;
		}
		
		$properties = $this->fetchMetadata($table);
		if (!isset($properties)) return null;
		
		$properties['#table']['name'] = $table;
		
		if ($this->metadataConfig) {
			$props_cfg = $this->metadataConfig[$table];
	    	if (!isset($props_cfg)) $props_cfg = array();
	    	if (!is_array($props_cfg)) throw new Exception("Invalid metadata from config for table '$table'. Should be an array with table and field properties, but is '$props_cfg'.");
	    	
	    	if (!isset($props_cfg['#table']) && isset($props_cfg['table_def'])) {
	    	    $props_cfg['#table'] =& $props_cfg['table_def'];
	    	    unset($props_cfg['table_def']);
	    	}
	
	    	if (isset($props_cfg['#table']) && is_scalar($props_cfg['#table'])) {
	    	    trigger_error("Invalid metadata from config for table properties of '$table'. Should be an array with properties, but is '{$props_cfg['#table']}'. Using default table properties only.", E_USER_WARNING);
	    	    $props_cfg['#table'] = array();
	    	}
		} else {
			$props_cfg = array();
		}
		
		$this->mergeProperties($properties, $props_cfg);
		
		if ($this->metadataCache) $this->metadataCache->set($table, $properties);
		return $properties;
	}
	
	/**
	 * Merge table properties from database with those from the config and the default props
	 * 
	 * @param array $properties
	 * @param array $props_cfg
	 */
	protected function mergeProperties(&$properties, &$props_cfg)
	{	
        if (!empty($props_cfg['#table'])) $properties['#table'] = $props_cfg['#table'] + $properties['#table'];
		$this->applyTableDefaults($properties);
        
		// Merge table properties
		if (!empty($properties['#table']['inherit'])) {
		    $inherit = $this->getMetadata($properties['#table']['inherit']);
		    if (isset($inherit['#role:id'])) {
		        unset($inherit['#role:id']['role'][array_search('id', $inherit['#role:id']['role'])], $inherit['#role:id']['is_primary'], $inherit['#role:id']);
		    } else {
		        $props = null;
		        foreach ($inherit as &$props) {
		            if (isset($props['is_primary'])) unset($props['is_primary']);
		        }
		    }
		    
		    $properties['#table'] += $inherit['#table'];
		}
		
		$is_juntion = true;
		
		// Merge field properties
		foreach (array_unique(array_merge(array_keys($properties), array_keys($props_cfg))) as $index) {
		    if ($index == '#table') continue;
		    
		    if (!isset($properties[$index])) $properties[$index] = array();
		    
        	if (isset($props_cfg[$index]) && !is_array($props_cfg[$index])) {
        	    trigger_error("Invalid metadata from config for field properties of '{$props_cfg['name']}.{$index}'. Should be an array with properties, but is '{$props_cfg[$index]}'. Using default table properties only.", E_USER_WARNING);
        	    $props_cfg[$index] = array();
        	}
		    
		    unset($props_cfg[$index]['name'], $props_cfg[$index]['name_db'], $props_cfg[$index]['table']);
		    if (isset($props_cfg[$index])) $properties[$index] = $props_cfg[$index] + $properties[$index];
		    if (isset($inherit[$index])) $properties[$index] += $inherit[$index];
			
		    $props =& $properties[$index];
		    
		    if (isset($props['name'])) $props['name_db'] = $props['name']; 
			if (isset($props['table'])) $props['table_db'] = $props['table'];
		    $props['name'] = $index;
			$props['table_def'] = $props_cfg['name'];

			$this->applyFieldDefaults($properties, $index);
			$is_juntion = $is_juntion && !empty($props['foreign_table']);
		}
		
		// Check if table is actually a junction table (only if not explicitly set)
		if ($is_juntion && $properties['#table']['role'] != 'junction') {
			foreach ($properties as $name=>&$props) {
				if ($name == '#table') continue;
				if (!empty($props['role']) && in_array('parentkey', (array)$props['role'])) {
					 $properties['#table']['role'] = 'junction';
				}
			}
		}
    }
    
	/**
	 * Apply defaults to table properties.
	 * (Don't call this outside of Q\DB classes)
	 *
	 * @param array $properties  Table properties
	 */
	static public function applyTableDefaults(&$properties)
	{
        if (!isset(self::$fnApplyFieldDefaults) && self::$functionCache) {
        	if (!(self::$functionCache instanceof Cache)) {
        		load_class('Q\Cache');
        		self::$functionCache = Cache::with(self::$functionCache, array('none-is-ok'=>true));
        	}
        	
        	self::$fnApplyTableDefaults = self::$functionCache->get('QDB-fnApplyTableDefaults');
        	self::$dtpUsedForFunction = self::$functionCache->get('QDB-dtpUsedForFunction');
        }
		
        if (self::$defaultTableProperties !== self::$dtpUsedForFunction) {
            if (empty(self::$defaultTableProperties)) {
                self::$fnApplyTableDefaults = false;
            } else {
                $code = self::generateCode_ApplyDefaultProperties('Table', '"#table"');
                self::$fnApplyTableDefaults = create_function('&$properties', $code);
            }
            
            self::$dtpUsedForFunction = self::$defaultTableProperties;
        }
        
        if (empty($properties['#table']['description'])) $properties['#table']['description'] = ucfirst(trim(preg_replace("/[^\w_]*_\W*+/", " ", $properties['#table']['name'])));        
        if (!self::$fnApplyTableDefaults) return;

        $fn = self::$fnApplyTableDefaults;
        $fn($properties);
	}

	/**
	 * Apply defaults to field properties.
	 * (Don't call this outside of Q\DB classes)
	 *
	 * @param array  $properties  Table properties or field properties
	 * @param string $index       Fieldname (when table properties are specified)
	 */
	static public function applyFieldDefaults(&$properties, $index=null)
	{
        if (!isset(self::$fnApplyFieldDefaults) && self::$functionCache) {
        	if (!(self::$functionCache instanceof Cache)) {
        		load_class('Q\Cache');
        		self::$functionCache = Cache::with(self::$functionCache, array('none-is-ok'=>true));
        	}
        	
        	self::$fnApplyFieldDefaults = self::$functionCache->get('QDB-fnApplyFieldDefaults');
        	self::$dfpUsedForFunction = self::$functionCache->get('QDB-dfpUsedForFunction');
        }
		
        if (self::$defaultFieldProperties !== self::$dfpUsedForFunction) {
            if (empty(self::$defaultFieldProperties)) {
                self::$fnApplyFieldDefaults = false;
            } else {
                $code = self::generateCode_ApplyDefaultProperties('Field', '$index');
                self::$fnApplyFieldDefaults = create_function('&$properties, $index', $code);
            }
            
            self::$dfpUsedForFunction = self::$defaultFieldProperties;
        }
        
        if (isset($index)) $p =& $properties;
          else $p[null] =& $properties;

		if (empty($p[$index]['datatype'])) $p[$index]['datatype'] = $p[$index]['type'];
        
        if (!empty(self::$fnApplyFieldDefaults)) {
            $fn = self::$fnApplyFieldDefaults;
            $fn($p, $index);
        }
        
        if (empty($p[$index]['description'])) $p[$index]['description'] = ucfirst(str_replace("_", " ", !empty($p[$index]['orm']) ? $p[$index]['orm'] : $p[$index]['name']));
        if (empty($p[$index]['caption'])) $p[$index]['caption'] = $p[$index]['description'];
	}
	
	/**
	 * Create code for default properties.
	 *
	 * @param string $type
	 * @param string $key
	 */
	static protected function generateCode_ApplyDefaultProperties($type, $type_key)
	{
        $code = '';
        $count = 0;
        
        $forceType = "forceType{$type}Properties";
		foreach (self::$$forceType as $p=>$t) {
		    $p = '"' . addcslashes($p, '"$') . '"';
		    if ($t == 'array') $code .= 'if (isset($properties[' . $type_key . '][' . $p . ']) && is_scalar($properties[' . $type_key . '][' . $p . '])) $properties[' . $type_key . '][' . $p . '] = Q\split_set(";", $properties[' . $type_key . '][' . $p . ']);' . "\n";
		      else $code .= 'if (isset($properties[' . $type_key . '][' . $p . '])) settype($properties[' . $type_key . '][' . $p . '], "' . addcslashes($t, '"$') . '");' . "\n";
		}
        
        foreach (self::${"default{$type}Properties"} as $option_key=>$options) {
            $option_key = addcslashes($option_key, '"$');
            
            foreach ($options as $option=>$values) {
                $values_quoted = array();
                $applysimple = true;
                $apply = "";
                
                $count = 0;
                $option = preg_replace('/(?<!\\\\)(\\\\{2})*+{?\$table\.([^}\s\']+)}?/', '$1{$properties["#table"]["$2"]}', $option, -1, $count);
                
                foreach ($values as $key=>$value) {
                    $count = 0;
                    if (is_string($value) && strpos($value, '$') !== false) {
                        $value = preg_replace(array('/(?<!\\\\)(\\\\{2})*+{?\$table\.([^}\s\']+)}?/', '/(?<!\\\\)(\\\\{2})*+{?\$([a..z][^}\s\']*)}?/i', '/(?<!\\\\)(\\\\{2})*+{?\$(\d+)}?/'), array('$1{$properties["#table"]["$2"]}', '$1{$properties[' . $type_key . ']["$2"]}', '$1{\$matches[$2]}'), $value, -1, $count);
                    }
                    
                    $key = addcslashes($key, '"$');
                    $var_value = is_string($value) ? '"' . str_replace('"' , '\'', $value) . '"' : var_give($value, true);
                    if (is_array($value)) $apply .= '$properties[' . $type_key . ']["' . $key . '"] = empty($properties[' . $type_key . ']["' . $key . '"]) ? ' . $var_value .  ' : array_unique(array_merge($properties[' . $type_key . ']["' . $key . '"], $var_value));' . "\n";
                      else $apply .= 'if (!isset($properties[' . $type_key . ']["' . $key . '"])) $properties[' . $type_key . ']["' . $key . '"] = ' . $var_value . ';' . "\n";                      
                    
                    $applysimple = $applysimple && $count==0 && $key[0] !== '+';
                    if ($applysimple && is_string($value) && strpos($value, '\$') !== 0) $values[$key] = str_replace('\$', '', $value);
                }
                
                if ($applysimple) $apply = "\$properties[$type_key] += " . var_give($values, true) . ';' . "\n";
                  else $apply = "{\n{$apply}}\n";
                
                if ($option[0] == '/') $code .= 'if (isset($properties[' . $type_key . ']["' . $option_key . '"]) && preg_match("' . str_replace('"', '\'', $option) . '", $properties[' . $type_key . ']["' . $option_key . '"], $matches)) ' . $apply;
                  else $code .= 'if (isset($properties[' . $type_key . ']["' . $option_key . '"]) && (is_array($properties[' . $type_key . ']["' . $option_key . '"]) ? in_array("' . str_replace('"', '\'', $option) . '", $properties[' . $type_key . ']["' . $option_key . '"]) : $properties[' . $type_key . ']["' . $option_key . '"] === "' . str_replace('"', '\'', $option) . '")) ' . $apply;
            }
        }
	    
        return $code;
	}
	
	/**
	 * Return a table definition.
	 * 
	 * @param string $table
	 * @param int    $flags  Optional DB::REFRESH
	 * @return DB_Table
	 */
	public function table($table, $flags=0)
	{
		if ($table === null) return new DB_Table($this, array());
	    if (isset($this->tables[$table])) return $this->tables[$table];
	    
	    $props = $this->getMetadata($table);
	    if (empty($props)) throw new Exception("Table '$table' does not exist");

		$this->tables[$table] = new DB_Table($this, $props);
		return $this->tables[$table];
	}
	
	// -----
	
	/**
	 * Quote a value so it can be savely used in a query.
	 *
	 * @param mixed  $value
	 * @param string $type   Force SQL type
	 * @param string $empty  Return $empty if $value is null
	 * @return string
	 */
	abstract public function quote($value, $type=null, $empty=null);

	/**
	 * Quotes a string so it can be safely used as a schema, table or field name.
	 * 
	 * @param string $identifier
	 * @return string Return NULL if $identifier is not valid.
	 */
	abstract public function quoteIdentifier($identifier);
	
	/**
	 * Parse single argument into a statement.
	 * 
	 * @param mixed  $statement  String or query object
	 * @param mixed  $value
	 * @param string $type       Force SQL type (not supported)
	 * @return mixed
	 */
	final public function quoteInto($statement, $value, $type=null)
	{
		return $this->parse($statement, array($value));
	}
	
	/**
	 * Parse arguments into a statement
	 *
	 * @param mixed $statement  String or query object
	 * @param array $args       Arguments to parse into statement on ?
	 * @return mixed
	 */
	abstract public function parse($statement, $args);

	
	/**
	 * Prepare a statement for execution.
	 * {@internal 1st argument might be the source (Q\Table), in that case the 2nd argument is the statement.}}
	 *
	 * @param string $statement
	 * @return DB_Statement
	 */
	abstract public function statement($statement);
	
	/**
	 * Build a select query statement.
	 * @internal If $fields is an array, $fields[0] may be a SELECT statement and the other elements are additional fields
	 *
	 * @param string $table     Tablename
	 * @param mixed  $fields    Array with fieldnames or fieldlist (string); NULL means all fields.
	 * @param mixed  $criteria  The value for the primairy key (int/string or array(value, ...)) or array(field=>value, ...)
	 * @return DB_Statement
	 */
	abstract public function select($table=null, $fields=null, $criteria=null);
	
	/**
	 * Build an insert or insert/update query statement.
	 *
	 * @param string $table   Table
	 * @param array  $colums  Assosiated array as (fielname=>value, ...) or ordered array (fielname, ...) with 1 value for each field
	 * @param array  $values  Ordered array (value, ...) for one row  
	 * @param Addition arrays as additional values (rows)
	 * @return DB_Statement
	 */
	abstract public function store($table=null, $columns=null, $values=null);
	
	/**
	 * Build a update query statement.
	 *
	 * @param string $table   Table
	 * @param array  $values  Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @return DB_Statement
	 */
	abstract public function update($table=null, $values=null);

	/**
	 * Build a delete query statement.
	 *
	 * @param string $table  Table
	 * @return DB_Statement
	 */
	abstract public function delete($table=null);
	
	// -----	
	
	/**
	 * Start database transaction.
	 */
	abstract public function beginTransaction();

	/**
	 * Commit changes made in the current transaction.
	 */
	abstract public function commit();

	/**
	 * Discard changes made in the current transaction.
	 */
	abstract public function rollBack();
	
	
	/**
	 * Load a record (from global space).
	 * 
	 * @return DB_Record
	 */
	abstract public function load($id, $resulttype=DB::FETCH_RECORD);
	
	/**
	 * Excecute a query statement.
	 * Returns DB_Result for select queries, new id for store queries and TRUE for other
	 * 
	 * @param string $statement  Query statement
	 * @param array  $args       Arguments to be parsed into the query on placeholders
	 * @return DB_Result
	 * 
	 * @throws DB_QueryException if query fails
	 */
	abstract public function query($statement, $args=null);
	
	/**
	 * Gets the number of affected rows in a previous operation.
	 * 
	 * @return int
	 */
	abstract public function affectedRows();
}

if (defined('Q_DB_ONLOAD')) include Q_DB_ONLOAD;
