<?php
namespace Q;

require_once 'Q/DB.php';
require_once 'Q/DB/MySQL/QuerySplitter.php';
require_once 'Q/DB/SQLStatement.php';

require_once 'Q/DB/MySQL/Result.php';
require_once 'Q/DB/MySQL/Result/Tree.php';
require_once 'Q/DB/MySQL/Result/NestedSet.php';

require_once 'Q/Cache.php';

/**
 * DB abstraction layer for LDAP.
 * 
 * @package    DB
 * @subpackage DB_LDAP
 */
class DB_LDAP extends DB
{
    /**
	 * Native ldap connection object
	 * @var resource
	 */
	protected $native;
	    
	/**
	 * Open a new connection to LDAP server.
	 * @static
	 *
	 * @param string $host      Hostname, hostname:port or DSN
	 * @param string $user      ldap rdn or dn
	 * @param string $password
	 * @param string $base
	 * @param string $port
	 * @param array  $settings  Additional settings
	 * @return DB_MySQL
	 */    
    public function connect($host=null, $user=null, $password=null, $base=null, $port=389, array $settings=array())
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
		if (!empty($settings['ssl'])) $host = "ldaps://$host/";
		if (empty($port)) $port=389;
		
		$native = new ldap_connect($host, $port);
		if (!$native) throw new DB_Exception("Connecting to LDAP server failed: " . ldap_error(), ldap_errno());
		$native = ldap_bind($native, $user, $password);
		
		$settings = compact('host', 'user', 'password', 'base', 'port') + $dsn_settings + $settings;

	    return new self($native, $settings);
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
		
		$class = self::$classes['QuerySplitter'];
		$this->querySplitter = new $class();

		if (isset($this->log)) $this->log->write(array('statement'=>"Connected to {$settings['host']}.", (isset($settings['database']) ? "Using database '{$settings['database']}'." : '')), 'db-connect');
	}

	
	/**
	 * Return the connection string (without additional settings)
	 * 
	 * @return string
	 */
	public function getDSN()
	{
		return 'ldap:' . implode_assoc($this->settings);
	}
	    
    
	/**
	 * Quote a value so it can be savely used in a query.
	 *
	 * @param mixed  $value
	 * @param string $empty  Return $empty if $value is null
	 * @return string
	 */
	public function quote($value, $empty=null)
	{
	    return addcslashes($value, '"+=<>');
	}
	
	/**
	 * Quotes a string so it can be safely used as path.
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function quoteSchema($identifier)
	{
	    return addcslashes($identifier, "<>" );
	}
		
	/**
	 * Quotes a string so it can be safely used as path.
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function quoteTable($identifier)
	{
	    return addcslashes($identifier, "<>" );
	}
	
	/**
	 * Quotes a string so it can be safely used as attribute.
	 * Return NULL if identifier is invalid.
	 *
	 * @param string $identifier
	 * @return string
	 */
	public function quoteField($identifier)
	{
	    return preg_match('/\W_/', $identifier) ? null : $identifier;
	}
}

?>