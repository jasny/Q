<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * Place content in a template using the output handler.
 * 
 * @package SiteTemplate
 */
class TemplateSite
{
	/**
	 * Singleton object
	 * @var Q\SiteTemplate
	 */
	protected static $instance;

	/**
	 * Marker to place data, %s for name.
	 * This is a pcre regular expression with ~ as delimiter.
	 * 
	 * @var string
	 */
	public $marker = '<div\s+id="mark-%s"\s*/>';
	
	/**
	 * Template
	 * @var string
	 */
	protected $template;
	
	/**
	 * Cached footer of the template
	 * @var string
	 */
	protected $footer;
	
	
	/**
	 * Queue of current markers
	 * @var array
	 */
	protected $curmarkers=array('content');

	
	/**
	 * Data for each marker
	 * @var array
	 */
	protected $data=array();

	
	/**
	 * Singleton method
	 * 
	 * @return SiteTemplate
	 */
	static function i()
	{
		if (!isset(self::$instance)) throw new Exception("TemplateSite is not initialised. Use Q/TemplateSite::with().");
		return self::$instance;
	}
	
	/**
	 * Set the options.
	 *
	 * @param string|array $dsn  DSN/driver (string) or array(driver[, arg1, ...])
	 * @return SiteTemplate
	 */
	public static function with($dsn)
	{
	    if (isset(self::$instance)) throw new Exception("TemplateSite instance is already created.");
	    
	    $options = is_string($dsn) ? $dsn : extract_dsn($dsn);
	    return new self($options);
    }
	    
	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	protected function __construct($options=array())
	{
	    foreach ($options as $key=>$value) {
	        $refl = new ReflectionProperty($this, $key);
	        if ($refl->isPublic()) $this->$key = $value;
	    }
	}
    
	/**
	 * Start output handler
	 */
	public function start()
	{
	    if (in_array(array($this, '__callback'), ob_list_handlers(), true)) throw new Exception("Site template already started");
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
    	if (count($this->curmarkers) != 1) {
    		$this->appendData(end($this->curmarkers), $buffer);
    		$buffer = null;	
    	}
    	
    	if (!empty($this->data[0])) {
    		$buffer = $this->data[0] . $buffer;
	    	$this->data[0] = null;
    	}
    	
		if (!isset($this->footer)) {
			list($header, $this->footer) = preg_split('/' . sprintf($this->marker, $this->curmarkers[0]) . '/i', $this->template, 2);
			$header = preg_replace('~' . str_replace($this->marker, preg_quote('%s', '/'), '(.*?)') . '~ie', 'isset($this->data[$1]) ? $this->data[$1] : ""', $header);
			$buffer = $header . $buffer;
		}

		if ($flags & PHP_OUTPUT_HANDLER_END) {
			$buffer .= preg_replace('/' . str_replace(preg_quote($this->marker, '/'), '%s', '(.*?)') . '/ie', 'isset($this->data[$1]) ? $this->data[$1] : ""', $this->footer);
		}
        
		return $buffer;
    }

	/**
	 * Directly set the data for a marker, overwriting existing data.
	 *
	 * @param string $marker
	 * @param string $data
	 */
    public function setData($marker, $data)
	{ 
		$this->data[$marker] = $data;
    }

	/**
	 * Append data for a marker
	 *
	 * @param string $marker
	 * @param string $data
	 */
    public function appendData($marker, $data)
	{
		$this->data[$marker] = (isset($this->data[$marker]) ? $this->data[$marker] : "") . $data;
    }
    

	/**
	 * The next data in the outputbuffer should be placed for this marker.  
	 *
	 * @param string $marker
	 * @param string $data
	 */
    public function mark($marker)
    {
    	$data = ob_get_clean();
    	if ($data) $this->appendData(end($this->curmarkers), $data);

    	array_push($this->curmarkers, $marker);
    }

    /**
     * The next data in the outputbuffer should be placed for the previous marker.
     */
    public function endmark()
    {
    	if (count($this->curmarkers) == 1) throw new Exception("Called endmark more often than mark.");
    	
    	$marker = array_pop($this->curmarkers);
    	$data = ob_get_clean();
		
		if ($data) $this->appendData($marker, $data);
    }
}

if (class_exists('Q\ClassConfig', false)) ClassConfig::applyToClass('Q\SiteTemplate');

