<?php
namespace Q;

require_once "Q/Crypt.php";
require_once "Q/Cache.php";
require_once "Q/HTTP.php";
require_once "Q/ExpectedException.php";
require_once "Q/Auth/User.php";

/**
 * Perform authentication.
 * Beware! User information stays set, even when user is not logged in. 
 *
 * Will autostart the default interface if configured and AUTH_NO_START is not defined.
 * 
 * @package Auth
 * 
 * @todo Implement session expire (not using session lifetime)
 * @todo Auto login/logout on var=value + multiple options + http arg support
 */
abstract class Auth
{
	/** Result code: no session exists */
	const NO_SESSION = -1;
	/** Result code: no error */
	const OK = 0;
	/** Result code: no username provided */
	const NO_USERNAME = 1;
	/** Result code: no password provided */
	const NO_PASSWORD = 2;
	/** Result code: user not found */
	const UNKNOWN_USER = 3;
	/** Result code: incorrect password */
	const INCORRECT_PASSWORD = 4;
	/** Result code: user is not active */
	const INACTIVE_USER = 5;
	/** Result code: client ip is blocked */
	const HOST_BLOCKED = 6;
	/** Result code: password is expired */
	const PASSWORD_EXPIRED = 7;
	/** Result code: session cecksum was not correct */
	const INVALID_SESSION = 8;	
	/** Result code: session is expired */
	const SESSION_EXPIRED = 9;	
	
	
	/**
	 * Named Auth instances
	 * @var Auth[]
	 */
	protected static $instances;

	/**
	 * Drivers with classname or as array(classname, arg, ...).
	 * @var array
	 */
	static public $drivers = array(
	    'manual'=>'Auth_Manual',
	    'db'=>'Auth_DB'
	);
	
	
	/**
	 * Log attempts.
	 * @var Log
	 */
	public $log;

	/**
	 * Method how the user session is stored and retrieved.
	 * 
	 * Options:
	 *   - none (black hole)
	 *   - session
	 *   - cookie (parameters can be specified as DSN string)
	 *   - request
	 *   - env
	 *   - http (Get user from HTTP authentication, no login/logout)
	 *   - posix (Get system user of current process, no login/logout)
	 *   - posix_username (as posix, but based on username instead of uid)
	 * @var string
	 */
	public $store = 'cookie';
	
	/**
	 * Encryption method to create checksum hash.
	 * Use a crypt method with secret word to make sure the hash is safe, even when a hacker manages to read out the encrypted passwords.
	 * 
	 * @var Crypt
	 */
	public $hashCrypt = 'md5';
	
	/**
	 * Encryption method used to encrypt passwords.
	 * @var Crypt
	 */
	public $passwordCrypt;

	
	/**
	 * If login is required and no user is logged in, display login page.
	 * @var boolean
	 */
	public $loginRequired = false;
    
	/**
	 * Login page, using redirect
	 * @var string
	 */
	public $loginPage;

	/**
	 * Page to change password, using redirect
	 * @var string
	 */
	public $expiredPage;

	/**
	 * Login from any page using $loginRequestVar as GET var or request method.
	 * Uses request var username and password if they exist, otherwise will take the value of the login var.
	 * 
	 * @var string
	 */
	public $loginRequestVar;
    
	/**
	 * Logout from any page using $logoutRequestVar as GET var or request method.
	 * @var string
	 */
	public $logoutRequestVar;


	/**
	 * Number of times before ip adress is blocked.
	 * @var int
	 */
	public $loginAttempts;
	
	/**
	 * Ip adresses that can't be blocked
	 * @var array
	 */
	public $unblockableHosts = array('127.0.0.1');
	
	/**
	 * Cache to store login attempts.
	 * @var Cache
	 */
	public $storeAttemps;
	
	
	/**
	 * Flag to state user is logged in
	 * @var boolean
	 */
	protected $loggedIn = false;
		
