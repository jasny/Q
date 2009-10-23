<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';

/**
 * Place content in a xsl template using the output handler.
 * 
 * Options:
 *   template   xsl template string or Fs_Node
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
        if (isset($options[0])) $options['template'] = Fs::file($options[0]);
          elseif (isset($options['file'])) $options['template'] = Fs::file($options['file']);
        
        unset($options[0], $options['file']);
		
        parent::__construct($options);
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
        if ($data instanceof Fs_Node) $xml->load((string)$data);
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
    	if (empty($this->template) || !is_string($this->template) && !($this->template instanceof Fs_Node)) throw new Transform_Exception("Unable to start XSL transformation : No template available or wrong variable type");

    	$toLoad = $this->template;

        $xslDoc = $this->loadXML($this->template);
        
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
   	    
        if ($data instanceof Fs_Node) $data = $data->getContents();
        
        if (is_array($data)) $data = Transform::to('xml', array('rootNodeName' => isset($this->rootNodeName) ? $this->rootNodeName : 'root'))->process($data);        

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
    	if (empty($data)) throw new Transform_Exception("Unable to start XSL transformation : No data supplied");
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