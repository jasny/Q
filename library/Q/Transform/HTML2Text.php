<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Text2HTML.php';

/**
 * Transform an HTML to text
 *
 * Options:
 *   tab  Use it to set the tab
 * 
 * @package Transform
 */
class Transform_HTML2Text extends Transform
{	
    /**
     * Get a transformer that does the reverse action.
     *
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Text2HTML($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;
    }

    /**
     * Start the transformation and return the result.
     *
     * @param mixed $data  Path to the file or the string that will be transformed 
     * @return mixed
     */
    public function process($data = null)
    {           
        if ($this->chainInput) $data = $this->chainInput->process($data);

        if(!is_string($data) && !($data instanceof Fs_Node)) throw new Transform_Exception("Unable to start text transformation: Incorrect data provided");
        
        if ($data instanceof Fs_Node) $data = $data->getContents();
        if (empty($data)) throw new Transform_Exception("Unable to start text file transformation: empty data");

        $data = html_entity_decode($data);
//        $data = urldecode($data); //the links should remain encoded or not?
        $data = preg_replace("/<br\s*\/>/", "\n", $data);
        $data = preg_replace("/<\/p>/", "</p>\n", $data);
        $data = strip_tags($data);
        
        return $data;
    }
}
