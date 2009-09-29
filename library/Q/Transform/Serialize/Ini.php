<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Unserialize/Ini.php';
require_once 'Q/Fs.php';

/**
 * Serialize data to a ini string.
 *
 * @package Transform
 */
class Transform_Serialize_Ini extends Transform
{
	/**
	 * Ini writer
	 * @var string
	 */
	public $writer = "";
		
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Unserialize_Ini($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
	
	/**
     * Serialize data and return result.
     *
     * @param mixed $data
     * @return string
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
        $this->writer = "";
        return $this->ArrayToIni($data);
    }

    /**
    * Convert a multi dimensional array to an Ini
    *
    * @param array $data
    * @param string $rootNodeName - root node name - default is 'root'
    * @return XMLWriter
    */
    protected function ArrayToIni(&$data)
    {
        foreach($data as $key =>&$value) {
            if (is_array($value)) {
//            	$this->startNode();
                $this->writer .= "\n[$key]\n";
                $this->ArrayToIni($value);
//                $this->closeNode();
            }else {
                $this->writer .= "$key = \"$value\"\n";
            }
        }
        return $this->writer;
    }

   
}
