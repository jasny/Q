<?php
namespace Q;

// DB exceptions are defined below
require_once 'Q/Exception.php';
require_once 'Q/SecurityException.php';
require_once 'Q/DB/Exception.php';
require_once 'Q/DB/ConstraintException.php';

require_once 'Q/Cache.php';
require_once 'Q/Config.php';
require_once 'Q/DB/Result.php';
require_once 'Q/DB/Table.php';

/**
 * A database abstraction layer, adding functionality as well as abstraction.
 *
 * To create a connection use Q\DB::connect(SETTINGS)->makeInstance() or set 'db' configuration settings.
 * @example Q\DB::connect('mysql', 'localhost', 'mydbuser', 'mypwd')->makeInstance();
 * @example Q\DB::connect('mysql:host=localhost;user=mydbuser;password=mypwd')->makeInstance();
 * 
 * To use additional meta data, use the setting 'config'. The settings can be split out using a dot.
 * @example Q\DB::connect('mysql:host=localhost;config.driver=yaml;config.path=path/to/dbconfig')->makeInstance();
 * 
 * For debugging purposes it can be useful to log database queries (to file, output, FireBug).
 * For this you can use the setting 'log' and 'log-columns'.
 * @example Q\DB::connect('mysql:host=localhost;log="firephp + file:/tmp/myqueries.log"')->makeInstance();
 * 
 * {@internal A child class should have a $classes property (see Q\DB_MySQL as example).}} 
 * 
 * @package DB
 * 
 * @todo For DB_QueryException: Don't stick query in message, but save in seperate property.
 * @todo Using vars in array for default values should work.  
 * @todo Do the auto:role thing automaticly for all mapping properties.
 */
abstract class DB
{
	/* Fetch methods */
	const FETCH_ORDERED = 3;
	const FETCH_ASSOC = 2;
	const FETCH_FULLARRAY = 4;
	const FETCH_PERTABLE = 32;
	const FETCH_VALUE = 33;
	const FETCH_RECORD = 34;
	const FETCH_ROLES = 35;
	const FETCH_OBJECT = 5;
	const FETCH_NON_RECURSIVE = 0x100;
	
	/* Field name format */
	const FIELDNAME_COL = 0;
	const FIELDNAME_FULL = 1;
	const FIELDNAME_DB = 2;
	const FIELDNAME_DBFULL = 3;
		
	/* Edit statement options */
	const ADD_REPLACE = 0x1;
	const ADD_PREPEND = 0x2;
	const ADD_APPEND = 0x4;
	const ADD_HAVING = 0x100;
	
	/* Single constraint */
	const SINGLE_ROW = 1;
	const MULTIPLE_ROWS = 2;
	const ALL_ROWS = 3;

