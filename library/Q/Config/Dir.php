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
        if (!empty($options['loadall'])) $this->loadAll();
    }
    
    
    /**
     * ArrayAccess; Whether or not an offset exists. 
     * 
     * @param string $key
     * @return boolean
     */
    public function offsetExists($key)
    {
        return parent::offsetExists($key) || is_dir("{$this->_path}/{$key}") || isset($this->_ext) ? file_exists("{$this->_path}/{$key}.{$this->_ext}") : glob("{$this->_path}/{$key}.*");
    }
    
    /**
     * ArrayAccess; Assigns a value to the specified offset. 
     * 
     * @param string            $key
     * @param Config_File|array $value
     */
    public function offsetSet($key, $value)
    {
       if (is_scalar($value) || is_resource($value)) throw new Exception("Unable to set '$key' to '$value' for Config_Dir '{$this->_path}': Creating a section requires setting an array or Config_File object");
       
       if ($value instanceof Config_File) {
            $config = $value;

            if (isset($config->_path)) throw new Exception("Unable to set '$key' to Config_File object for Config_Dir '{$this->_path}': Config_File path is already set'");
            
            if (isset($config->_ext) && isset($this->_ext) && $config->_ext != $this->_ext) throw new Exception("Unable to create section '$key': Extension specified for Config_Dir '{$this->_path}' and extension specified for Config_File object setting are different");
            if (!isset($config->_ext) && !isset($this->_ext)) throw new Exception("Unable to create section '$key': No extension specified for Config_Dir '{$this->_path}' or for the Config_File object setting");
            if (!isset($config->_ext)) $config->_ext = $this->_ext;
            
            if (isset($this->_transformer)) $config->_transformer = $this->_transformer;
            
            $config->_path = $config instanceof Config_Dir ? $this->_path->dir($key) : $this->_path->file("$key.{$this->_ext}");
       } else {
            if (!$this->_ext) throw new Exception("Unable to create section '$key': No extension specified for Config_Dir '{$this->_path}', creating a section requires setting a Config_File object"); 

            $options = array();
            if ($this->_transformer) $options['transformer'] = $this->_transformer;
                    
            $config = new Config_File(array($this->_path->file("$key.{$this->_ext}")), $options);
            $config->exchangeArray((array)$value);
       }
       
       parent::offsetSet($key, $config);
    }
    
    /**
     * ArrayAccess; Returns the value at specified offset, loading the section if needed.
     * 
     * @param string $key
     * @return Config_File
     */
    public function offsetGet($key)
    {
        if (parent::offsetExists($key)) return parent::offsetGet($key);
        
        $dirname = "{$this->_path}/{$key}";
        $filename = "{$dirname}.{$this->_ext}";
        
        $options = array();
        if ($this->_transformer) $options['transformer'] = $this->_transformer;
        
        if (is_dir($dirname)) {
            parent::offsetSet($key, new Config_Dir(Fs::dir($dirname), $options));
        } elseif (file_exists($filename)) {
            parent::offsetSet($key, new Config_File(Fs::file($filename), $options));
        } else {
            trigger_error("Configuration section '$key' doesn't exist for '{$this->_path}'", E_WARNING);
            return null;
        }
        
        return parent::offsetGet($key);
    }
    
    /**
     * ArrayAccess; Unsets an offset.
     * 
     * @param string $key
     */
    public function offsetUnset($key)
    {
        parent::offsetSet($key, null);
    }
    
    
    /**
     * Load all settings (eager load).
     * (fluent interface)
     * 
     * @return Config_Dir
     */
    protected function loadAll()
    {
        if (!isset($this->_path) || !($this->_path instanceof Fs_Dir)) throw new Exception("Unable to create Config object: Path not specified or not instance of Fs_Dir");
        
        $options = array();
        if ($this->_transformer) $options['transformer'] = $this->_transformer;
        if ($this->_ext) $options['ext'] = $this->_ext;
        $options['loadall'] = true;
        
        foreach ($this->_path as $key=>$file) {  
            if ($file instanceof Fs_Dir) {
                if (!isset($this[$file->filename()])) parent::offsetSet($file->filename(), new Config_Dir($file, $options));
            } elseif ($file instanceof Fs_File) {
                if (isset($this->_ext) && substr($file, -strlen($this->_ext)) != $this->_ext) continue;
                if (!isset($this[$file->filename()])) parent::offsetSet($file->filename(), new Config_File($file, $options));
            }
        }
        
        return $this;
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