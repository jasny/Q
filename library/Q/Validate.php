<?php
require_once 'Q/Validator.php';

/**
 * Abstract class for validators.
 * 
 * If value is null or an empty string, validators return null.
 *
 * @package Validate
 */
abstract class Validate implements Validator
{
	/**
	 * Available drivers
	 * @var array
	 */
	public $drivers = array (
	  'regex' => 'Validator_Regex',
	  'email' => 'Validator_Email',
	  'date'  => 'Validator_Date',
	  'time'  => 'Validator_Time',
	  'NL-bankaccount' => 'Validate_NL_Bankaccount',
	
	  'fn'       => 'Validator_Function',
	
	  'compare'  => 'Validator_Compare',
	  'empty'    => 'Validator_Empty',
	  'required' => array('Validator_Empty', array('negate'=>true)),
	
	  'length'    => 'Validator_Length',
	  'minlength' => array('Validator_Length', array('type'=>'min')),
	  'maxlength' => array('Validator_Length', array('type'=>'max'))
	);
	
	
	/**
     * Negate validation: return true when validation fails
     * @var boolean
     */
    public $negate = false;

    
    /**
     * Magic method for using the object as function; Alias of Validate::validate().
     * 
     * @param mixed $value  Value to be validated
     * @return boolean
     */
    public final function __invoke($value)
    {
    	return $this->validate($value);
    }
    
    /**
     * Parse expression, creating a (composite) Validate object.
     * 
     * @param string $expression
     * @return Validate
     */
    public static function parseExpression($expresision)
    {
    	$tokens = token_get_all($expresision);
    	var_dump($tokens);
    }
}
