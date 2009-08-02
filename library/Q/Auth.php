<?php
namespace Q;

require_once "Q/ExpectedException.php";
require_once "Q/CommonException.php";

require_once "Q/Crypt.php";
require_once "Q/Cache.php";
require_once "Q/HTTP.php";
require_once "Q/Auth/User.php";

/**
 * Perform authentication.
 * 
 * Beware! User information be set, even when the user account can't be used (eg expired).
 * Make sure you call Auth->isLoggedIn() or Auth->authz().
 *
 * @package Auth
 * 
 * @todo Implement session expire (not using session lifetime)
 */
abstract class Auth
{
	/** Status code: no session exists */
	const NO_SESSION = -1;
	/** Status code: no error */
	const OK = 0;
	/** Status code: no username provided */
	const NO_USERNAME = 1;
	/** Status code: no password provided */
	const NO_PASSWORD = 2;
	/** Status code: user not found */
	const UNKNOWN_USER = 3;
	/** Status code: incorrect password */
	const INCORRECT_PASSWORD = 4;
	/** Status code: user is not active */
	const INACTIVE_USER = 16;
	/** Status code: client ip is blocked */
	const HOST_BLOCKED = 17;
	/** Status code: session cecksum was not correct */
	const INVALID_CHECKSUM = 18;	
	/** Status code: session is expired */
	const SESSION_EXPIRED = 19;	
	/** Status code: password is expired */
	const PASSWORD_EXPIRED = 0x100;
	
	/**
	 * Named Auth instances
	 * @var array[]Auth
	 */
	static private $instances;

	/**
	 * Drivers with classname or as array(classname, arg, ...).
	 * @var array
	 */
	static public $drivers = array(
	    'manual' => 'Q\Auth_Manual',
	    'db' => 'Q\Auth_DB'
	);
	
