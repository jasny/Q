<?php
require_once('../Validator/Regex.php');

/**
 * Validation of a dutch back account using a eleven test.
 * Can validate giro as well as bank accounts.
 *
 * @package Validate
 */
class Validate_NL_BankAccount 
{
	/** Match giro account */
	const GIRO = 1;
	/** Match bank account */
	const BANK = 2;
	/** Match savings account */
	const SAVINGS = 4;
	
	/**
	 * Which type of accounts to match.
	 * @var int
	 */
	public $type = 7;
	
	
	/**
	 * Class constructor.
	 * 
	 * @param array $props
	 */
	public function __construct($props=array())
	{
		if (isset($props[0])) $props['type'] = $props[0];
		unset($props[0]); 
		
		if (isset($props['type']) && is_string($props['type']) && !ctype_digit($props['type'])) {
			$types = explode($props['type']);
			$props['type'] = 0;
			
			foreach ($types as $type) {
				switch ($type) {
					case 'giro': $props['type'] = $props['type'] | self::GIRO; break;
					case 'bank': $props['type'] = $props['type'] | self::BANK; break;
					case 'savings': $props['type'] = $props['type'] | self::SAVINGS; break;
					default: trigger_error("Unknown type '{$type}' specified for dutch bank account.", E_USER_NOTICE);
				}
			}
			
			if ($props['type'] == 0) throw new Exception("The specified type" . (count($type) == 1 ? '' : 's') . " '" . join(',', $types) . "' for dutch bank accounts are invalid.");
		}
		
		parent::__construct($props);
	}
	
	
    /**
     * Validate a value.
     *
     * @param string $value  Value to be checked    
     * @return boolean
     */
    function validate($value)
    {
    	if (!isset($value) || $value === "") return null;
    	
		if ($this->type & self::GIRO && preg_match('/^\d{3,7}$/')) return true;
		if ($this->type > 1 && !preg_match('/^\d{9}' . ($this->type & self::SAVINGS ? '\d' . ($this->type & self::BANK ? '?' : '') : '') . '$/', $value)) return false;
		
    	$sum = 0;
    	$len = strlen($value);
        for ($i=$len; $i > 0; $i--) $sum += $i * $value[$len - $i];
		return $sum % 11 == 0;
    }
}
