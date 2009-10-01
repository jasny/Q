<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';

/**
 * Place content in a xsl template using the output handler.
 * 
 * Options:
 *   file       xsl file path or Fs_Item use file or template, not both
 *   template   xsl template 
 *   
 * @package Transform
 */
class Transform_XSL  extends Transform
{
	/**
	 * Template XSL
	 * @var string
	 */
	public $template;

	/**
	 * Data to transform
	 * @var string
	 */
	protected $data;

	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	public function __construct($options=array())
	{

        if (!isset($options['file']) && isset($options[0])) $options['file'] = $options[0];

        parent::__construct($options);
        
//        if (is_file($options['file'])) $this->template = file_get_contents($options['file']);	
	}
    
    /**
     * Load XML from a string
     *
     * @param string $data  Data to transform
     * @return DOMDocument object
     */
    protected function loadXML($data)
    {
        $xml = new \DOMDocument();
        if (is_file($data)) $xml->load($data);
        else $xml->loadXML($data);
        
        return $xml;
    }
    
    /**
     * Creates a new XSLTProcessor object and imports the stylesheet into the object 
     *
     * @return XSLTProcessor object
     */
    protected function getXSLTProcessor()
    {
        if ((empty($this->template) && isset($this->file) && is_file($this->file))) $toLoad = $this->file;	
        elseif (!empty($this->template)) $toLoad = $this->template;        	
        else throw new Transform_Exception("Unable to start XSL transformation : No template available");
        
        $xslDoc = $this->loadXML($toLoad);
        
        $xsltProcessor = new \XSLTProcessor();
        $xsltProcessor->importStyleSheet($xslDoc);

        return $xsltProcessor;
    }
	
	/**
     * Prepare the transformation
     *
     * @param mixed $data  Data to transform - xml or array or a file that contains the xml or the array
     * @return string
     */
   protected function getCleanedData($data=null)
   {
        if (is_string($data) && !preg_match('/^([\s|\t]*(<\?xml\s.*\?>)|(<\w+>))/i', $data) && !file_exists($data)) throw new Transform_Exception("File '$data' doesn't exists.");
   	    
        if (!is_array($data) && is_file($data)) $data = file_get_contents($data);

        if (is_array($data)) $data = Transform::with('array2xml')->process($data);        
        
        return $data;
    }
        
    /**
     * Start the transformation and return the result.
     *
     * @param mixed $data  Data to transform - xml or a file that contains the xml
     * @return mixed
     */
    public function process($data=null) 
    {
    	if ($this->chainInput) $data = $this->chainInput->process($data);
    	
    	$data = $this->getCleanedData($data);
        $xsltProcessor = $this->getXSLTProcessor();
        $xmlDoc = $this->loadXMl($data);            
        return $xsltProcessor->transformToXML($xmlDoc);
    }    
    
    /**
     * Do the transformation and output the result.
     *
     * @param mixed $data  Data to tranform
     */
    public function output($data) 
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
    	    	
        $data = $this->getCleanedData($data);
        $xsltProcessor = $this->getXSLTProcessor();
        $xmlDoc = $this->loadXMl($data);            
        $xsltProcessor->transformToUri($xmlDoc, 'php://output');
    }

    /**
     * Do the transformation and save the result into a file.
     *
     * @param sting $filename File name
     * @param mixed $data     Data to tranform
     */
    public function save($filename, $data) 
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
    	    	
        $data = $this->getCleanedData($data);
        $xsltProcessor = $this->getXSLTProcessor();
        $xmlDoc = $this->loadXMl($data);            
        $xsltProcessor->transformToUri($xmlDoc, $filename);
    }
}