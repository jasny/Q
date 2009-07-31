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
 * Beware! User information stays set, even when user is not logged in. 
 *
 * Will autostart the default interface if configured.
 * Auto start behaviour can be controller with environment var 'Q_AUTH':
 *   On       : Start Auth::i() on load (default)
 *   Off      : No auto start
 *   Required : Start Auth::i() on load and set loginRequired to true
 *   
 * @package Auth
 * 
 * @todo Implement session expire (not using session lifetime)
 * @todo Auto login/logout on var=value + multiple options + http arg support
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
	/** Status code: password is expired */
	const PASSWORD_EXPIRED = 18;
	/** Status code: session cecksum was not correct */
	const INVALID_CHECKSUM = 19;	
	/** Status code: session is expired */
	const SESSION_EXPIRED = 20;	
	
	
	/**
	 * Named Auth instances
	 * @var Auth[]
	 */
	private static $instances;

	/**
	 * Drivers with classname or as array(classname, arg, ...).
	 * @var array
	 */
	static public $drivers = array(
	    'manual'=>'Q\Auth_Manual',
	    'db'=>'Q\Auth_DB'
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
	 * If login is required and user is not logged in, display login page.
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
	 * Validate the status of the user on each request.
	 * If you only require authentication, we will use lazy load if this is set to false.
	 * 
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
	 * Flag to indicate the user is succesfully logged in.
	 * @boolean
	 */
	protected $loggedIn = false;
	
	/**
	 * Current user session information
	 * @var array
	 */
	protected $info;

	/**
	 * Current user
	 * @var Auth_User
	 */
	protected $user;	
	
	
	/**
	 * Set the options.
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
	 * Magic method to retun specific instance
	 *
	 * @param string $name
	 * @param string $args
	 * @return Auth
	 */
	static public function __callstatic($name, $args)
	{
		if (!isset(self::$instances[$name])) {
		    if (!class_exists('Q\Config') || !Config::i()->exists() || !($dsn = Config::i()->get('auth' . ($name != 'i' ? ".{$name}" : '')))) return new Auth_Mock($name);
	        Auth::$instances[$name] = Auth::with($dsn);
		}
	    	    
        return self::$instances[$name];
    }
    
    /**
     * Get specific named interface.
     * 
     * @param string $name
     * @return Auth
     */
    static public function getInterface($name)
    {
        return self::__callstatic($name, null);
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
		return $this->loggedIn;
	}

	/**
	 * Get session info.
	 * 
	 * @return object
	 */
	public function info()
	{
	    return (object)$this->info;
	}
	
	/**
	 * Get the current user.
	 *
	 * @return Auth_User
	 */
	public function user()
	{
	    if (!isset($this->user)) {
	        if (isset($this->info['uid'])) {
            	$this->user = $this->fetchUser($this->info['uid']);
            } elseif (isset($info['username'])) {
        	    $this->user = $this->fetchUserByName($this->info['username']);
            }
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
            $this->storeAttemps = Cache::with($this->storeAttemps, array('memorycaching'=>true, 'overwrite'=>true));
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
	    if (!isset($this->info)) return null;
	    
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
	    if (isset($this->info)) return (object)$this->info;
	    
	    if (is_string($this->store)) $this->store = extract_dsn($this->store);
	    
	    switch ($this->store['driver']) {
	    	case 'none':			$this->info = null; break;
	        case 'session': 		session_start();
									$this->info = isset($_SESSION['AUTH']) ? $_SESSION['AUTH'] : null; break;
	        case 'cookie':  		$this->info = array_chunk_assoc($_COOKIE, 'AUTH', '__'); break;
	        case 'request': 		$this->info = isset($_REQUEST['AUTH']) ? $_REQUEST['AUTH'] : null; break;
	        case 'env':				$this->info = array_chunk_assoc($_ENV, 'Q_AUTH', '__'); break;
	        case 'http':			$this->info = isset($_ENV['REMOTE_USER']) ? array('username'=>$_ENV['REMOTE_USER']) : null; break;
	        case 'posix':			$this->info = array('uid'=>posix_getuid()); break;
	        case 'posix_username':	$this->info = array('username'=>posix_getlogin()); break;
	        default: throw new Exception("Invalid option '{$this->store['driver']}' specified for retrieving info.");
	    }
	    return (object)$this->info;
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
	 *
	 * @param array $info
	 */
	protected function storeInfo($info)
	{
	    $this->info = $info;
	    
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
	            HTTP::addUrlRewriteVar('AUTH', $info);
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

        $this->initInfo();
        
        if (isset($this->logoutRequestVar) && (isset($_GET[$this->logoutRequestVar]) || $_SERVER['REQUEST_METHOD'] == $this->logoutRequestVar)) {
            $this->logout();
            return self::NO_SESSION;
        }
		        
        if (!isset($this->info)) {
            if ($this->loginRequired) {
                if (!isset($this->loginPage)) throw new Auth_Session_Exception("Login required", self::NO_SESSION);
                HTTP::redirect($this->loginPage);
            }
            return self::NO_SESSION;
        }

        $result = self::OK;
        if ($this->canStoreInfo() && (!isset($this->info['hash']) || $this->checksum($this->info['hash']) !== $this->info['hash'])) $result = self::INVALID_CHECKSUM;
        
        if ($this->validateOnStart) {
            if (!$this->user()) $result = self::UNKNOWN_USER;
              elseif (!$this->user()->isActive()) $result = self::INACTIVE_USER;
        }
        
        if ($result != self::OK) {
            $this->logout($result);
            return $result;
        }
        
        $this->onStart();
        $this->loggedIn = true;
        return self::OK;
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
            unset($result);
            
            if (!$this->user->isActive()) $result = self::INACTIVE_USER;
              elseif ($this->user->getExpires() < time()) $result = self::PASSWORD_EXPIRED;
              else $result = self::OK;
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

        $this->storeInfo(array('uid'=>$this->user->getId(), 'hash'=>$this->checksum()));
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
    public function logout($result=self::OK)
    {
    	if (!$this->canStoreInfo()) throw new Exception("Logging out through PHP is not supported with store option '{$this->store['driver']}'.");
    	
    	$this->loggedIn=false;
    	$this->logEvent('logout', $result);
    	if ($this->info() && isset($this->info['username'])) $username = $this->info['username'];
        
		$this->onLogout();
        if ($result != 0 && $result < 16) {
            $this->storeInfo(null);
            $this->user = null;
        }
        
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
     * Check if the current user is in specific role(s)
     * 
     * @param string $role  Role name, multiple roles may be supplied as array
     * @param Multiple roles may be supplied as additional arguments
     * 
     * @throws Auth_Session_Exception if user is not logged in
     * @throws Authz_Exception if the user is not in one of the roles
     */
    public function authz($role)
    {
        if (!$this->isLoggedIn()) throw new Auth_Session_Exception("User is not logged in.", self::NO_SESSION);
        
    	$roles = is_array($role) ? $role : func_get_args();
    	$this->user()->authz($roles);
    }
    
    
    /**
     * Store event and result in log.
     *
     * @param string $event 
     * @param int    $result   Status code
     */
    protected function logEvent($event, $code=0)
    {
        if (!isset($this->log)) return;
        
        if (!($this->log instanceof Log_Handler)) $this->log = Log::to($this->log);
        
        $msg = ucfirst($event) . ($result == 0 ? ' success' : ' failed: ' . $this->getMessage($result));
        $this->log->write(array('username'=>$this->user->username, 'host'=>HTTP::clientRoute(), 'message'=>$msg), $event);  
    }
    
    /**
     * Get message for status code.
     *
     * @param int $code  Status code
     * @return string
     */
    public function getMessage($code)
    {
        switch ($code) {
            case self::NO_SESSION:         return "No session";
            case self::OK:                 return "Success";
            case self::NO_USERNAME:        return "No username";
            case self::NO_PASSWORD:        return "No password";
            case self::UNKNOWN_USER:       return "Unknown user";
            case self::INCORRECT_PASSWORD: return "Incorrect password";
            case self::INACTIVE_USER:      return "User inactive";
            case self::HOST_BLOCKED:       return "Host blocked";
	        case self::INVALID_CHECKSUM:   return "Invalid checksum hash";	
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
abstract class Auth_Exception extends Exception implements CommonException, ExpectedException
{
    /**
     * Class constructor
     * 
     * @param string $message
     * @param int $code
     */
    public function __construct($message, $code=403)
    {
        parent::__construct($message, $code);
    }
}

/**
 * Auth login exceptions
 * @package Auth
 */
class Auth_Login_Exception extends Auth_Exception
{}

/**
 * Auth session exceptions
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
     * @param int $code
     */
    public function __construct($message, $code=401)
    {
        parent::__construct($message, $code);
    }
}

if (!empty($_ENV['Q_ONLOAD']) && !in_array(strtolower($_ENV['Q_ONLOAD']), array('off', 'no', 'false'), true)) @include 'Q.Auth.onload.php';


/* --------------- Auto start ------------------ */

if ((!isset($_ENV['Q_AUTH']) || (!empty($_ENV['Q_AUTH']) && !in_array(strtolower($_ENV['Q_AUTH']), array('off', 'no', 'false'), true))) && Auth::i()->exists()) {
    if (isset($_ENV['Q_AUTH']) && strtolower($_ENV['Q_AUTH']) == 'required') Auth::i()->loginRequired = true;
    Auth::i()->start();
}
