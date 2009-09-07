<?php
require_once('Validator/Regex.php');
require_once('Date/Local.php');

/**
 * Date validation
 *
 * Use a format from Date_Local as pattern
 * @see Date_Local
 *
 * NOTE: If the value is empty, the validation will result succes
 *
 * @category     Validation
 * @package      Validator
 * @author       Arnold Daniels <arnold@bean-it.nl>
 * @version      1.0
 * @since        PHP5
 */
class Validator_Date extends Validator_Regex 
{
    /**
     * Regulair expressions for each format constant
     * @var  array
     */
	static public $patterns = array(
		'eu' => array(	
		    'date_numeric' => '(?:(?:(?:(31){delim}?(0?[13578]|1[02]))|(?:(29|30){delim}?(0?[1,3-9]|1[0-2]))){delim}?((?:1[6-9]|[2-9]\d)?\d{2}))|(?:(29){delim}?(0?2){delim}?((?:(?:1[6-9]|[2-9]\d)?(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00))))|(0?[1-9]|1\d|2[0-8]){delim}?((?:0?[1-9])|(?:1[0-2])){delim}?((?:1[6-9]|[2-9]\d)?\d{2})?',
			'date_text' => '((?:31(?!\s*(?:{MON_2}|{MON_4}|{MON_6}|{MON_9}|{MON_11})))|(?:(?:30|29)(?!\s*{MON_2}))|(?:29(?=\s*{MON_2}\s*(?:(?:(?:1[6-9]|[2-9]\d)(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))))|(?:0?[1-9])|1\d|2[0-8])\s*({MON_1}|{MON_2}|{MON_3}|{MON_4}|{MON_5}|{MON_6}|{MON_7}|{MON_8}|{MON_9}|{MON_10}|{MON_11}|{MON_12})\s*((?:1[6-9]|[2-9]\d)?\d{2})?',
			'time' => '(\d{2}):?(\d{2}):?(\d{2})?(?:\.(\d+))?',	),

		'us' => array(			
			'date_numeric' => '(?:(?:(0?[13578]|1[02]){delim}?(0?[1-9]|[1-2][0-9]|3[01]))|(?:([469]|11){delim}?(0?[1-9]|[1-2][0-9]|30))|(?:(0?2){delim}?(0?[1-9]|[1-2][0-9]))){delim}?((?:1[6-9]|[2-9]\d)?\d{2})',
			'date_text' => '(?:(?:({MON_1}|{MON_3}|{MON_5}|{MON_7}|{MON_8}|{MON_10}|{MON_12}){delim}?(0?[1-9]|[1-2][0-9]|3[01]))|(?:({MON_4}|{MON_6}|{MON_9}|{MON_11}){delim}?(0?[1-9]|[1-2][0-9]|30))|(?:({MON_2}){delim}?(0?[1-9]|[1-2][0-9])))\,?\s*{delim}?((?:1[6-9]|[2-9]\d)?\d{2})',
			'time' => '(\d{2}):?(\d{2}):?(\d{2})?(?:\.(\d+))?',
			'time_ampm' => '(?:(?:(0[1-9])|([1-9])|(1[0-2]))\:([0-5][0-9])(?:\s|(?:\:([0-5][0-9])\s))([AM|PM|am|pm]{2,2}))',
		),

		'iso' => array(
			'date' => '(\d{4}){delim}?(\d{2}){delim}?(\d{2})',
			'time' => '(\d{2}):?(\d{2}):?(\d{2})?(?:\.(\d+))?(Z|[\+\-]\d{2}(?:\:d{2})?)?',
		),

		'unix' => '\d+',
	);
	
    /**
     * Property get method for $this->pattern
     * @return  string
     */
	protected function __getPattern()
	{
		return !empty($this->_pattern) ? $this->_pattern : Date_Local::FORMAT_ANY;
	}
	
    /**
     * Lookup a regulair expression from Date_Local
     *
     * @param  string   $pattern       A format from Date_Local
     * @return string
     */
    static function getRegex($pattern)
    {
    	return Date_Local::regex($pattern);
    }
	
    /**
     * Validates a value using a regular expression
     *
     * @param     string    $value         Value to be checked
     * @param     string    $pattern       A format from Date_Local. Leave NULL to use $this->pattern.
     * @return    boolean   true if $value is valid
     */
    function validate($value)
    {
    	if ($value === null || $value === "") return null;
    	
    	if (is_int($value) && $this->pattern & Date_Local::FORMAT_UNIXTIME) return true;
    	return parent::validate($value, self::getRegex($this->pattern));
    }
}
?>