<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/RestException.php';
require_once 'Q/ExpectedException.php';

/**
 * A lock may be used for pessimistic locking of a resource.
 * 
 * @package Lock
 */
class Lock
{
    /**
     * Lock name
     * @var string
     */
    protected $name;

    /**
     * Timeout on a lock (in seconds).
     * @var int
     */
    public $timeout = 300;
    
    /**
     * Cache object that hold key
     * @var Cache
     */
    public $store = 'file';
    
    /**
     * Cached info
     * @var array
     */
    protected $info;
    
    /**
     * Class constructor.
     *
     * @param string $name
     * @param array  $options  Lock properties   
     */
    public function __construct($name, $options=array())
    {
        if (is_array($name)) {
            $options = $name + $options;
            $name = isset($options[0]) ? $options[0] : $options['name'];
            unset($options[0], $options['name']);
        }
        
        $value = null;
        foreach ($options as $key=>&$value) {
            $this->$key = $value;
        }
    }
    
    /**
     * Acquire the key or refresh a lock.
     * 
     * @param string $key  Key that should fit the lock when refreshing the lock.
     * @return string|false
     */
    public function acquire($key=null)
    {
        if ($this->getKey() && $key != $this->getKey()) return false;

        if (!isset($key)) $key = md5(microtime());
        $this->info = array('timestamp'=>strftime('%Y-%m-%d %T'), 'key'=>$key);
        if (class_exists('Q\Auth', false) && Auth::i()->isLoggedIn()) {
            $this->info['user'] = Auth::i()->user()->getUsername();
            $this->info['user_fullname'] = Auth::i()->user()->getFullaname();
        }

        if (!($this->store instanceof Cache)) $this->store = Cache::with($this->store);
        $this->store->save('lock:' . $this->name, $this->info, $this->timeout);
        
        return $key;
    }
    
    /**
     * Release the lock.
     * 
     * @param string $key  Key that should fit the lock
     * @return boolean
     */
    public function release($key)
    {
        if ($key != $this->getKey()) return false;
        if (!empty($this->info)) $this->store->remove('lock:' . $this->name);
    }

    /**
     * Return the name of the lock.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Get the key that fits this lock.
     *
     * @return string
     */
    public function getKey()
    {
        if (!isset($this->info)) $this->info = (array)$this->store->get("lock:" . $this->name);
        return isset($this->info['key']) ? $this->info['key'] : null;  
    }
    
    
    /**
     * Get public information.
     * 
     * @return string
     */
    public function getInfo()
    {
        return array('name'=>$this->name, 'timeout'=>$this->timeout, 'user_fullname'=>isset($this->info['user_fullname']) ? $this->info['user_fullname'] : null, 'timestamp'=>$this->info['timestamp']);
    }
    
    /**
     * Return lock as XML.
     * 
     * @return string
     */
    public function asXml()
    {
        return "<lock name=\"{$this->name}\" timeout=\"{$this->timeout}\">" .
          (isset($this->info['user_fullname']) ? '<user>' . htmlspecialchars($this->info['user_fullname'], ENT_COMPAT, 'UTF-8') . '</user>' : null) .
          "<timestamp>{$this->info['timestamp']}</timestamp>" .
          '</lock>';
    }
    
    /**
     * Get string representation for key.
     *
     * @return string
     */
    public function __asString()
    {
        $key = $this->getKey();
        return $this->name . ($key ? ":$key" : '');
    }
}


/**
 * Exception than can be thrown when the requested item is locked. 
 * 
 * @package Exception
 */
class Lock_Exception extends Exception implements RestException, ExpectedException
{
    /**
     * Class constructor
     * 
     * @param string $message
     * @param int    $code     HTTP status code
     */
    public function __construct($message="Requested item is locked", $code=423)
    {
        parent::__construct($message, $code);
    }    
}