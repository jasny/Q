<?php
namespace Q;

require_once("Q/DB.php");

/**
 * Authenticate using checking against a DB.
 * Authentication table should have an 'authenticate' statement and possibly an 'onstart', 'onlogin' and 'onlogout' statement.
 *  
 * @package Authenticate
 */
class Authenticate_DB extends Authenticate 
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
	 * Return query statement object.
	 * Also initialises onStart, onLogin and onLogout queries.
	 *
	 * @return DB_Statement
	 */
	public function getStatement()
	{
	    if ($this->queryInit) return $this->query; 
	    
	    if (isset($this->table)) {
	        if (is_string($this->table)) $this->table = DB::i()->table($this->table);
	        if (!isset($this->query)) $this->query = $this->table->getStatement('authenticate');
	        if (!isset($this->onStartQuery) && $this->table['onstart']) $this->onStartQuery = $this->table->getStatement('onstart');
	        if (!isset($this->onLoginQuery) && $this->table['onlogin']) $this->onLoginQuery = $this->table->getStatement('onlogin');
	        if (!isset($this->onLogoutQuery) && $this->table['onlogout']) $this->onLogoutQuery = $this->table->getStatement('onlogout');
	        
	        $link = $this->table->getLink();
	    } else {
	        $link = DB::i();
	    }
	    
	    if (!isset($this->query)) throw new Exception("Table nor query is set.");
	    if (is_string($this->query)) $this->query = $link->prepare($this->query);

	    if (isset($this->onStartQuery) && is_string($this->onStartQuery)) $this->onStartQuery = $link->prepare($this->onStartQuery);	    
	    if (isset($this->onLoginQuery) && is_string($this->onLoginQuery)) $this->onLoginQuery = $link->prepare($this->onLoginQuery);
	    if (isset($this->onLogoutQuery) && is_string($this->onLogoutQuery)) $this->onLogoutQuery = $link->prepare($this->onLogoutQuery);

	    if (!isset($this->passwordCrypt)) $this->passwordCrypt = $this->query->getField(4)->getProperty('crypt'); 
	    
	    $this->queryInit = true;
	    return $this->query;
	}
	

	/**
	 * Fetch user based on username.
	 * Returns authentication result
	 *
	 * @param string $username
     * @param string $password
     * @param int    $code      Output: return code
	 * @return Authenticate_User
	 */
	public function authUser($username, $password, &$code)
	{
	    $stmt = clone $this->getStatement();
	    if (!($this->passwordCrypt instanceof Crypt)) $this->passwordCrypt = Crypt::with(!empty($this->passwordCrypt) ? $this->passwordCrypt : 'none');
	    
	    $stmt->addCriteria(2, $username);
	    if (($ip = HTTP::clientIP())) $stmt->addCriteria(3, $ip, 'REVERSE LIKE');
	    $result = $stmt->execute();
	    
	    $row = $result->fetchRow();
	    $info = $row ? array_combine(array('id', 'fullname', 'username', 'host', 'password', 'groups', 'active', 'expire') + $result->getFieldNames(), $row) : array('username'=>$username, 'password'=>$this->passwordCrypt->encrypt($password));
	    
	    $info['host'] = HTTP::clientIp();
        $user = new Authenticate_User($info);
	    
        if ($user->id === null) $code = self::UNKNOWN_USER;
	      elseif ($user->password != $this->passwordCrypt->encrypt($password, $user->password)) $code = self::INCORRECT_PASSWORD;
          elseif (!$user->active) $code = self::INACTIVE_USER;
          elseif ($user->expire && $user->expire < time()) $code = self::PASSWORD_EXPIRED;
        
	    return $user; 
    }
	
	/**
	 * Fetch user based on id.
	 *
	 * @param int|string $uid
	 * @return Authenticate_User
	 */
	public function fetchUser($uid)
	{
	    $stmt = clone $this->getStatement();
	    	    
	    $stmt->addCriteria(0, $uid);
	    $result = $stmt->execute();
	    
	    if (!$result->countRows()) return null;
	    
	    $info = array_combine(array('id', 'fullname', 'username', 'host', 'password', 'groups', 'active', 'expire') + $result->getFieldNames(), $result->fetchRow());
	    $info['host'] = HTTP::clientIp();
	    return new Authenticate_User($info);
    }


    /**
     * Actions to perform on successfull session start.
     * Executes onStartQuery, parsing in user info as named arguments.
     */
    protected function onStart()
    {
        if (!$this->queryInit) $this->getStatement();
        if (!isset($this->onStartQuery)) return;

        $this->onStartQuery->getLink()->parse($this->onStartQuery, $this->_user->getInfo())->execute();
    }
        
    /**
     * Actions to perform on login.
     * Executes onLoginQuery, parsing in user info as named arguments.
     */
    protected function onLogin()
    {
        if (!$this->queryInit) $this->getStatement();
        if (!isset($this->onLoginQuery)) return;

        $this->onLoginQuery->getLink()->parse($this->onLoginQuery, $this->_user->getInfo())->execute();
    }

    /**
     * Actions to perform on login.
     * Executes onLogoutQuery.
     */
    protected function onLogout()
    {
        if (!$this->queryInit) $this->getStatement();
        if (!isset($this->onLogoutQuery)) return;

        $this->onLogoutQuery->execute();
    }
}

?>