	/**
	 * Current user information
	 * @var Auth_User
	 */
	protected $_user;

	
	/**
	 * Set the options.
	 *
	 * @param string|array $dsn  DSN/driver (string) or array(driver[, arg1, ...])
	 * @param 
	 * @return Auth
	 */
	public function with($dsn, $options=array())
	{
	    if (isset($this) && $this instanceof self) throw new Exception("Auth instance is already created.");
	    
	    $options = (!is_string($dsn) ? $dsn : extract_dsn($dsn)) + $options;
	    $driver = $options['driver'];
	    
		if (!isset(self::$drivers[$driver])) throw new Exception("Unable to create Auth object: Unknown driver '$driver'");

		$args = array();
	    $props = array();
	    
		$class_options = (array)self::$drivers[$driver];
	    $class = array_shift($class_options);
		foreach (array_merge($class_options, $options) as $key=>$value) {
		    if (is_int($key)) $args[] = $value;
		      else  $props[$key] = $value;
		}
	    $args += array_fill(0, 5, null);
		
		if (!load_class($class)) throw new Exception("Unable to create $class object: Class does not exist.");
		$object = new $class($args[0], $args[1], $args[2], $args[3], $args[4]); 
	    
		if (!empty($props)) {
		    $reflection = new \ReflectionObject($object);
    	    foreach ($props as $key=>$value) {
    	        if (!$reflection->hasProperty($key) || !$reflection->getProperty($key)->isPublic()) continue;
        		if (is_array($value) && is_array($object->$key)) $object->$key = array_merge($object->$key, $value);
        		  else $object->$key = $value; 
    	    }
		}
		
		return $object;
    }
	
	/**
	 * Magic method to retun specific instance
	 *
	 * @param string $name
	 * @param string $args
	 * @return Config
	 */
	static public function __callstatic($name, $args)
	{
		if (!isset(self::$instances[$name])) {
		    if (!class_exists('Config') || !Config::i()->exists() || !($dsn = Config::i()->get('cache' . (isset($name) ? ".{$name}" : '')))) return new Auth_Mock($name);
	        self::$instances[$name] = self::with($dsn);
		}
	    	    
        return self::$instances[$name];
    }
	
	/**
	 * Check is singeton object exists
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function exists()
	{
	    return true;
	}

	/**
	 * Register instance
	 * 
	 * @param string $name
	 */
	public final function useFor($name)
	{
		self::$instances[$name] = $this;
	}
    
	
	/**
	 * Class constructor
	 */
	public function __construct() {}
	
	/**
	 * Check if a user is loggin in.
	 * 
	 * @return boolean
	 */
	public function isLoggedIn()
	{
		return $this->loggedIn;
	}

	/**
	 * Get current user
	 *
	 * @throws Auth_Session_Exception if no user is logged in
	 * @return Auth_User
	 */
	public function user()
	{
		if (!isset($this->_user)) throw new Auth_Session_Exception("No user is logged in.");
		return $this->_user;
	}
	
	/**
	 * Check if host is blocked.
	 * Returns 0 if unblockable.
	 * 
	 * @param string  $host
	 * @param boolean $attempt  Increment attempts (bool) or attempt (int)
	 * @return boolean
	 */
	public function isBlocked($host=null, $attempt=false)
	{
	    if (empty($this->loginAttempts)) return 0;
	    
        if (!isset($host)) $host = HTTP::clientIP(HTTP::CONNECTED_CLIENT);
        if (empty($host) || in_array($host, $this->unblockableHosts, true)) return 0;
        
        if (!isset($this->storeAttemps)) {
            if (!Cache::hasInstance()) return 0;
            $this->storeAttemps = Cache::i();
        } elseif (!($this->storeAttemps instanceof Cache)) {
            $this->storeAttemps = Cache::with($this->storeAttemps, array('memorycaching'=>true, 'overwrite'=>true));
        }
        
        if (is_bool($attempt)) $attempt = (int)$this->storeAttemps->get("AUTH-login_attempts:$host") + 1;
        if ($attempt) $this->storeAttemps->save("AUTH-login_attempts:$host", $attempt);
          else $this->storeAttemps->remove("AUTH-login_attempts:$host");
        
        return ($this->loginAttempts - $attempt) < 0;
	}
	
	/**
	 * Return a checksum hash to identify the user and session.
	 * 
	 * @param string $salt
	 * @return string
	 */
	public function hash($salt=null)
	{
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    if (!($this->hashCrypt instanceof Crypt)) $this->hashCrypt = Crypt::with($this->hashCrypt);
	    
	    return $this->hashCrypt->encrypt($this->_user->id . $this->_user->password . HTTP::clientRoute() . ($this->store['driver'] == 'session' ? session_id() : null), $salt);
	}
	
