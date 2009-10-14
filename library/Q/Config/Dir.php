<?php
namespace Q;

require_once 'Q/Config/File.php';

/**
 * Load all config from a dir
 *
 * @package Config
 */
class Config_Dir extends Config_File
{
    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($path, $options=array())
    {
        if (is_array($path)) {
            $options = $path + $options;
            $path = null;
        
            if (isset($options['driver'])) {
                if (isset($options[0])) {
                    if (!isset($options['path'])) $options['path'] = $options[0];
                    if (!isset($options['ext'])) $options['ext'] = $options['driver'];
                    unset($options[0]);
                } else {
                    $options[0] = $options['driver'];
                }
            }
        }
        
        if (isset($options[0])) {
            if (strpos($options[0], ':') !== false) {
                list($options['ext'], $options['path']) = explode(':', $options[0], 2);
            } else {
                $key = !isset($options['ext']) && strpos($options[0], '.') === false && strpos($options[0], '/') === false ? 'ext' : 'path';
                if (!isset($options[$key])) $options[$key] = $options[0];
            }
        }
        
        $this->_path = isset($path) ? Fs::dir($path) : (isset($options['path']) ? Fs::dir($options['path']) : null);
                
        if (isset($options['transformer'])) {
            $this->_transformer = $options['transformer'] instanceof Transformer ? $options['transformer'] : Transform::with($options['transformer']);
        } 
        $this->_ext = isset($options['ext']) ? $options['ext'] : (isset($this->_transformer) ? $this->_transformer->ext : null);
        
        if (!isset($this->_transformer) && !empty($this->_ext)) {
            $this->_transformer = Transform::from($this->_ext);
        }
        
        \ArrayObject::__construct(array(), \ArrayObject::ARRAY_AS_PROPS);
    }
    
    public function offsetSet($key, $value)
    {
       if (is_scalar($value) || is_resource($value)) throw new Config_Exception();
       //check if not ext .....
       $config = new Config_File(array($this->_path->file("$key.{$this->_ext}")));
       $config->exchangeArray((array)$value);
       
       parent::offsetSet($key, $config);
    }
    
    public function offsetGet($key)
    {
        if (isset($this[$key])) return parent::offsetGet($key);
                
        $dirname = "{$this->_path}/{$key}";
        $filename = "{$dirname}.{$this->_ext}";

        $options = array();
        if ($this->_transformer) $options['transformer'] = $this->_transformer;
        
        if (is_dir($dirname)) {
            $this[$key] = new Config_Dir(Fs::dir($dirname), $options);
        } elseif (Fs::has($filename)) {
            $this[$key] = new Config_File(Fs::file($filename), $options);
        } else {
            trigger_error("Configuration section '$key' doesn't exist for '{$this->_path}'", E_WARNING);
            return null;
        }
        
        return parent::offsetGet($key);
    }
}
/* 
$conf = new Config_Dir('/etc/myapp', array('ext'=>'yaml'));
$conf['af']['de'] = 10;

$conf['af'] = array();
$conf['af'] = new Config_File(array('ext'=>'yaml'));
$conf['af'] = new Config_Dir(array('ext'=>'yaml'));

$a = new Config_File();
$a['xs'] = 'ssrr';
$a['x']['v'] = 10;

$conf['a'] = $a;  // /etc/myapp/a.yaml
$conf['x'] = $a;
$a->save();
*/