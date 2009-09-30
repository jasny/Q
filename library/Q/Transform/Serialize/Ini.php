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
	protected $writer = "";
		
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
        foreach($data as $key =>&$value) {
            if (is_array($value)) {
                $this->writer .= "\n[{$key}]\n";
                    foreach($value as $k=>$v) {
                        $this->ProcessIniSetting($k, $v);
                    }
            }else {
                $this->writer .= "{$key} = \"$value\"\n";
            }
        }
        return $this->writer;
    }

    /**
    * Transform a setting from array to ini format
    *
    * @param mixed $key
    * @param mixed $value
    */
    protected function ProcessIniSetting($key, $value)
    {
        if (is_array($value))
        foreach($value as $k=>$v) {
          if (!is_int($k) || is_array($v)) throw new Transform_Exception("Unable to serialize data to a ini string: Invalid array structure.");
          $this->writer .= "{$key}[] = \"$v\"\n";
        }
        else $this->writer .= "{$key} = \"$value\"\n";
   }
}
