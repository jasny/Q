<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Unserialize/XML.php';

/**
 * Transform a multi dimensional array to an XML
 *
 * Options:
 *   rootNodeName  Root node name that will be pass when create the xml (default is 'root') 
 *   map           Map values as array(tagname=>mapping) using '@att', 'node', '"string"' or combine as '"string".@node' . I recommend not to use it.
 *   
 * @package Transform
 */
class Transform_Serialize_XML extends Transform
{	
	/**
	 * XMLWriter
	 * @var XMLWriter object
	 */
	protected $writer;
	
	/**
	 * Root node name that will be pass when create the xml;
	 * @var string
	 */
	public $rootNodeName = 'root';
		
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Unserialize_XML($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
	
	/**
    * Convert a multi dimensional array to a XML
    *
    * @param array $data
    * @param string $rootNodeName - root node name - default is 'root'
    * @return XMLWriter
    */
    protected function ArrayToXML(&$data)
    {
        foreach($data as $key =>&$value) {
        	
            if (is_array($value)) {
            	$this->writer->startElement($key);
            	$this->ArrayToXML($value);
            	$this->writer->endElement(); // end node $key
            }else {
            	$this->writer->writeElement($key, $value);            	
            }
        }
        return $this->writer;
    }

    /**
     * Transform and return the result.
     *
     * @param array $data  Array to transform to xml
     * @return mixed
     */
    protected function exec($data=null) 
    {
        if (!is_array($data)) throw new Transform_Exception('Unable to transform Array to XML: data is not array');
        
        if (isset($this->map)) {
        	if (!is_array($this->map)) throw new Transform_Exception("Unable to transform Array to XML. map type " . gettype($this->map) . " is incorect. Array is expected."); 
        	
            foreach($this->map as $key=>$value) {
            	$this->mapArray($key, $data);
            }
        }

		$this->writer->setIndent(true);
        $this->writer->startDocument('1.0', 'ISO-8859-1');
		$this->writer->startElement($this->rootNodeName);
        $this->ArrayToXML($data);
        $this->writer->endElement(); // end node $this->rootNodeName;
        $this->writer->endDocument();
        
        return $this->writer;
    }

	/**
	 * Map an array
	 *
	 * @param mixed $needle The key to check for
	 * @param mixed $data The array to search
	 */ 
    protected function mapArray($needle, &$data) {
        global $map;
        foreach ($data as $key => &$value) {
            if ($needle == $key) {
                $value = array($this->map[$key]=>$value);
                return;
            }
            if (is_array($value)) {
                if ($this->mapArray($needle, $value) == true) return;
                else continue;
            }
        }
           
        return;
    }
   
    /**
     * Start the transformation and return the result.
     *
     * @param array $data  Array to transform to xml
     * @return mixed
     */
    public function process($data = null)
    {   
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
        $this->writer = new \xmlWriter();
    	$this->writer->openMemory();
    	$this->exec($data);
    	return $this->writer->flush();
    }
    
	/**
	 * Start the transformation and display the result.
	 *
	 * @param array $data Array to transform to xml
	 * @return mixed
	 */
	public function output($data=null)
	{
        if ($this->chainInput) $data = $this->chainInput->process($data);
				
        $this->writer = new \xmlWriter();
		$this->writer->openUri('php://output');
		$this->exec($data);
		$this->writer->flush();
	}

	/**
	 * Start the transformation and save the result into a file
	 *
	 * @param string $filename File name
	 * @param array  $data     Array to transform to xml
	 * @return mixed
	 */
	function save($filename, $data=null)
	{
        if ($this->chainInput) $data = $this->chainInput->process($data);
				
        $this->writer = new \xmlWriter();
		$this->writer->openUri($filename);
		$this->exec($data);
	}
}
