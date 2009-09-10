<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Transform.php';

/**
 * Transform a multi dimensional array to an XML
 *
 * @package Transform
 */
class Transform_Array2XML extends Transform
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
    * Convert a multi dimensional array to an XML
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
        if (!is_array($data)) throw new Exception('Unable to transform Array to XML: data is not array');
        
		$this->writer->setIndent(true);
        $this->writer->startDocument('1.0', 'ISO-8859-1');
		$this->writer->startElement($this->rootNodeName);
        $this->ArrayToXML($data);
        $this->writer->endElement(); // end node $this->rootNodeName;
        $this->writer->endDocument();
        
        return $this->writer;
    }

    /**
     * Start the transformation and return the result.
     *
     * @param array $data  Array to transform to xml
     * @return mixed
     */
    public function process($data = null)
    {   
        if ($this->chainNext) $data = $this->chainNext->process($data);
    	
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
        if ($this->chainNext) $data = $this->chainNext->process($data);
		
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
        if ($this->chainNext) $data = $this->chainNext->process($data);
		
        $this->writer = new \xmlWriter();
		$this->writer->openUri($filename);
		$this->exec($data);
	}
}
