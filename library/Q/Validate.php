<?php
require_once('DynamicProperties.php');
require_once('Q/Factory.php');
require_once('Q/Config/Collection.php');

/**
 * Abstract class for validators.
 * 
 * If value is null or an empty string, validators return null.
 *
 * @package Validate
 */
abstract class Validator
{
	/**
	 * Available drivers
	 * @var array
	 */
	public $drivers = array (
	  'regex'          => 'Validator_Regex',
      'lettersonly'    => array('Validator_Regex', array('lettersonly')),
      'lettersnumbers' => array('Validator_Regex', array('lettersnumbers')),
      'alphanumeric'   => array('Validator_Regex', array('alphanumeric')),
      'numeric'        => array('Validator_Regex', array('numeric')),
      'integer'        => array('Validator_Regex', array('integer')),
      'nopunctuation'  => array('Validator_Regex', array('nopunctuation')),	
	
	  'email' => 'Validator_Email',
	  'date'  => 'Validator_Date',
	  'time'  => 'Validator_Time',
	  'NL-bankaccount' => 'Validate_NL_Bankaccount',
	
	  'callback' => 'Validator_Callback',
	  'fn'       => 'Validator_Callback',
	
	  'compare'  => 'Validator_Compare',
	  'empty'    => 'Validator_Empty',
	  'required' => array('Validator_Empty', array('negate'=>true)),
	
	  'length'    => 'Validator_Length',
	  'minlength' => array('Validator_Length', array('min')),
	  'maxlength' => array('Validator_Length', array('max'))
	);
	
	
	/**
     * Negate validation: return true when validation fails
     * @var boolean
     */
    public $negate = false;

    
    /**
     * Validate a value.
     *
     * @param mixed $value  Value to be validated
     * @return boolean
     */
    public abstract function validate($value);
    
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
    
    
    /**
     * Evaluate a boolean expression using values true, false and unknown (null).
     * 
     * true and null = null;  false and null = false;  null and null = null
     * true or null = true;   false or null = null;    null or null = null
     * true xor null = null;  false xor null = null;   null xor null = null
     * not null = null
     * 
     * @param  string  $exp
     * @return boolean|null
     */
	public static function eval3state($exp)
	{	
		while (preg_match('/\(([^\(\)]*)\)/', $exp)) $exp = preg_replace_callback('/\(([^\(\)]*)\)/', array(__CLASS__, 'eval3state_exp'), $exp);
		return self::eval3state_exp($exp, true);
	}    

    /**
     * Eval a 3state boolean expression without parentheses.
     * Callback function for eval3state().
     * 
     * @param   string   $exp
     * @param   boolean  $as_bool  Return the result as a boolean instead of a string
     * @return  mixed   
     */
	protected static function eval3state_exp($exp, $as_bool=false)
	{
		$exp = is_array($exp) ? trim($exp[1]) : trim($exp);
		
		if (!preg_match('/\s/', $exp)) {
			if (!$as_bool) return $exp;
			return $exp == 'true' ? true : ($exp == 'false' ? false : null);
		}
		
		$parts = preg_split('/\s+/', $exp);
		for ($i=-1; $i<sizeof($parts); $i+=2) {
			$opr = ($i == -1) ? null : $parts[$i];
			
			$inv = false;
			while ($parts[$i+1] == 'not' || $parts[$i+1] == '!') {
				$inv = !$inv;
				$i++;
			}
			
			$val = $parts[$i+1] == 'true' ? true : ($parts[$i+1] == 'false' ? false : null);
			if ($inv && $val !== null) $val = !$val;
			
			switch ($opr) {
				case null: $res = $val; break;
				case 'and': $res = (($res === null || $val === null) && $res !== false && $val !== false ? null : ($res and $val)); break;
				case 'or': $res = (($res === null || $val === null) && $res !== true && $val !== true ? null : ($res or $val)); break;
				case 'xor': $res  = ($res === null || $val === null ? null : ($res xor $val)); break;
			}
		}
		
		if ($as_bool) return $res;
		 else return $res === null ? ' null ' : ($res ? ' true ' : ' false ');
	}
}