	/**
	 * Get AUTH info from session data.
	 * 
	 * @return array
	 */
	protected function getInfo()
	{
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    
	    switch ($this->store['driver']) {
	    	case 'none':			return null;
	        case 'session': 		session_start();
									return isset($_SESSION['AUTH']) ? $_SESSION['AUTH'] : null;
	        case 'cookie':  		return array_chunk_assoc($_COOKIE, 'AUTH', '__');
	        case 'request': 		return isset($_REQUEST['AUTH']) ? $_REQUEST['AUTH'] : null;
	        case 'env':				return array_chunk_assoc($_ENV, 'Q_AUTH', '__');
	        case 'http':			return array('username'=>$_ENV['REMOTE_USER']);
	        case 'posix':			return array('uid'=>posix_getuid());
	        case 'posix_username':	return array('username'=>posix_getlogin());
	        default: throw new Exception("Invalid option '{$this->store['driver']}' specified for retrieving info.");
	    }
	    return null;
	}

	/**
	 * Return if driver is able to store information
	 * 
	 * @return boolean
	 */
	protected function canStoreInfo()
	{
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    
	    switch ($this->store['driver']) {
	    	case 'none':	return true;
	        case 'session': return true;
	        case 'cookie':  return true;
	        case 'request': return true;
	        case 'env':     return true;
	        case 'http':	return false;
	        case 'posix':	return false;
	        default: throw new Exception("Invalid option '{$this->store['driver']}' specified for storing and retrieving info.");
	    }
	    return false;
	}
	
