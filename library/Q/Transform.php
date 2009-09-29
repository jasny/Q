<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/Transform/Exception.php';
require_once 'Q/Transformer.php';

/**
 * Base class for Transform interfaces.
 * 
 * {@example
 * $type = 'json';
 * $transformer = Transform::from($type);
 * $data = $transformer->process($_POST['data']);
 * $transformer->getReverse()->output($data);
 * }}
 * 
 * {@example
 * Transform::width('xml:' .$path)->process($_POST['data']); // $path is the path to the file that will be transformed
 * }}
 * 
 * Available drivers :
 * xsl, replace, php, text2html, serialize-jason, serialize-xml, serialize-php, 
 * serialize-yaml, serialize-ini, unserialize-json, unserialize-xml, 
 * unserialize-php, unserialize-yaml, unserialize-ini
 *  * @package Transform
 */
abstract class Transform implements Transformer
{
	/**
	 * Drivers with classname.
	 * @var array
	 */
	static public $drivers = array(
      'xsl' => 'Q\Transform_XSL',
	  'replace' => 'Q\Transform_Replace',
	  'php' => 'Q\Transform_PHP',
	  'text2html' => 'Q\Transform_Text2HTML',
	
	  'serialize-json' => 'Q\Transform_Serialize_Json',
	  'serialize-xml' => 'Q\Transform_Array2XML',
	  'serialize-php' => 'Q\Transform_Serialize_PHP',
	  'unserialize-json' => 'Q\Transform_Unserialize_Json',
	  'unserialize-xml' => 'Q\Transform_Unserialize_XML',
	  'unserialize-php' => 'Q\Transform_Unserialize_PHP',
      'unserialize-yaml' => 'Q\Transform_Unserialize_Yaml',
      'unserialize-ini' => 'Q\Transform_Unserialize_Ini',
	);
	
    /**
     * Next transform item in the chain
     * @var Transform
     */
    protected $chainInput;
	
    
	/**
	 * Create a new Transformer.
	 *
	 * @param string|array $dsn      Transformation options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return Transformer
	 */
	public static function with($dsn, $options=array())
	{
		$options = (is_scalar($dsn) ? extract_dsn($dsn) : (array)$dsn) + (array)$options;
		$driver = $options['driver'];

		if (!isset(self::$drivers[$driver]) && strpos($driver, '.') !== false && isset(self::$drivers[pathinfo($driver, PATHINFO_EXTENSION)])) {
		    $options[0] = $driver;
		    $driver = pathinfo($driver, PATHINFO_EXTENSION);
		}

		if (!isset(self::$drivers[$driver])) throw new Transform_Exception("Unable to create Transform object: Unknown driver '$driver'");
		$class = self::$drivers[$driver];
		if (!load_class($class)) throw new Transform_Exception("Unable to create $class object: Class does not exist.");

		return new $class($options);
	}
	
	/**
	 * Create a new Tranfromer to serialize data.
	 * 
	 * @param string $type
	 * @param array  $options
	 * @return Transformer
	 */
	public static function to($type, $options=array())
	{
		return self::with("serialize-$type", $options);
	}

	/**
	 * Create a new Tranfromer to unserialize data.
	 * 
	 * @param string $type
	 * @param array  $options
	 * @return Transformer
	 */
	public static function from($type, $options=array())
	{
		return self::with("unserialize-$type", $options);
	}
	
	
	/**
	 * Class constructor
	 *
	 * @param array $options
	 */
	public function __construct($options=array())
	{
	    foreach ($options as $key=>$value) {
	        $this->$key = $value;
	    }
	}
	
	/**
	 * Get a transformer that does the reverse action.
	 * 
	 * @param Transformer $chain
	 * @return Transformer
	 */
	public function getReverse($chain=null)
	{
		throw new Transform_Exception("There is no reverse transformation defined.");
	}
	
    /**
     * Pull input through chained transformer, before processing.
     *
     * @param Transform $cache  Transform object, DNS string or options
     */
    public function chainInput($transform)
    {
        if (!($transform instanceof Transform)) $transform = self::with($transform);
        $this->chainInput = $transform;
    }
    
    
    /**
     * Magic method when object is used as function; Alias of Transform::process().
     * 
     * @param $data
     * @return mixed
     */
    public function __invoke($data)
    {
		$this->transform($data);
    }
    
	/**
	 * Transform data and display the result.
	 *
	 * @param mixed $data
	 */
	public function output($data)
	{
		$out = $this->process($data);
        if (!is_scalar($out) && !(is_object($out) && method_exists($out, '__toString'))) throw new Transform_Exception("Unable to output data: Transformation returned a non-scalar value of type '" . gettype($out) . "'.");
        
        echo $out;
	}

	/**
	 * Transform data and save the result into a file.
	 *
	 * @param string $filename File name
	 * @param mixed  $data
     * @param int    $flags    Fs::RECURSIVE and/or FILE_% flags as binary set.
	 */
	function save($filename, $data=null, $flags=0)
	{
		$out = $this->process($data);
        if (!is_scalar($out) && !(is_object($out) && method_exists($out, '__toString'))) throw new Transform_Exception("Unable to save data to '$filename': Transformation returned a non-scalar value of type '" . gettype($out) . "'.");
		
        if (!Fs::file($filename)->putContents((string)$out, $flags)) throw new Transform_Exception("Failed to create file {$filename}.");		
	}

}
