<?php
namespace Q;

require_once "Q/Auth.php";
require_once "Q/Auth/SimpleUser.php";
require_once "Q/DB.php";

/**
 * Authenticate using checking against a DB.
 * Authentication table should have an 'auth' statement and possibly an 'onstart', 'onlogin' and 'onlogout' statement.
 *  
 * @package Auth
 */
class Auth_DB extends Auth 
{
	/**
	 * Database table to get user data from.
	 * @var Q\DB_Table|string
	 */
	public $table;

    /**
	 * Is query initialed
	 * @var boolean
	 */
	public $queryInit;
	
    /**
	 * Query statement to get user data from the DB.
	 * Shoud result in array(id, fullname, username, host, password, group(s), active(bool), expire(unix timestamp), ...)
	 * 
	 * @var Q\DB_Statement|string
	 */
	public $query;

	/**
	 * Query to perform on session start.
	 * You can speficy multiple as array.
	 * 
	 * @var Q\DB_Statement|string
	 */
	public $onStartQuery;
		
	/**
	 * Query to perform on login.
	 * You can speficy multiple as array.
	 * 
	 * @var Q\DB_Statement|string
	 */
	public $onLoginQuery;
	
	/**
	 * Query to perform on logout.
	 * You can speficy multiple as array.
	 * 
	 * @var Q\DB_Statement|string
	 */
	public $onLogoutQuery;
	
	
	/**
	 * Class constructor
	 * 
	 * @param string $table
	 */
	public function __construct($table=null)
	{
	    $this->table = $table;
	    parent::__construct();
	}	
	
	/**
	 * Initialises auth, onStart, onLogin and onLogout queries.
	 */
	public function initStatement()
	{
	    if (isset($this->table)) {
	        if (is_string($this->table)) $this->table = DB::i()->table($this->table);
	        if (!isset($this->query)) $this->query = $this->table->getStatement('auth');
	        if (!isset($this->onStartQuery) && $this->table['onstart']) $this->onStartQuery = $this->table->getStatement('onstart');
	        if (!isset($this->onLoginQuery) && $this->table['onlogin']) $this->onLoginQuery = $this->table->getStatement('onlogin');
	        if (!isset($this->onLogoutQuery) && $this->table['onlogout']) $this->onLogoutQuery = $this->table->getStatement('onlogout');
	        
	        $link = $this->table->getLink();
	    } else {
	        $link = DB::i();
	    }
	    
	    if (!isset($this->query)) throw new Exception("Table nor query is set.");
	    if (is_string($this->query)) $this->query = $link->prepare($this->query);
	      else $this->query->commit();

	    if (isset($this->onStartQuery) && is_string($this->onStartQuery)) $this->onStartQuery = $link->prepare($this->onStartQuery);	    
	    if (isset($this->onLoginQuery) && is_string($this->onLoginQuery)) $this->onLoginQuery = $link->prepare($this->onLoginQuery);
	    if (isset($this->onLogoutQuery) && is_string($this->onLogoutQuery)) $this->onLogoutQuery = $link->prepare($this->onLogoutQuery);

	    if (!isset($this->passwordCrypt)) $this->passwordCrypt = $this->query->getField(4)->getProperty('crypt'); 
	    
	    $this->queryInit = true;
	}
	

	/**
	 * Fetch user based on username.
	 * Returns authentication result
	 *
	 * @param string $username
     * @param string $password
     * @param int    $code      Output: return code
	 * @return Auth_User
	 */
	public function authUser($username, $password, &$code)
	{
		if (!$this->queryInit || !($this->query instanceof DB_Statement)) $this->initStatement(); 
	    
	    $this->query->addCriteria(2, $username);
	    if (($ip = HTTP::clientIP())) $this->query->addCriteria(3, $ip, 'REVERSE LIKE');
	    $result = $this->query->execute();
	    $this->query->reset();
	    
	    $row = $result->fetchRow();
	    $info = $row ? array_combine(array('id', 'fullname', 'username', 'host', 'password', 'groups', 'active', 'expire') + $result->getFieldNames(), $row) : array('username'=>$username, 'password'=>$this->encryptPassword($password));
	    
	    $info['host'] = HTTP::clientIp();
        $user = new Auth_User($info);
	    
        if ($user->id === null) $code = self::UNKNOWN_USER;
	      elseif ($user->password != $this->encryptPassord($password, $user->password)) $code = self::INCORRECT_PASSWORD;
          elseif (!$user->active) $code = self::INACTIVE_USER;
          elseif ($user->expire && $user->expire < time()) $code = self::PASSWORD_EXPIRED;
        
	    return $user; 
    }
	
	/**
	 * Fetch user.
	 *
	 * @param string $field  Criteria field
	 * @param mixed  $value  Criteria value
	 * @return Auth_User
	 */
    protected function doFetchUser($field, $value)
    {
		if (!$this->queryInit || !($this->query instanceof DB_Statement)) $this->initStatement();
		 
		$this->query->addCriteria($field, $value);
	    $result = $this->query->execute();
	    $this->query->reset();
	    
	    if (!$result->countRows()) return null;
	    
	    $info = array_combine(array('id', 'fullname', 'username', 'host', 'password', 'groups', 'active', 'expire') + $result->getFieldNames(), $result->fetchOrdered());
	    $info['host'] = HTTP::clientIp();
	    return new Auth_User($info);    	
    }
    
	/**
	 * Fetch user based on id.
	 *
	 * @param int|string $uid
	 * @return Auth_User
	 */
	public function fetchUser($uid)
	{
		return $this->doFetchUser(0, $uid);
    }

	/**
	 * Fetch user based on username.
	 *
	 * @param string $username
	 * @return Auth_User
	 */
	public function fetchUserByName($username)
	{
		return $this->doFetchUser(1, $username);
	}
	    

    /**
     * Actions to perform on successfull session start.
     * Executes onStartQuery, parsing in user info as named arguments.
     */
    protected function onStart()
    {
		if (!$this->queryInit) $this->initStatement(); 
    	if (!isset($this->onStartQuery)) return;
    	
    	if (!($this->onStartQuery instanceof DB_Statement)) $this->initStatement(); 
        $this->onStartQuery->execute($this->_user->getInfo());
    }
        
    /**
     * Actions to perform on login.
     * Executes onLoginQuery, parsing in user info as named arguments.
     */
    protected function onLogin()
    {
        if (!$this->queryInit) $this->initStatement(); 
        if (!isset($this->onLoginQuery)) return;

    	if (!($this->onLoginQuery instanceof DB_Statement)) $this->initStatement(); 
        $this->onLoginQuery->execute($this->_user->getInfo());
    }

    /**
     * Actions to perform on login.
     * Executes onLogoutQuery.
     */
    protected function onLogout()
    {
        if (!$this->queryInit) $this->initStatement(); 
        if (!isset($this->onLogoutQuery)) return;

    	if (!($this->onLogoutQuery instanceof DB_Statement)) $this->initStatement(); 
        $this->onLogoutQuery->execute($this->_user->getInfo());
    }
}

