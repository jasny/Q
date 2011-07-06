<?php
require_once('../Validator/Regex.php');

/**
 * Validation of a dutch social security number using a eleven test.
 *
 * @package Validate
 */
class Validate_NL_BSN
{
    /**
     * Validate a value.
     *
     * @param string $value  Value to be checked    
     * @return boolean
     */
    function validate($value)
    {
    	if (!isset($value) || $value === "") return null;
    	
		if (!preg_match('/^\d{9}$/', $value)) return false;
		
    	$sum = 0;
        for ($i=9; $i > 0; $i--) $sum += ($i == 1 ? -1 : $i) * $value[9 - $i];
		return $sum && $sum % 11 == 0;
    }
}