	/**
	 * Store AUTH info to session data.
	 *
	 * @param array $info
	 */
	protected function storeInfo($info)
	{
	    $matches = null;
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    
		switch ($this->store['driver']) {
			case 'none':
				break;
				
	        case 'session':
	            session_start();
	            $_SESSION['AUTH'] = $info;
	            break;
	            
	        case 'cookie':
	            $cookie_params = $this->store + session_get_cookie_params();
	            
	            if (!isset($info)) {
	                $info = array();
	                foreach (array_keys($_COOKIE) as $key) if (preg_match('/^AUTH__(.*)$/', $key, $matches)) $info[$matches[1]] = null;
	                $cookie_params['lifetime'] = 1;
	            }
	            
	            foreach (array_combine_assoc($info, 'AUTH', '__') as $key=>$value) {
	                setcookie($key, $value, ($cookie_params['lifetime'] <= 1 ? 0 : time()) + $cookie_params['lifetime'], $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
	                $_COOKIE[$key] = $value;
	            }
	            break;
	            
	        case 'request':
	            HTTP::output_add_rewrite_var('AUTH', $info);
	            $_REQUEST['AUTH'] = $info;
	            break;
	            
	        case 'env':
	            if (!isset($info)) {
	                $info = array();
	                foreach (array_keys($_ENV) as $key) if (preg_match('/^Q_AUTH__(.*)$/', $key, $matches)) $info[$matches[1]] = null;
	            }
	            
	            foreach (array_combine_assoc($info, 'Q_AUTH', '__') as $key=>$value) {
	                putenv("$key=$value");
	                $_ENV[$key] = $value;
	            }
	            break;
	            
	        default: throw new Exception("Invalid option '{$this->store['driver']}' specified for storing info.");
        }
	}
	
	
	/**
	 * Fetch user based on username.
	 * Returns authentication result
	 *
	 * @param string $username
     * @param string $password
     * @param int    $result      Output: return code
	 * @return Auth_User
	 */
	abstract public function authUser($username, $password, &$result);
	
	/**
	 * Fetch user based on id.
	 *
	 * @param int|string $uid
	 * @return Auth_User
	 */
	abstract public function fetchUser($uid);

	/**
	 * Fetch user based on username.
	 *
	 * @param string $username
	 * @return Auth_User
	 */
	abstract public function fetchUserByName($username);
		
	
	/**
	 * Start Authenticator.
	 * 
	 * @return int
	 * @throws Authenticator_Session_Exception if login is required and auth fails (and login page is not set)
	 */
	public function start()
    {
        if (isset($this->loginRequestVar) && (isset($_GET[$this->loginRequestVar]) || $_SERVER['REQUEST_METHOD'] == $this->loginRequestVar)) {
			if (isset($_REQUEST['username']) || empty($_GET[$this->loginRequestVar]) || $_GET[$this->loginRequestVar] == 1) {
			    $username = isset($_REQUEST['username']) ? $_REQUEST['username'] : null;
			    $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : null;
			} else {
                list($username, $password) = explode(':', $_GET[$this->loginRequestVar]) + array(null, null);
			}
			
            return $this->login($username, $password);
        }
				
        if (isset($this->logoutRequestVar) && (isset($_GET[$this->logoutRequestVar]) || $_SERVER['REQUEST_METHOD'] == $this->logoutRequestVar)) {
            $this->logout();
            return self::NO_SESSION;
        }
		
        $info = $this->getInfo();

        if (isset($info['uid'])) {
        	$this->_user = $this->fetchUser($info['uid']);
        } elseif (isset($info['username'])) {
        	$this->_user = $this->fetchUserByName($info['username']);
        } elseif (!isset($info['uid'])) {
            if ($this->loginRequired) {
                if (!isset($this->loginPage)) throw new Auth_Session_Exception("Login required", self::NO_SESSION);
                HTTP::redirect($this->loginPage);
            }
            return self::NO_SESSION;
        }
        
        if (!$this->_user) $result = self::UNKNOWN_USER;
          elseif ($this->canStoreInfo() && (!isset($info['hash']) || $this->hash($info['hash']) !== $info['hash'])) $result = self::INVALID_SESSION;
          elseif (!$this->_user->active) $result = self::INACTIVE_USER;
          else $result = self::OK;
        
        if ($result != self::OK) {
            $this->logout($result);
        } else {
            $this->loggedIn = true;
            $this->onStart();
        }
        
        return $result;
	}

    /**
     * Auth and start user session.
     * Returns result if login is not required.
     *
     * @param string $username
     * @param string $password
     * @return int
     * 
     * @throws Authenticator_Login_Exception if login fails (and login page is not set)
     */
    public function login($username, $password)
    {
    	if (!$this->canStoreInfo()) throw new Exception("Logging in through PHP is not supported with store option '{$this->store['driver']}'.");
    	
        $this->loggedIn = false;
        $this->_user = null;
        $this->storeInfo(null);
        
        if ($this->isBlocked(null, true)) {
            $result = self::HOST_BLOCKED;
        } elseif (!isset($username)) {
            $result = self::NO_USERNAME;
        } elseif (!isset($password)) {
            $result = self::NO_PASSWORD;
        } else {
            $this->_user = $this->authUser($username, $password, $result);
        }

        $this->logEvent('login', $result);
        
        if ($result != self::OK) {
            if ($result == self::INCORRECT_PASSWORD) $result = self::UNKNOWN_USER; // Preventing dictionary attack
            
            if ($this->loginRequired) {
                $page = $result == self::PASSWORD_EXPIRED ? $this->expiredPage : $this->loginPage;
                if (!isset($page)) throw new Auth_Login_Exception("Login failed: " . $this->getMessage($result), $result);
                HTTP::redirect($page . (strpos($page, '?') === false ? '?' : '&') . "auth_result={$result}&username=" . urlencode($username));
            }
            
            return $result;
        }

        $this->storeInfo(array('uid'=>$this->_user->id, 'hash'=>$this->hash()));
        $this->isBlocked(null, 0);
        
        $this->loggedIn = true;
        $this->onLogin();
        
        return self::OK;
    }

	/**
	 * End user session.
	 * 
	 * @param int $result
	 */
    public function logout($result=0)
    {
    	if (!$this->canStoreInfo()) throw new Exception("Logging out through PHP is not supported with store option '{$this->store['driver']}'.");
    	
    	$this->logEvent('logout', $result);
        
        $username = $this->_user->username;
        
        $this->storeInfo(null);
		$this->onLogout();
        $this->_user = null;
        
        if ($this->loginRequired) {
            if (!isset($this->loginPage)) throw new Auth_Session_Exception("Logout: " . $this->getMessage($result), $result);

            if ($result) $args['auth_result'] = $result;
            if (!empty($username)) $args['username'] = $username;
            HTTP::redirect($this->loginPage . (!empty($args) ? (strpos($this->loginPage, '?') === false ? '?' : '&') . http_build_query($args) : ''));
        }
    }

    /**
     * Actions to perform on successfull session start.
     * To be overwritten by subclass.
     */
    protected function onStart() { }
    
    /**
     * Actions to perform on login.
     * To be overwritten by subclass.
     */
    protected function onLogin() { }
    
    /**
     * Actions to perform on logout.
     * To be overwritten by subclass.
     */
    protected function onLogout() { }

    
    /**
     * Check if the current user is in specific group(s)
     * 
     * @param string $group  Group name, multiple groups may be supplied as array
     * @param Multiple groups may be supplied as additional arguments
     * 
     * @throws Auth_Session_Exception if no user is logged in
     * @throws Authz_Exception if the user is not in one of the groups
     */
    public function authz($group)
    {
    	$groups = is_array($group) ? $group : func_get_args();
    	$this->user()->authz($groups);
    }
    
    
    /**
     * Store event and result in log.
     *
     * @param string $event 
     * @param int    $result   Result code
     */
    protected function logEvent($event, $result=0)
    {
        if (!isset($this->log)) return;
        
        if (!($this->log instanceof Log_Handler)) $this->log = Log::to($this->log);
        
        $msg = ucfirst($event) . ($result == 0 ? ' success' : ' failed: ' . $this->getMessage($result));
        $this->log->write(array('username'=>$this->_user->username, 'host'=>HTTP::clientRoute(), 'message'=>$msg), $event);  
    }
    
    /**
     * Get message for result code.
     *
     * @param int $result  Result code
     * @return string
     */
    public function getMessage($result)
    {
        switch ($result) {
            case self::NO_SESSION:         return "No session";
            case self::OK:                 return "Success";
            case self::NO_USERNAME:        return "No username";
            case self::NO_PASSWORD:        return "No password";
            case self::UNKNOWN_USER:       return "Unknown user";
            case self::INCORRECT_PASSWORD: return "Incorrect password";
            case self::INACTIVE_USER:      return "User inactive";
            case self::HOST_BLOCKED:       return "Host blocked";
	        case self::INVALID_SESSION:    return "Invalid session";	
        	case self::SESSION_EXPIRED:    return "Session expired";
        }
        
        return null;
    }
    
}

/**
 * Mock object to create Auth instance.
 * @ignore 
 */
class Auth_Mock
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
	 * Create a new Auth interface instance.
	 *
	 * @param string|array $dsn      Authuration options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Auth
	 */
	public function with($dsn, $options=array())
	{
	    $instance = Auth::with($dsn, $options);
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
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __get($key)
    {
        $name = $this->_name;
        if (Auth::$name()->exists()) trigger_error("Illigal use of mock object 'Auth::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Auth interface '{$this->_name}' does not exist.");
    }

    /**
     * Magic set method
     *
     * @param string $key
     * @param mixed  $value
     * 
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __set($key, $value)
    {
        $name = $this->_name;
        if (Auth::$name()->exists()) trigger_error("Illigal use of mock object 'Auth::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Auth interface '{$this->_name}' does not exist.");
    }
    
    /**
     * Magic call method
     *
     * @param string $name
     * @param array  $args
     * 
     * @throws Exception because this means that the instance is used, but does not exist.  
     */
    public function __call($function, $args)
    {
        $name = $this->_name;
        if (Auth::$name()->exists()) trigger_error("Illigal use of mock object 'Auth::{$this->_name}()'.", E_USER_ERROR);
        throw new Exception("Auth interface '{$this->_name}' does not exist.");
    }
}

/* --------------- Exceptions ----------------- */

/**
 * Base class for Auth exceptions
 * @package Auth
 */
abstract class Auth_Exception extends \Exception implements \ExpectedException {}

/**
 * Auth login exceptions
 * @package Auth
 */
class Auth_Login_Exception extends Auth_Exception {}

/**
 * Auth session exceptions
 * @package Auth
 */
class Auth_Session_Exception extends Auth_Exception {}

/**
 * Authorization exception
 * @package Auth
 */
class Authz_Exception extends Auth_Exception {}


/* --------------- ClassConfig ----------------- */
if (class_exists('ClassConfig', false)) ClassConfig::applyToClass('Q\Auth');

/* --------------- Auto start ----------------- */
if (!defined('AUTH_NO_START') && Auth::i()->exists()) Auth::i()->start();