	/**
	 * Descriptions for the status codes.
	 * 
	 * @var string
	 */	
	static public $messages = array(
      self::NO_SESSION => "No session",
      self::OK => "Success",
      self::NO_USERNAME => "No username",
      self::NO_PASSWORD => "No password",
      self::UNKNOWN_USER => "Unknown user",
      self::INCORRECT_PASSWORD => "Incorrect password",
      self::INACTIVE_USER => "Inactive user",
      self::HOST_BLOCKED => "Host blocked",
      self::INVALID_CHECKSUM => "Invalid session checksum",	
	  self::SESSION_EXPIRED => "Session expired",
	  self::PASSWORD_EXPIRED => "Password expired"
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
	public $checksumCrypt = 'md5';

	/**
	 * Add the password to the checksum; enabling this requires fetching the user on each request.
	 * @var boolean
	 */
	public $checksumPassword = true;
	
	/**
	 * Add the client IP to the checksum to defend against session hijacking.
	 * @var boolean
	 */
	public $checksumClientIp = false;

	/**
	 * Encryption method used to encrypt passwords.
	 * @var Crypt
	 */
	public $passwordCrypt;
	
	
	/**
	 * Validate the status of the user on each request.
	 * @var boolean
	 */
	public $validateOnStart = true;
	
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
	 * Status of session
	 * @var int
	 */
	protected $status;
	
	/**
	 * Current user session information
	 * @var array
	 */
	protected $info;

	/**
	 * Current user.
	 * {@internal Value FALSE indicates that the user has not been loaded}}
	 * 
	 * @var Auth_User
	 */
	protected $user=false;	
	
	
	/**
	 * Factory method.
	 *
	 * @param string|array $dsn  DSN/driver (string) or array(driver[, arg1, ...])
	 * @param 
	 * @return Auth
	 */
	static public function with($dsn, $options=array())
	{
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
     * Get default interfase; this is the interface used by Q.
     * 
     * @return Auth
     */
    static public function i()
    {
        return isset(self::$instances['i']) ? self::$instances['i'] : self::getInterface('i');
    }
    
	/**
	 * Magic method to retun specific instance.
	 *
	 * @param string $name
	 * @param string $args
	 * @return Auth
	 */
	static public function __callstatic($name, $args)
	{
        return isset(self::$instances[$name]) ? self::$instances[$name] : self::getInterface($name);
    }
    
    /**
     * Get specific named interface.
     * 
     * @param string $name
     * @return Auth
     */
    static public function getInterface($name)
    {
    	if (!isset(self::$instances[$name])) {
		    if (!class_exists('Q\Config') || !Config::i()->exists() || !($dsn = Config::i()->get('auth' . ($name != 'i' ? ".{$name}" : '')))) return new Auth_Mock($name);
	        Auth::$instances[$name] = Auth::with($dsn);
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
	public function __construct()
	{}
	
	
	/**
	 * Check if user is logged in.
	 * 
	 * @return boolean
	 */
	public function isLoggedIn()
	{
	    if (!isset($this->info)) $this->start();
		return ($this->status & 0xFF) == self::OK;
	}

	/**
	 * Get the login status of this session.
	 * Returns one of the status code constants.
	 * 
	 * @return int
	 */
	public function getStatus()
	{
	    if (!isset($this->info)) $this->start();
		return $this->status;
	}
		
	/**
	 * Get session info.
	 * 
	 * @return object
	 */
	public function info()
	{
	    if (!isset($this->info)) $this->start();
	    return !empty($this->info) ? (object)($this->info) : null;
	}
	
	/**
	 * Get the current user.
	 *
	 * @return Auth_User
	 */
	public function user()
	{
	    if ($this->user !== false) return $this->user;
	    
        if (isset($this->info()->uid)) {
        	$this->user = $this->fetchUser($this->info()->uid);
        } elseif (isset($this->info()->username)) {
    	    $this->user = $this->fetchUserByName($this->info()->username);
        } else {
            $this->user = null;
        }
        
		return $this->user;
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
	    
        if (!isset($host)) $host = HTTP::getClientIP(HTTP::CONNECTED_CLIENT);
        if (empty($host) || in_array($host, $this->unblockableHosts, true)) return 0;
        
        if (!isset($this->storeAttemps)) {
            if (!Cache::hasInstance()) return 0;
            $this->storeAttemps = Cache::i();
        } elseif (!($this->storeAttemps instanceof Cache)) {
            $this->storeAttemps = Cache::with($this->storeAttemps, array('overwrite'=>true));
        }
        
        if (is_bool($attempt)) $attempt = (int)$this->storeAttemps->get("AUTH-login_attempts:$host") + 1;
        if ($attempt) $this->storeAttemps->save("AUTH-login_attempts:$host", $attempt);
          else $this->storeAttemps->remove("AUTH-login_attempts:$host");
        
        return ($this->loginAttempts - $attempt) < 0;
	}
	
	
	/**
	 * Return the hash of a password
	 * 
	 * @param string $password
	 * @param string $salt
	 * @return string
	 */
	public function encryptPassword($password, $salt=null)
	{
	    if (!($this->passwordCrypt instanceof Crypt)) $this->passwordCrypt = Crypt::with(!empty($this->passwordCrypt) ? $this->passwordCrypt : 'none');
        return $this->passwordCrypt->encrypt($password, $salt);
	}
	
	/**
	 * Return a checksum hash to identify the user and session.
	 * 
	 * @param string $salt
	 * @return string
	 */
	public function checksum($salt=null)
	{
	    if (!isset($this->info) && !$this->info()) return null;
	    
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    if (!($this->checksumCrypt instanceof Crypt)) $this->checksumCrypt = Crypt::with($this->checksumCrypt);
	    if (empty($this->checksumCrypt->secret) && !$this->passwordCrypt) throw new Exception("To create a checksum, either the password needs to be included or a secret key needs to be used.");
	    
	    return $this->checksumCrypt->encrypt((isset($this->info['uid']) ? $this->info['uid'] : $this->info['username']) . ($this->checksumPassword ? $this->user()->getPassword() : null) . ($this->checksumClientIp ? HTTP::getClientRoute() : null) . ($this->store['driver'] == 'session' ? session_id() : null), $salt);
	}
	
	
	/**
	 * Get AUTH info from session data.
	 */
	protected function initInfo()
	{
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    
	    switch ($this->store['driver']) {
	    	case 'none':			$this->info = null; break;
	        case 'session': 		session_start();
									$this->info = isset($_SESSION['AUTH']) ? $_SESSION['AUTH'] : null; break;
	        case 'cookie':  		$this->info = array_chunk_assoc($_COOKIE, 'AUTH', '__'); break;
	        case 'request': 		$this->info = isset($_REQUEST['AUTH']) ? $_REQUEST['AUTH'] : null; break;
	        case 'env':				$this->info = split_set_assoc(unquote(getenv('AUTH'))); break;
	        case 'http':			$this->info = getenv('REMOTE_USER') ? array('username'=>getenv('REMOTE_USER')) : null; break;
	        case 'posix':			$this->info = array('uid'=>posix_getuid()); break;
	        case 'posix_username':	$this->info = array('username'=>posix_getlogin()); break;
	        default: throw new Exception("Invalid option '{$this->store['driver']}' specified for retrieving info.");
	    }
	}

	/**
	 * Return if driver is able to store information.
	 * 
	 * @return boolean
	 */
	protected function canStoreInfo()
	{
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    
	    switch ($this->store['driver']) {
	    	case 'none':	return true; // Black hole; act like we can store data
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
	 */
	protected function storeInfo()
	{
	    $this->info = null;
	    if (isset($this->user)) {
	        $this->info['uid'] = $this->user->getId();
	        $this->info['checksum'] = $this->checksum();
	    }
	    
	    $matches = null;
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    
		switch ($this->store['driver']) {
			case 'none':
				break;
				
	        case 'session':
	            session_start();
	            $_SESSION['AUTH'] = $this->info;
	            break;
	            
	        case 'cookie':
	            $cookie_params = $this->store + session_get_cookie_params();
	            
	            if (!isset($this->info)) {
	                $this->info = array();
	                foreach (array_keys($_COOKIE) as $key) {
	                    if (!preg_match('/^AUTH_(.*)$/i', $key, $matches)) continue;
	                    setcookie($key, $value, 1, $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
	                    unset($_COOKIE[$key]);
	                }
	            } else {
    	            foreach (array_combine_assoc($this->info, 'AUTH', '_') as $key=>$value) {
    	                setcookie($key, $value, ($cookie_params['lifetime'] <= 1 ? 0 : time()) + $cookie_params['lifetime'], $cookie_params['path'], $cookie_params['domain'], $cookie_params['secure'], $cookie_params['httponly']);
    	                $_COOKIE[$key] = $value;
    	            }
	            }
	            break;
	            
	        case 'request':
	            HTTP::addUrlRewriteVar('AUTH', $this->info);
	            $_REQUEST['AUTH'] = $this->info;
	            break;
	            
	        case 'env':
	            putenv('AUTH='. $this->info ? escapeshellarg(implode_assoc(';', $this->info)) : null);
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
	 * @return Auth_User
	 */
	abstract public function authUser($username, $password);
	
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
	 * Start Authenticator; This is done implicitly. 
	 */
	protected function start()
    {
        if (isset($this->loginRequestVar) && (isset($_GET[$this->loginRequestVar]) || $_SERVER['REQUEST_METHOD'] == $this->loginRequestVar)) {
			if (isset($_REQUEST['username']) || empty($_GET[$this->loginRequestVar]) || $_GET[$this->loginRequestVar] == 1) {
			    $username = isset($_REQUEST['username']) ? $_REQUEST['username'] : null;
			    $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : null;
			} else {
                list($username, $password) = explode(':', $_GET[$this->loginRequestVar]) + array(null, null);
			}
			
            $this->login($username, $password);
            return;
        }

        $this->initInfo();
        
        if (isset($this->logoutRequestVar) && (isset($_GET[$this->logoutRequestVar]) || $_SERVER['REQUEST_METHOD'] == $this->logoutRequestVar)) {
            $this->logout();
        }
		        
        if (!isset($this->info)) {
            $this->status = self::NO_SESSION;
            return;
        }

        $result = self::OK;
        if ($this->canStoreInfo() && (!isset($this->info['checksum']) || $this->checksum($this->info['checksum']) !== $this->info['checksum'])) $result = self::INVALID_CHECKSUM;

        if ($this->validateOnStart) {
            if (!$this->user()) $result = self::UNKNOWN_USER;
              elseif (!$this->user()->isActive()) $result = self::INACTIVE_USER;
              elseif ($this->user->getExpires() < time()) $result = self::PASSWORD_EXPIRED;
        }
        
        if (($result & 0xFF) != self::OK) {
            $this->logout($result);
            return;
        }
        
        $this->status = $result;
        $this->onStart();
    }

    /**
     * Auth and start user session.
     *
     * @param string $username
     * @param string $password
     * @return int
     * 
     * @throws Auth_Login_Exception if login fails
     */
    public function login($username=null, $password=null)
    {
    	if (!$this->canStoreInfo()) throw new Exception("Logging in through PHP is not supported with store option '{$this->store['driver']}'.");
    	
        $this->loggedIn = false;
        $this->user = null;
        $this->storeInfo(null);
        
        if ($this->isBlocked(null, true)) {
            $result = self::HOST_BLOCKED;
        } elseif (!isset($username)) {
            $result = self::NO_USERNAME;
        } elseif (!isset($password)) {
            $result = self::NO_PASSWORD;
        } else {
            $result = $this->authUser($username, $password);
        }
        
        if (is_object($result)) {
            $this->user = $result;
            
            if (!$this->user->isActive()) $result = self::INACTIVE_USER;
              elseif ($this->user->getExpires() < time()) $result = self::PASSWORD_EXPIRED;
              else $result = self::OK;
        }

        $this->status = $result;
        $this->logEvent('login', $result);

        if ($result == self::PASSWORD_EXPIRED) throw new Auth_PasswordExpired_Exception(); 
          elseif ($result != self::OK) throw new Auth_Login_Exception($result == self::INCORRECT_PASSWORD ? self::UNKNOWN_USER : $result); // Never output incorrect password, to prevent dictionary attacks

        $this->storeInfo();
        $this->isBlocked(null, 0);

        $this->onLogin();
        return;
    }

	/**
	 * End user session.
	 * 
	 * @param int $status  Reason for loggin out.
	 */
    public function logout($status=self::OK)
    {
    	if (!$this->canStoreInfo()) throw new Exception("Logging out through PHP is not supported with store option '{$this->store['driver']}'.");
    	
    	$this->logEvent('logout', $status);
        $this->status = $status == self::OK ? self::NO_SESSION : $status;        
    	
		$this->onLogout();
        if ($status > 0 && $status < 16) {
            $this->storeInfo(null);
            $this->user = null;
        }
    }

    /**
     * Actions to perform on successfull session start.
     * To be overwritten by subclass.
     */
    protected function onStart()
    {}
    
    /**
     * Actions to perform on login.
     * To be overwritten by subclass.
     */
    protected function onLogin()
    {}
    
    /**
     * Actions to perform on logout.
     * To be overwritten by subclass.
     */
    protected function onLogout()
    {}

    
    /**
     * Authenticate and authorize; User needs to have in all roles.
     * 
     * @param string|array $role
     * 
     * @throws Auth_Session_Exception if user is not logged in.
     * @throws Authz_Exception if the user is not in one of the roles.
     */
    public function authz($role=null/*. , args .*/)
    {
        if (!$this->isLoggedIn()) throw new Auth_Session_Exception($this->status);
        if (!isset($role)) return;
        
    	$roles = is_array($role) ? $role : func_get_args();
    	$this->user()->authz($roles);
    }
    
    /**
     * Authenticate and authorize; User needs to have one of the roles.
     * 
     * @param string|array $role
     * 
     * @throws Auth_Session_Exception if user is not logged in.
     * @throws Authz_Exception if the user is not in one of the roles.
     */
    public function authzAny($role=null/*. , args .*/)
    {
        if (!$this->isLoggedIn()) throw new Auth_Session_Exception($this->status);
        if (!isset($role)) return;
        
    	$roles = is_array($role) ? $role : func_get_args();
    	$this->user()->authzAny($roles);
    }    
    
    
    /**
     * Store event and result in log.
     *
     * @param string $event 
     * @param int    $code   Status code
     */
    protected function logEvent($event, $code=0)
    {
        if (!isset($this->log)) return;
        
        if (!($this->log instanceof Log_Handler)) $this->log = Log::to($this->log);
        
        $msg = ucfirst($event) . ($code == self::OK ? ' success' : ' failed: ' . $this->getMessage($code));
        $this->log->write(array('username'=>$this->user()->username, 'host'=>HTTP::clientRoute(), 'message'=>$msg), $event);  
    }
    
    /**
     * Get message for status code.
     *
     * @param int $code  Status code
     * @return string
     */
    static public function getMessage($code)
    {
        return isset(self::$messages[$code]) ? self::$messages[$code] : "Unspecified authentication fault.";
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
abstract class Auth_Exception extends Exception implements CommonException, ExpectedException
{
    /**
     * Class constructor
     * 
     * @param string|int $status  Auth status message or code
     * @param int        $code    HTTP status code
     */
    public function __construct($status, $code=403)
    {
        if (is_int($status)) $status = Auth::getMessage($status);
        parent::__construct($status, $code);
    }
}

/**
 * Auth login exception
 * @package Auth
 */
class Auth_Login_Exception extends Auth_Exception
{}

/**
 * Auth exception for when password is expired 
 * @package Auth
 */
class Auth_PasswordExpired_Exception extends Auth_Login_Exception
{
    /**
     * Class constructor
     * 
     * @param string|int $status  Auth status message or code
     * @param int        $code    HTTP status code
     */
    public function __construct($status=Auth::PASSWORD_EXPIRED, $code=403)
    {
        parent::__construct($status, $code);
    }
}

/**
 * Auth session exception
 * @package Auth
 */
class Auth_Session_Exception extends Auth_Exception
{}

/**
 * Authorization exception
 * @package Auth
 */
class Authz_Exception extends Auth_Exception
{
    /**
     * Class constructor
     * 
     * @param string $message
     * @param int    $code     HTTP status code
     */
    public function __construct($message, $code=401)
    {
        parent::__construct($message, $code);
    }
}

if (defined('Q_AUTH_ONLOAD')) include Q_AUTH_ONLOAD;
