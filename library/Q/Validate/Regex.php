<?php
namespace Q;

require_once('Q\Validate.php');

/**
 * Validate values using a regular expression.
 *
 * @package Validate
 */
class Validate_Regex extends Validate
{
    /**
     * Registered regular expressions.
     * @var array
     */
    static public $patterns = array(
      'alpha'          => '/^[a-zA-Z]*$/',
      'alphanumeric'   => '/^[a-zA-Z0-9]*$/',
      'word'           => '/^\w*$/',
      'numeric'        => '/^-?\d*[\.,]?\d+$/',
      'integer'        => '/^-?\d*$/',
      'nopunctuation'  => '/^[^\(\)\.\/\*\^\?#!@$%+=,\"\'><~\[\]{}]*$/',
    
      'creditcard'     => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$/',
      'domain'         => '/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/',
      'website'        => '/^https?://([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}(\?[a-z0-9\-._~%!$&\'()*+,;=:@/?]*)?(\#[a-z0-9\-._~%!$&\'()*+,;=:@/?]*)?$/',
      'hyperlink'      => '^([a-z][a-z0-9+\-.]*:(//([a-z0-9\-._~%!$&\'()*+,;=]+@)?([a-z0-9\-._~%]+|\[[a-f0-9:.]+\]|\[v[a-f0-9][a-z0-9\-._~%!$&\'()*+,;=:]+\])(:[0-9]+)?(/[a-z0-9\-._~%!$&\'()*+,;=:@]+)*/?|(/?[a-z0-9\-._~%!$&\'()*+,;=:@]+(/[a-z0-9\-._~%!$&\'()*+,;=:@]+)*/?)?)|([a-z0-9\-._~%!$&\'()*+,;=@]+(/[a-z0-9\-._~%!$&\'()*+,;=:@]+)*/?|(/[a-z0-9\-._~%!$&\'()*+,;=:@]+)+/?))(\?[a-z0-9\-._~%!$&\'()*+,;=:@/?]*)?(\#[a-z0-9\-._~%!$&\'()*+,;=:@/?]*)?',
      'ip'             => '/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/',
      'ip6'            => '/^(?:[A-F0-9]{1,4}:){7}[A-F0-9]{1,4}$/i',
      
      'AT-socialsecurity' => '/^\d{4}(?:0[1-9]|[12]\d|3[01])(?:0[1-9]|1[0-5])\d{2}$/',
      'BE-socialsecurity' => '/^\d{2}(?:[024][1-9]|[135][0-2])(?:0[1-9]|[12]\d|3[01])\d{5}$',
      'BG-socialsecurity' => '/^\d{2}(?:[024][1-9]|[135][0-2])(?:0[1-9]|[12]\d|3[01])[-+]?\d{4}$/',
      'CA-socialsecurity' => '/^[1-9]\d{2}[- ]?\d{3}[- ]?\d{3}$/',
      'CN-socialsecurity' => '/^\d{6}(?:19|20)\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])\d{4}$/',
      'HR-socialsecurity' => '/^(?:0[1-9]|[12]\d|3[01])(?:0[1-9]|1[0-2])(?:9\d{2}|0[01]\d)\d{6}$/',
      'DK-socialsecurity' => '/^(?:0[1-9]|[12]\d|3[01])(?:0[1-9]|1[0-2])\d{2}[-+]?\d{4}$/',
      'FI-socialsecurity' => '/^(?:0[1-9]|[12]\d|3[01])(?:0[1-9]|1[0-2])\d{2}[-+a]\d{3}[a-z0-9]$/',
      'IN-socialsecurity' => '/^[a-z]{3}[abcfghjlpt][a-z]\d{4}[a-z]$/',
      'IT-socialsecurity' => '/^(?:[bcdfghj-np-tv-z][a-z]{2}){2}\d{2}[a-ehlmprst](?:[04][1-9]|[1256]\d|[37][01])(?:\d[a-z]{3}|z\d{3})[a-z]$/',
      'NO-socialsecurity' => '/^(?:0[1-9]|[12]\d|3[01])(?:[04][1-9]|[15][0-2])\d{7}$/',
      'RO-socialsecurity' => '/^[1-8]\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])(?:0[1-9]|[1-4]\d|5[0-2]|99)\d{4}$/',
      'KR-socialsecurity' => '/^\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])-[0-49]\d{6}$/',
      'SE-socialsecurity' => '/^(?:19|20)?\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[-+]?\d{4}$/',
      'TW-socialsecurity' => '/^[a-z][12]\d{8}$/',
      'UK-socialsecurity' => '/^[abceghj-prstw-z][abceghj-nprstw-z] ?\d{2} ?\d{2} ?\d{2} ?[a-dfm]?$/',
      'US-socialsecurity' => '/^(?!000)(?!666)(?:[0-6]\d{2}|7(?:[0-356]\d|7[012]))[- ](?!00)\d{2}[- ](?!0000)\d{4}$/',
    
      'BE-licenseplate' => '/^[A-Z\d]{3}\.[A-Z\d]{3}|[A-Z]\.\d{3}\.[A-Z]|[A-Z]{2}\.\d{3}|[A-Z]\.\d{4}$/',
      'IN-licenseplate' => '/^(?:dl ?[1-9]?\d ?[cprstvy]|[a-z]{2} ?\d{1,2}) ?[a-z]{0,2} ?\d{1,4}$/',
      'NL-licenseplate' => '/^\d{2}-[A-Z]{3}-\d|[A-Z\d]{2}-[A-Z\d]{2}-[A-Z\d]{2}$/',
      
      'international-phone' => '/^+(?:[17]|\d{2,4})([-. ]\d+)+$/',
      'BE-phone' => '/^0[1-9](?:\d[-. ]?|[-. ]\d)\d{6}$/',
      'CA-phone' => '/^\(?\b(\d{3})\)?[-. ]?(\d{3})[-. ]?([\d{4})$/',
      'MX-phone' => '/^\d{2}(?:\d[-. ]?|[-. ]\d)\d{7}$/',
      'NL-phone' => '/^0(6[-. ]?\d{2}|[1-57]\d(?:\d[-. ]?|[-. ]\d)\d{6}$/',
      'US-phone' => '/^\(?(\d{3})\)?[-. ]?(\d{3})[-. ]?(\d{4})$/',
    );
 
    /**
     * The regular expression.
     * @var string
     */
	public $pattern;
	

    /**
	 * Class constructor.
	 * 
	 * @param array $props
	 */
	public function __construct($props=array())
	{
		if (isset($props[0])) $props['pattern'] = $props[0];
		unset($props[0]); 
		
		parent::__construct($props);
	}

    /**
     * Validates a value using a regular expression.
     *
     * @param string $value  Value to be checked
     * @return boolean
     */
    function validate($value)
    {
    	if (!isset($value) || $value === "") return null;
    	
    	if (ctype_alpha($this->pattern[0])) {
			if (!isset(self::$patterns[$this->pattern])) throw new Exception("Unable to validate using a regex: Unknown pattern '{$this->pattern}'.");
			$regex = self::$patterns[$this->pattern];
    	} else {
    		$regex = $this->pattern;
    	}
    	
	   	return (bool)preg_match($regex, (string)$value);
    }
}