	/* Special keys for add/replace part */
    const COMMAND_PART = 0;
	
    
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
	 * Automaticly create a mapping link, if field contains a properties in this list.
	 * If the first character is a '~', it means that if the table of field isn't the basetable, the property will not be mapped.
	 * 
	 * @var array
	 */
	static public $mappingProperties = array(
	  'role',
	  '~orm'
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
	 * @var Q\Config
	 */
	protected $metaData;

	/**
	 * Cached table objects.
	 * @var array
	 */
	protected $tables=array();
	
	/**
	 * Fetch mode.
	 * @var int
	 */
	protected $fetchMode=self::FETCH_ASSOC;
	
	
	/**
	 * Log queries and other actions.
	 * Using DB logging is bad for performance, so for debugging purpose only.
	 * 
	 * @var Q\Log_Handler
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
	 * 
	 * @return DB
	 */
	static public final function i()
	{
        if (isset(self::$instances['i'])) return self::$instances['i'];
        return self::__callstatic('i', array());
	}
	
	/**
	 * Magic method to return specific instance.
	 *
	 * @param string $name
	 * @param array  $args
	 * @return DB
	 */
	static public function __callstatic($name, $args)
	{
		if (!isset(self::$instances[$name])) {
		    if (!class_exists('Q\Config') || !Config::i()->exists() || !($dsn = Config::i()->get('db' . ($name != 'i' ? ".{$name}" : '')))) return new DB_Mock($name);
	        self::$instances[$name] = self::connect($dsn);
		}
		
		return self::$instances[$name];
    }	
	
	/**
	 * Check if instance exists.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public final function exists()
	{
	    return true;
	}
	
	/**
	 * Register instance.
	 * 
	 * @param string $name
	 */
	public final function useFor($name)
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
		$this->metaData = $config instanceof Config ? $config : Config::with($config, array('mapkey'=>array('table_def'=>"'#table'", 'field'=>'@name', 'alias'=>"'#alias:'.@name")));

		if (isset($this->settings['log']) && load_class('Q\Log')) $this->log = $this->settings['log'] instanceof Log_Handler ? $this->settings['log'] : Log::with($this->settings['log']);
		  elseif (class_exists('Q\Log') && Log::db()->exists()) $this->log = Log::db();
		if (isset($this->settings['log-columns'])) $this->logColumns = is_string($this->settings['log-columns']) ? split_set(',', $this->settings['log-columns']) : (array)$this->settings['log-columns'];
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
	abstract protected function fetchMetaData($table);

	/**
	 * Get properties for $table.
	 * (Don't call this outside of Q\DB classes)
	 *
	 * @param string $table
	 * @return array
	 */
	public function &getMetaData($table)
	{
	    // Get from cache if possible
		if ($this->metaData->isCached($table)) {
		    $properties = $this->metaData->get($table);
		    if (isset($properties['#table']['name'])) return $properties;
		}

		// Lookup properties
		$props_cfg = $this->metaData->get($table);
    	if (!isset($props_cfg)) $props_cfg = array();
    	if (!is_array($props_cfg)) throw new Exception("Invalid metadata from config for table '$table'. Should be an array with table and field properties, but is '$props_cfg'.");
    	
    	if (!isset($props_cfg['#table']) && isset($props_cfg['table_def'])) {
    	    $props_cfg['#table'] = $props_cfg['table_def'];
    	    unset($props_cfg['table_def']);
    	}

    	if (isset($props_cfg['#table']) && !is_array($props_cfg['#table'])) {
    	    trigger_error("Invalid metadata from config for table properties of '$table'. Should be an array with properties, but is '{$props_cfg['#table']}'. Using default table properties only.", E_USER_WARNING);
    	    $props_cfg['#table'] = array();
    	}

    	$props_cfg['#table']['name'] = $table;
    	$dbtable = empty($props_cfg['#table']['table']) ? $table : $props_cfg['#table']['table'];
		$properties = $this->fetchMetaData($dbtable);
		
		if (empty($properties) && empty($props_cfg)) return null;
    	
		$inherit = null;
		$this->mergeTableProperties($properties, $props_cfg, $inherit);
		$this->mergeFieldProperties($properties, $props_cfg, $inherit);
        $this->setImplicitProperties($properties);

		$this->metaData->set($table, $properties);
		return $properties;
	}
	
	/**
	 * Clear cached metadata.
	 */
	public function clearMetaDataCache($table=null)
	{
	    $this->metaData->clearCache($table);
	}
	
	/**
	 * Merge table properties from database with those from the config and the default props
	 * 
	 * @param array $properties
	 * @param array $props_cfg
	 * @param array $inherit     Output: Inherited properties
	 */
	protected function mergeTableProperties(&$properties, &$props_cfg, &$inherit)
	{	
		// Set properties for table
        $properties['#table'] = $props_cfg['#table'] + (isset($properties['#table']) ? $properties['#table'] : array());

		$this->applyTableDefaults($properties);
        
		if (!empty($properties['#table']['inherit'])) {
		    $inherit = $this->getMetaData($properties['#table']['inherit']);
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
	}
	
	/**
	 * Merge field properties from database with those from the config and the default props
	 * 
	 * @param array $properties
	 * @param array $props_cfg
	 * @param array $inherit
	 */
	protected function mergeFieldProperties(&$properties, &$props_cfg, &$inherit)
	{
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

			// Apply field defaults and apply symantic mapping
			$this->applyFieldDefaults($properties, $index);
			
			if ($props['type'] == 'string' && !isset($props['#role:description']) && (empty($props['role']) || !in_array('description', $props['role']))) {
			    $props['role'][] = 'description';
			    $props['auto:role:description'] = true;
			}
			$this->applyMapping($properties, $index);
		}
    }

    /**
     * Set properties that implicitly follow out of other properties.
     * 
     * @param array $properties
     */
    public function setImplicitProperties(&$properties)
    {}
    
	/**
	 * Apply defaults to table properties.
	 * (Don't call this outside of Q\DB classes)
	 *
	 * @param array $properties  Table properties
	 */
	static public function applyTableDefaults(&$properties)
	{
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
	 * Apply symantic mapping.
	 *
	 * @param array $properties
	 * @param string $index
	 */
	protected function applyMapping(&$properties, $index)
	{
	    $props =& $properties[$index];
	    
		// Add field mapping based on properties (like role)
		foreach (self::$mappingProperties as $mp) {
		    if ($mp[0] == '~') $mp[0] = substr($mp, 1);
			if (empty($props[$mp]) || $props[$mp] === '0') continue;
			
			if (is_scalar($props[$mp]) && (int)$props[$mp] === 1) {
			    if (empty($props["auto:$mp"]) && !empty($properties["#$mp"]["auto:$mp"])) {
			        unset($properties["#$mp"][$mp]);
			        unset($properties["#$mp"]);
			    }
			    
			    if (!isset($properties["#$mp"])) $properties["#$mp"] =& $props;
				  elseif (!empty($props["auto:$mp"])) unset($props[$mp]);
			      else trigger_error(ucfirst($mp). " is defined for field '" . $properties["#$mp"]['name'] . "' as well as '$index'. Should be unique. Please change config file.", E_USER_NOTICE);
				  
			} elseif (!is_array($props[$mp])) {
			    $mv = $props[$mp];
			    if (empty($props["auto:$mp:$mv"]) && !empty($properties["#$mp:$mv"]["auto:$mp:$mv"])) {
			        unset($properties["#$mp:$mv"][$mp]);
			        unset($properties["#$mp:$mv"]);
			    }
			    
				if (!isset($properties["#$mp:$mv"])) $properties["#$mp:$mv"] =& $props;
				  elseif (!empty($props["auto:$mp:$mv"])) unset($props[$mp]);
				  else trigger_error(ucfirst($mp). " '$mv' is defined for field '" . $properties["#$mp:$mv"]['name'] . "' as well as '$index'. Should be unique. Please change config file.", E_USER_NOTICE);
				  
			} else {
			    foreach ($props[$mp] as $mv) {
				    if (empty($props["auto:$mp:$mv"]) && !empty($properties["#$mp:$mv"]["auto:$mp:$mv"])) {
				        unset($properties["#$mp:$mv"][$mp][array_search($mv, $properties["#$mp:$mv"][$mp])]);
				        unset($properties["#$mp:$mv"]);
				    }
				    
					if (!isset($properties["#$mp:$mv"])) $properties["#$mp:$mv"] =& $props;
					  elseif (!empty($props["auto:$mp:$mv"])) unset($props[$mp][array_search($mv, $props[$mp])]);
					  else trigger_error(ucfirst($mp). " '$mv' is defined for field '" . $properties["#$mp:$mv"]['name'] . "' as well as '$index'. Should be unique. Please change config file.", E_USER_NOTICE);
				}
			}
		}
	}
	
	/**
	 * Return a table definition.
	 * 
	 * @param string $table
	 * @return DB_Table
	 */
	public function table($table)
	{
	    if (isset($this->tables[$table])) return $this->tables[$table];
	    
	    if ($table === null) {
	        $props = array();
	    } else {
		    $props =& $this->getMetaData($table);
		    if (empty($props)) throw new Exception("Table '$table' does not exist");
	    }

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
	 * @internal 1st argument might be the source (Q\Table), in that case the 2nd argument is the statement.
	 *
	 * @param string $statement
	 * @return DB_Statement
	 */
	abstract public function prepare($statement);

	/**
	 * Build a select query statement.
	 * @internal If $fields is an array, $fields[0] may be a SELECT statement and the other elements are additional fields
	 *
	 * @param string $table     Tablename
	 * @param mixed  $fields    Array with fieldnames, fieldlist (string) or SELECT statement (string). NULL means all fields.
	 * @param mixed  $criteria  The value for the primairy key (int/string or array(value, ...)) or array(field=>value, ...)
	 * @param string $where     Additional criteria as string
	 * @return DB_Statement
	 */
	abstract public function prepareSelect($table=null, $fields=null, $criteria=null, $where=null);

	/**
	 * Alias of Q\DB_Statement::prepareSelect().
	 *
	 * @param string $table     Tablename
	 * @param mixed  $fields    Array with fieldnames, fieldlist (string) or SELECT statement (string). NULL means all fields.
	 * @param mixed  $criteria  The value for the primairy key (int/string or array(value, ...)) or array(field=>value, ...)
	 * @param string $where     Additional criteria as string
	 * @return DB_Statement
	 */
	final public function select($table=null, $fields=null, $criteria=null, $where=null)
	{
		return $this->prepareSelect($table, $fields, $criteria, $where);
	}
	
	/**
	 * Build an insert or insert/update query statement.
	 *
	 * @param string $table   Tablename
	 * @param array  $values  Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @param Give additional arguments (arrays) to insert/update multiple rows. $value should be array(fieldname, ...) instead. U can also use Q\DB::args(values, $rows).
	 * @return DB_Statement
	 * 
	 * @throws Q\DB_Constraint_Exception when no rows are given.
	 */
	abstract public function prepareStore($table=null, $values=null);
	
	/**
	 * Build a update query statement.
	 *
	 * @param string $table   Tablename
	 * @param mixed  $id      The value for a primairy (or as array(value, ..) if multiple key fields) or array(field=>value, ...)
	 * @param array  $values  Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @return DB_Statement
	 */
	abstract public function prepareUpdate($table=null, $id=null, $values=null);

	/**
	 * Build a delete query statement.
	 *
	 * @param string $table  Tablename
	 * @param mixed  $id     The value for a primairy (or as array(value, ..) if multiple key fields) or array(field=>value, ...)
	 * @return DB_Statement
	 */
	abstract public function prepareDelete($table=null, $id=null);
	
	
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
	 * Excecute a query statement.
	 * Returns DB_Result for 'SELECT', 'SHOW', etc queries, returns new id for 'INSERT' query, returns TRUE for other
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Arguments to be parsed into the query on placeholders
	 * @return DB_Result
	 */
	abstract public function query($statement, $args=null);
	
	/**
	 * Gets the number of affected rows in a previous operation.
	 * 
	 * @return int
	 */
	abstract public function affectedRows();
	
	
	/**
	 * Select a single value from a table.
	 * 
	 * @param string $table      Table name
	 * @param mixed  $fieldname  The fieldname for the column to fetch the value from. CAUTION: the fieldname is *not* quoted!
	 * @param mixed  $id         The value for a primairy (or as array(key, ..) if multiple key fields ) or array(field=>value, ...)
	 * @return mixed
	 * 
	 * @throws DB_Constraint_Exception if query results in > 1 record
	 */
	abstract public function lookupValue($table, $fieldname, $id);

	/**
	 * Count the number of rows in a table (with the given criteria)
	 * 
	 * @param string $table     Table name
	 * @param mixed  $criteria  The value for a primairy (or as array(key, ..) if multiple key fields ) or array(field=>value, ...)
	 * @return int
	 */
	abstract public function countRows($table, $criteria=null);

	
	/**
	 * Set the result type for Q\DB::fetchAll() and Q\DB::fetchRow()  
	 * 
	 * @param int $resulttype
	 */
	public function setFetchMode($resulttype)
	{
		$this->fetchMode = $resulttype;
	}
	
	/**
	 * Query statement and return the result based on the set fetch type.
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	public function fetchAll($statement, $args=null)
	{
		$ret = $this->query($statement, $args);
		return $ret instanceof DB_Result ? $ret->fetchAll($this->fetchMode) : $ret;
	}

	/**
	 * Query statement and return the result as ordered array
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	public function fetchOrdered($statement, $args=null)
	{
		$ret = $this->query($statement, $args);
		return $ret instanceof DB_Result ? $ret->fetchAll(self::FETCH_ORDERED) : $ret;
	}
	
	/**
	 * Query statement and return the result as associated array
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	public function fetchAssoc($statement, $args=null)
	{
		$ret = $this->query($statement, $args);
		return $ret instanceof DB_Result ? $ret->fetchAll(self::FETCH_ASSOC) : $ret;
	}
	
	/**
	 * Query statement and return the first column
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	public function fetchColumn($statement, $args=null)
	{
		$ret = $this->query($statement, $args);
		return $ret instanceof DB_Result ? $ret->fetchCol() : $ret;
	}
	
	/**
	 * Alias of Q\DB::fetchColum()
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	final public function fetchCol($statement, $args=null)
	{
		return $this->fetchColumn($statement, $args);
	}
	
	/**
	 * Query statement and return the first column as key and the second as value
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	public function fetchPairs($statement, $args=null)
	{
		$ret = $this->query($statement, $args);
		return $ret instanceof DB_Result ? $ret->fetchColumn(1, 0) : $ret;
	}

	/**
	 * Query statement and return the first row based on the set fetch type.
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	public function fetchRow($statement, $args=null)
	{
		$ret = $this->query($statement, $args);
		return $ret instanceof DB_Result ? $ret->fetch($this->fetchMode) : $ret;
	}

	/**
	 * Query statement and return a single value
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	public function fetchValue($statement, $args=null)
	{
		$ret = $this->query($statement, $args);
		return $ret instanceof DB_Result ? $ret->fetchValue() : $ret;
	}

	/**
 	 * Alias of Q/DB::fetchValue()
	 * 
	 * @param mixed $statement  String or query object
	 * @param array $args       Parsed on placeholder
	 * @return array
	 */
	final public function fetchOne($statement, $args=null)
	{
		return $this->fetchValue($statement, $args);
	}
	

	/**
	 * Select a single record from a table.
	 * 
	 * @param string $table       Tablename or 'SELECT' query with one ? (per primairy key field)
	 * @param mixed  $id          The value for a primairy (or as array(key, ..) if multiple key fields ) or array(field=>value, ...)
	 * @param int    $resulttype  Specify how to format the result. A DB::FETCH_% constant
	 * @return array
	 * 
	 * @throws DB_Constraint_Exception if query results in > 1 record
	 */
	abstract public function load($table, $id, $resulttype=DB::FETCH_RECORD);
	
	/**
	 * Alias of Q\DB::store()
	 * Note that if the row can't be inserted because of primairy/unique key constraints (the record already exists), the specific row is updated instead.
	 * 
	 * @param string $table
	 * @param array  $values  Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @param Give additional args to insert/update multiple rows. $value should be array(fieldname, ...) instead.
	 * @return int Returns the last inserted/updated id
	 * 
	 * @throws Q\DB_Constraint_Exception when no rows are given.
	 */
	final public function insert($table, $values)
	{
		$args = func_get_args();
		call_user_func_array(array($this, 'store'), $values);
	}
	
	/**
	 * Inserts or updates rows for a table.
	 * If the row can't be inserted because of primairy/unique key constraints (the record already exists), the specific row is updated instead.
	 * 
	 * Fields for which there is no key in the array, are not affected.
	 * If a record is inserted in a table that has an unique key other than the primary key and the value for the unique key already exists, the record is updated but the id is not changed.
	 * 
	 * @param string $table
	 * @param array  $values  Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @param Give additional args to insert/update multiple rows. $value should be array(fieldname, ...) instead.
	 * @return int  Returns the last inserted/updated id
	 * 
	 * @throws Q\DB_Constraint_Exception when no rows are given.
	 */
	public function store($table, $values)
	{
	    $args = func_get_args();
	    return call_user_func_array(array($this, 'prepareStore'), $args)->execute();
	}

	/**
	 * Update rows of a table.
	 * Fields for which there is no key in the array, are not affected.
	 * 
	 * @param string $table
	 * @param mixed  $id          The value for a primairy (or as array(value, ..) if multiple key fields) or array(field=>value, ...)
	 * @param array  $values      Assasioted array as (fielname=>value, ...) or ordered array (value, ...) with 1 value for each field
	 * @param int    $constraint  Constraint based on the number or rows: SINGLE_ROW, MULTIPLE_ROWS, ALL_ROWS.
	 * @return int  Returns the number of rows affected by the update
	 * 
	 * @throws Q\DB_Constraint_Exception if query results in > 1 record and constraint == SINGLE_ROW
	 */
	public function update($table, $id, $values, $constraint=DB::MULTIPLE_ROWS)
	{
	    if ((int)$constraint == DB::ALL_ROWS && isset($id)) throw new Exception("Update on `$table` failed: Can't use all rows constraint together with id value.");
	    
	    $stmt = $this->prepareUpdate($table, $id, $values);
	    if ($constraint == DB::SINGLE_ROW && $stmt->countRows() > 1) throw new DB_Constraint_Exception("Update on table `$table` failed: Query would affect in multiple records. " . $stmt->getStatement());
	    $stmt->execute();
	    
	    return $this->affectedRows();
	}
	
	/**
	 * Delete a single record or multiple records from a table.
	 * 
	 * @param string $table
	 * @param mixed  $id          The value for a primairy (or as array(value, ..) if multiple key fields) or array(field=>value, ...)
	 * @param int    $constraint  Constraint based on the number or rows: SINGLE_ROW, MULTIPLE_ROWS, ALL_ROWS.
	 * @return int  Returns the number of rows affected by the delete
	 * 
	 * @throws Q\DB_Constraint_Exception if query results in > 1 record and constraint == SINGLE_ROW
	 */
	public function delete($table, $id, $constraint=self::SINGLE_ROW)
	{
        if ((int)$constraint == DB::ALL_ROWS) {
            if (isset($id)) throw new Exception("Truncate table `$table` failed: Can't use all rows constraint together with id value.");
            $id = (object)array('#truncate'=>true);
        }
        
        $stmt = $this->prepareDelete($table, $id);
        if ($constraint == DB::SINGLE_ROW && $stmt->countRows() > 1) throw new DB_Constraint_Exception("Update on table `$table` failed: Query would affect in multiple records. " . $stmt->getStatement());
        $stmt->execute();
        
        return $this->affectedRows();
	}
}


/**
 * Mock object to create DB instance.
 * @ignore 
 */
class DB_Mock
{
    /**
     * Instance name
     * @var string
     */
    protected $_name;
    
    /**
     * Class constructor
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }
    
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
	    $args = func_get_args();
	    $instance = call_user_func_array(array('Q\DB', 'connect'), $args);
	    $instance->useFor($this->_name);
	    
	    return $instance;
    }
    
    
    /**
     * Check if instance exists.
     *
     * @return boolean
     */
    public function exists()
    {
        return false;
    }
    
    /**
     * Magic get method
     *
     * @param string $key
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($key)
    {
        $name = $this->_name;
        if (DB::$name()->exists()) trigger_error("Illigal use of object 'Q\DB mock::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("DB interface '{$this->_name}' does not exist.");
    }

    /**
     * Magic set method
     *
     * @param string $key
     * @param mixed  $value
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __set($key, $value)
    {
        $name = $this->_name;
        if (DB::$name()->exists()) trigger_error("Illigal use of mock object 'Q\DB mock::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("DB interface '{$this->_name}' does not exist.");
    }
    
    /**
     * Magic call method
     *
     * @param string $function
     * @param array  $args
     * 
     * @throws Q\Exception because this means that the instance is used, but does not exist.  
     */
    public function __call($function, $args)
    {
        $name = $this->_name;
        if (DB::$name()->exists()) trigger_error("Illigal use of object 'Q\DB mock::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("DB interface '{$this->_name}' does not exist.");
    }
}

if (defined('Q_DB_ONLOAD')) include Q_DB_ONLOAD;
