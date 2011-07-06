<?php
namespace Q;

require_once 'Q/Output/Handler.php';
require_once 'Q/Cache.php';

/**
 * Cache output.
 * 
 * If an empty buffer is flushed and PHP_OUTPUT_HANDLER_END is set, the handler assumes that ob_end_clean() is
 * called and will discard the data, not caching anything.
 *
 * @package Output
 */
class Output_Cache implements Output_Handler
{
    /**
     * Cache interface
     * @var Cache
     */
    public $cache;
    
    /**
     * Cached data
     * @var string|array
     */
    protected $data;
    
    
    /**
     * Class constructor
     *
     * @param Cache $cache  Cache interface or DSN string
     */
    public function __construct($cache)
    {
        if (!($cache instanceof Cache)) $cache = Cache::with($cache);
        $this->cache = $cache;
        ob_start(array($this, 'callback'));
    }
    
    
    /**
     * Callback for output handling.
     *
     * @param string|array $buffer
     * @param int          $flags
     * @return string|array
     */
    public function callback($buffer, $flags)
    {
        if (is_array($buffer)) {
            $this->data = $buffer;
        } else {
            $marker = Output::curMarker();
            if ($marker !== null) {
                if (!is_array($this->data)) $this->data = isset($this->data) ? array(Output::$defaultMarker=>$this->data) : array();
                $this->data[$marker] = $this->data;
            } else {
                $this->data .= $buffer;
            }
        }
        
        if ($flags & PHP_OUTPUT_HANDLER_END) $this->cache->save(static::makeKey(), $this->data);
        return false;
    }
    
    /**
     * Generate identifier for cache interface.
     * @return string
     * 
     * @todo The file extension of the cached file should be replaced based on the content type.
     */
    static public function makeKey()
    {
        return $_SERVER["REQUEST_URI"];
    } 
}
