<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * Cache output.
 * 
 * @package CacheOutput
 * @todo The file extension of the cached file should be replaced based on the content type.
 */
class CacheOutput
{
	/**
	 * Cache interface
	 * @var Cache
	 */
	public $cache;
	
	/**
	 * Cached data
	 * @var string
	 */
	protected $data;
    
	
	/**
	 * Create CacheOutput object.
	 *
	 * @param Cache $cache
	 * @return CacheOutput
	 */
	public static function with($cache)
	{
	    return new self($cache);
    }
	    
	/**
	 * Class constructor
	 * 
	 * @param Cache $cache
	 */
	public function __construct($cache)
	{
	    $this->cache = $cache;
		ob_start(array($this, '__callback'));
    }

    /**
     * Callback method for ob_start
     * @ignore
     * 
     * @param string $buffer
     * @param int    $flags
     * @return string
     */
    public function __callback($buffer, $flags)
    {
		if ($flags & PHP_OUTPUT_HANDLER_END) {
			if (!($this->cache instanceof Cache)) $this->cache = Cache::with($this->cache);
			
			$buffer = $this->data . $buffer;
			$this->cache->save($_SERVER["REQUEST_URI"], $buffer);
			return $buffer;  
		} else {
		    $this->data .= $buffer;
		}
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && class_exists('Q\Config') && Config::i()->exists() && ($dsn = Config::i()->get('cacheoutput'))) {
    CacheOutput::with($dsn);
}
