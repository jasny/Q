<?php
namespace Q;

<<<<<<< HEAD:library/Q/Transform/Unserialize/Ini.php
require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Serialize/Ini.php';
=======
require_once 'Q/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Serialize/Ini.php';
require_once 'Q/Fs.php';
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:library/Q/Transform/Unserialize/Ini.php

/**
 * Load a ini file into an array
 *
 * @package Transform
 */
class Transform_Unserialize_Ini extends Transform
{
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Serialize_Ini($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
		
    /**
     * Transform data and return the result.
     *
     * @param string $data  Yaml string
     * @return array
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
<<<<<<< HEAD:library/Q/Transform/Unserialize/Ini.php
        if ($data instanceof Fs_Node) $data = parse_ini_file($data, true);
=======
        if ($data instanceof Fs_Item) $data = parse_ini_file($data, true);
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:library/Q/Transform/Unserialize/Ini.php
          else $data = parse_ini_string((string)$data, true);
          
        return $data;
    }
}
