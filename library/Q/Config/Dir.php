<?php
namespace Q;

require_once 'Q/Config.php';
require_once 'Q/Config/File.php';

/**
 * Load all config from a dir
 *
 * @package Config
 */
class Config_Dir extends Config
{
    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options=array())
    {        
        if (!is_array($options)) $options = (array)$options;
        
        if (!isset($options['path'])) {
            if (!isset($options[0])) throw new Exception("Unable to load files for config: No option 'path' supplied.");
            $options['path'] = $options[0];
            unset($options[0]);
        }
        $this->_path = Fs::dir($options['path']);
                
        if (isset($options['transformer'])) {
            $this->_transformer = $options['transformer'] instanceof Transformer ? $options['transformer'] : Transform::with($options['transformer']);
        } else {
            $options['driver'] = isset($options['driver']) ? $options['driver'] : $this->_path->extension();
            $this->_transformer = Transform::from($options['driver']);
        }
        if (isset($options['driver'])) $this->_ext = $options['driver'];
                
//        \ArrayObject::__construct($this->_transformer->process($this->_path));          
    }
    
    public function offsetSet($key, $value)
    {
       if (is_scalar($value) || is_resource($value)) throw new Config_Exception();
       //check if not ext .....
       $config = new Config_File(array($this->_path->file("$key.{$this->_ext}")));
       $config->exchangeArray((array)$value);
       
       \ArrayObject::offsetSet($key, $config);
    }

}