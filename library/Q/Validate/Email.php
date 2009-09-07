<?php
namespace Q;

require_once('Validate/Regex.php');

/**
 * Email validation.
 *
 * @category     Validation
 * @package      Validator
 * @author       Arnold Daniels <arnold@bean-it.nl>
 * @version      1.0
 * @since        PHP5
 */
class Validate_Email extends Validator_Regex
{
	/**
	 * Regular expression to check for valid e-mail address.
	 * @var string
	 */
	public $pattern = '/^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$/';
	
   	/**
     * Check nameserver for email address.
     * @var boolean
     */
   	public $check_dns = false;
   	
   	
	/**
	 * Class constructor.
	 * 
	 * @param array $props
	 */
	public function __construct($props=array())
	{
		Validate::__construct($props); // Don't set pattern based on name
	}
	
    /**
     * Check if $value is a valid e-mail address.
     *
     * @return boolean
     */
    public function validate($value)
    {
    	return (parent::validate($value) != $this->negate && (!$this->check_dns || self::checkDNS($value))) xor $this->negate;
    }
    
    /**
     * Check DNS for e-mail address.
     * 
     * @param string $value
     * @return boolean
     */
    public static function checkDNS($value)
    {
    	$domain = substr(strstr($value, '@'), 1);
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
	}
}
