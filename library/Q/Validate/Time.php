<?php
require_once('Validator/Regex.php');

/**
 * Time validation
 *
 * NOTE: If the value is empty, the validation will result succes
 *
 * @category     Validation
 * @package      Validator
 * @author       Ralph Hoedeman <ralph@bean-it.nl>
 * @version      1.0
 * @since        PHP5
 *
 * @todo should merge with Validator_Date
 */
class Validator_Time extends Validator_Regex 
{
    /**
     * Property get method for $this->pattern
     * @return  string
     */
	protected function __getPattern()
	{
		return !empty($this->_pattern) ? $this->_pattern : '/^([0-9]|[0-1][0-9]|[2][0-3])(.)([0-5][0-9])$/';
	}
	
    /**
     * Validates a value using a regular expression
     *
     * @param     string    $value         Value to be checked
     * @param     string    $pattern       A format from Date_Local. Leave NULL to use $this->pattern.
     * @return    boolean   true if $value is valid
     */
    function validate($value, $pattern=null)
    {
    	if ($value === null || $value === "") return null;

    	if (!isset($pattern) && (!isset($this) || !($this instanceof self))) $pattern =  parent::getRegex('time');
		if (isset($pattern)) $pattern = self::getRegex($pattern);
  	
    	if (is_int($value) && (isset($pattern) ? $pattern : $this->pattern)) return !$this->negate;
    	return parent::validate($value, $pattern);
    }
}
?>