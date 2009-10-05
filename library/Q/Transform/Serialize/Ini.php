<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Unserialize/Ini.php';

/**
 * Serialize data to a ini string.
 *
 * @package Transform
 */
class Transform_Serialize_Ini extends Transform
{
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
        if(is_scalar($data) || is_resource($data)) throw new Transform_Exception("Unable to serialize to a ini string : incorrect data type");
        
        $writer = "";
        foreach($data as $k0 =>&$v0) {
            if (is_array($v0)) {
                $writer .= "\n[{$k0}]\n";
                foreach($v0 as $k1=>$v1) {
			        if (is_array($v1)) {
			            foreach($v1 as $k2=>$v2) {
			                if (!is_int($k2) || is_array($v2)) {
			                    trigger_error("Unable to serialize data to a ini string: Invalid array structure.", E_USER_WARNING);                                	
			                    continue;
			                }
			                $writer .= "{$k1}[] = \"$v2\"\n";
			            }
			        } else {
			            $writer .= "{$k1} = \"$v1\"\n";
			        }    
                }
            }else {
                $writer .= "{$k0} = \"$v0\"\n";
            }
        }
        
        return $writer;
    }
}
