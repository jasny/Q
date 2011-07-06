<?php
namespace Q;

require_once 'Q/Output/Handler.php';
require_once 'Q/Transform.php';

/**
 * Transform output.
 *
 * @package Output
 */
class Output_Transform implements Output_Handler
{
    /**
     * Transform interface
     * @var Transform
     */
    public $transform;
    
    /**
     * Output handler options
     * @var int
     */
    public $opt;
    
    /**
     * Cached data
     * @var array
     */
    protected $data;
    
    
    /**
     * Class constructor
     *
     * @param Transform $transform  Transform interface or DSN string
     * @param int       $opt        Output handler options
     */
    public function __construct($transform, $opt=0)
    {
        if (!($transform instanceof Transform)) $transform = Transform::with($this->transform);
        $this->transform = $transform;
        $this->opt = $opt;
        
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
        $marker = $this->opt & Output::IGNORE_MARKERS || is_array($buffer) ? null : Output::curMarker();
        
        // Directly flush the output
        if (!isset($marker)) return $this->transform->process($buffer);
        
        // Keep caching the output of flush
        $this->data[$marker] = $this->data;
        return $flags & PHP_OUTPUT_HANDLER_END ? $this->transform->process($this->data) : null;
    }
}
