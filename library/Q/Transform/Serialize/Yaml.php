<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';
require_once 'Q/Transform/Unserialize/Yaml.php';

/**
 * Transform array to yaml
 *
 * Options:
 *   fastDump   set true to use syck_dump
 *   
 * @package Transform
 */
class Transform_Serialize_Yaml extends Transform
{
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'yaml';
    
    /**
     * Set fastDump = true to use syck_dump for fast transform
     * @var boolean
     */
    public $fastDump=false;
	
    /**
     * Get a transformer that does the reverse action.
     * 
     * @param Transformer $chain
     * @return Transformer
     */
    public function getReverse($chain=null)
    {
        $ob = new Transform_Unserialize_Yaml($this);
        if ($chain) $ob->chainInput($chain);
        return $this->chainInput ? $this->chainInput->getReverse($ob) : $ob;  
    }
    		
    /**
     * Transform array to yaml and return the result
     *
     * @param mixed $data
     * @return string
     */
    public function process($data)
    {
        if ($this->chainInput) $data = $this->chainInput->process($data);

        if ($this->fastDump) return syck_dump($data);
        return $this->serialize($data);
    }
    
    /**
	 * Searialize setting as YAML config.
	 * The yaml config will be nicely formatted, contrary to syck_dump.
	 *
	 * @param array $data
	 * @param int $level Indent level
	 * @return string
	 * @ todo : make it work with other variables beside arrays
	 */
    protected function serialize($data, $level=0)
    {
        if (empty($data)) return '';
        
        $maxlen = 0;
        foreach (array_keys($data) as $key) {
            $maxlen = max($maxlen, is_int($key) ? 1 : (preg_match('/[^\w-\.]/', $key) ? strlen(addcslashes($key, "'")) + 2 : strlen($key)));
        }
        
        $indent = str_repeat(' ', $level);
        $yaml = "";
        foreach ($data as $key=>$value) {
            if ($value === null) continue;
            
            if (is_int($key)) $key = '-';
              elseif (preg_match('/[^\w-\.]/', $key)) $key = "'" . addcslashes($key, "'") . "'";
            
            if (is_array($value)) {
                $yaml .= str_repeat(' ', $level) . "$key:\n" . $this->serialize($value, $level+1) . "\n";
            } else {
                if (is_string($value) && ($value === '' || preg_match('/[^\w-\.]/', $value))) $value = '"' . addcslashes($value, '"') . '"';
                $yaml .= $indent . str_pad($key . ':', $maxlen+1, ' ', STR_PAD_RIGHT) . " $value\n";
            }
        }
        
        return preg_replace(array('/\n{3,}/', '/\n\n$/'), array("\n\n", "\n"), $yaml);
    }     
}
