<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Fs.php';

/**
 * Load a ini file into an array
 *
 * @package Transform
 */
class Transform_Unserialize_Ini extends Transform
{
	/**
	 * Return associated array instead of value object
	 * @var boolean
	 */
	public $assoc = true;
	
    /**
     * Transform data and return the result.
     *
     * @param string $data  Yaml string
     * @return array
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);
        $file_object = new Fs_File($data);
    	if ($file_object->exists()) $data = parse_ini_file($data, true);
          else $data = parse_ini_string((string)$data, true);
          
        return $data;
    }
}
