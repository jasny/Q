<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Transform.php';

/**
 * Transform a xml into an array
 *
 * @package Transform
 */
class Transform_Unserialize_XML extends Transform
{	
    /**
     * Create an using the values from 
     * @param array $values
     * @return array
     */
	protected function exec(&$array, $stack, $value) {
		if ($stack) {
			$key = array_shift($stack);
			$this->exec($array[$key], $stack, $value);
			return $array;
		}
            if (is_array($array)) $array[] = $value;
            else if($array) $array = array($array, $value);
            else $array = $value;
	}

    /**
     * Start the transformation and return the result.
     *
     * @param string $data  The xml that will be transformed into an array
     * @return array
     * @todo: take care of duplicate keys
     */
    public function process($data = null)
    {   
        if ($this->chainInput) $data = $this->chainInput->process($data);
        
        if (!is_string($data)) throw new Exception('Unable to transform XML into Array: incorect data type');
        if (is_file($data)) $data = file_get_contents($data);
        
		$xmlParser = xml_parser_create(); 
        xml_parser_set_option($xmlParser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($xmlParser, XML_OPTION_SKIP_WHITE, 1);
        if (!xml_parse_into_struct($xmlParser, $data, $values)) throw new Exception('Unable to transform XML into Array : '.xml_error_string(xml_get_error_code($xmlParser))); 
		xml_parser_free($xmlParser); 

        $array = array();
        $stack = array();
        foreach($values as $val) {
            if($val['type'] == "open" && $val['level'] != 1) {
                   array_push($stack, $val['tag']);
            } elseif($val['type'] == "close") {
                   array_pop($stack);
            } elseif($val['type'] == "complete") {
               array_push($stack, $val['tag']);
               $this->exec($array, $stack, array_key_exists('value', $val) ? $val['value'] : '');
               array_pop($stack);
            }
        }
        
        return $array;
    }
}
