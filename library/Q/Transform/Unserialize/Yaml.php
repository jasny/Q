<?php
namespace Q;

<<<<<<< HEAD:library/Q/Transform/Unserialize/Yaml.php
require_once 'Q/Transform/Exception.php';
=======
require_once 'Q/Exception.php';
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:library/Q/Transform/Unserialize/Yaml.php
require_once 'Q/Transform.php';

/**
 * Load a yaml file into an array
 *
 * @package Transform
 */
class Transform_Unserialize_Yaml extends Transform
{
    /**
     * Transform data and return the result.
     *
     * @param string $data  Yaml string or file
     * @return array
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
    	if ($data instanceof Fs_Item) $data = $data->getContents();
          else $data = (string)$data;
        
        $data = syck_load($data);
          
        return $data;
    }
}
