<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Transform.php';

/**
 * Place content in a template using the output handler.
 *
 * @package Transform
 */
class Transform_Replace extends Transform
{
	/**
	 * Marker to place data, %s for name.
	 * This is a pcre regular expression with ~ as delimiter.
	 * 
	 * @var string
	 */
	public $marker = '%%{%s}';
			
	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	public function __construct($options=array())
	{
        parent::__construct($options);

        if (!isset($options['file']) && isset($options[0]) && is_file($options[0])) $options['file'] = $options[0];
        if (isset($options['file']) && is_file($options['file'])) $this->template = file_get_contents($options['file']);

        if(!isset($this->template) && isset($options['template'])) $this->template = $options['template'];
	}

    /**
     * Do the transformation and return the result.
     *
     * @param mixed $data  Data to transform
     * @return mixed
     */
   public function process($data=null) {
   	    if (empty($this->template)) throw new Exception('Unable to start the replace process: No template specified');

   	    if ($this->chainNext) $data = $this->chainNext->process($data);

   	    if (!is_array($data)) throw new Exception("Unable to start the replace process : Incorect data type");
        $this->data = $data;
        
        $content = $this->template;
        
        foreach ($data as $key => $value) {
			$marker = sprintf($this->marker, $key);
			$content = preg_replace('/(' . preg_quote($marker, '/'). ')/', $value, $content);
        }
        return $content;
    }
    
    /**
     * Do the transformation and output the result.
     *
     * @param mixed $data  Data to tranform
     */
    public function output($data) {
        echo $this->process($data);
    }

    /**
     * Do the transformation and save the result to a file
     *
     * @param srting $filename  The file path where to save the result
     * @param mixed  $data      Data to tranform
     */
    public function save($filename, $data) {
    	if(!file_put_contents($filename, $this->process($data))) throw new Exception("Unable to create file {$filename}");
    }
    
}
