<?php
require_once ('Validator.php');

/**
 * Rule to compare two values
 *
 * @category     Validation
 * @package      Validator
 * @author       Arnold Daniels <arnold@bean-it.nl>
 * @version      1.0
 * @since        PHP5
 */
class Validator_Compare extends Validator
{
    /**
     * Possible operators to use.
     * @var array
     */
    static private  $_mOperators = array(
        'eq'  => '==',
        'neq' => '!=',
        'lte' => '<=',
        'gte' => '>=',
        'lt'  => '<',
        'gt'  => '>',
        'b_all' => '&;==',
        'b_any' => '&',
        'b_none'  => '&;!',
        'a_all' => 'a_all',
        'a_any' => 'a_any',
    );

	/**
     * Description for operators, used to create description
     * @var     string
     */
	public static $operatorDescription = array(
        '==' =>	'equal to',
        '!=' =>	'different from',
        '<=' => 'less than or equal to',
        '>=' =>	'greater than or equal to',
        '<' =>	'less than',
        '>' =>	'greater than',
        '&;==' => 'all of',
        '&' => 'any of',
        '&;!' => 'none of',
        'a_all' => 'all of',
        'a_any' => 'any of',
	);
	
	/**
     * Description for current date, used to create description for {date}
     * @var     string
     */
	public static $curdateDescription = 'the current date';
		    
    /**
     * Functions for comparing values
     * One function for each operator. Created on demand
     * @var array
     */
    static private  $_mOperatorFunctions = array();

    
    /**
     * Compareson operator
     * May only be set at construction
     * @var  string
     */
	private $_operator = "==";

    /**
     * Compareson operator
     * May only be set at construction
     * @var  string
     */
	private $_valueCompare;

	
	/**
     * Propery get method for $this->description
     * @return    string
     */
    function __getDescription()
    {
    	$desc = self::$operatorDescription[self::findOperator($this->_operator)];
        if (!empty($this->_valueCompare)) $desc .= ($desc ? " " : "") . ($this->_valueCompare == '#date#' || $this->_valueCompare == '#now#' ? self::$curdateDescription : (is_array($this->_valueCompare) ? join(', ', $this->_valueCompare) : $this->_valueCompare));
        return $desc;
    } 
    	
	
	/**
     * Returns the correct operator to use for comparing the values
     *
     * @param     string     $operator    Operator name
     * @param     string     $default     Returned when operator cannot be found, returns $operator by is $default is NULL
     * @return    string     Operator to use for validation
     */
    static function findOperator($operator, $default=null)
    {
        switch (true) {
        	case empty($operator):							return '==';
        	case isset(self::$_mOperators[$operator]):		return self::$_mOperators[$operator];
	        case in_array($operator, self::$_mOperators):	return $operator;
	        default:						            	return isset($default) ? $default : $operator;
        }
    }

	/**
     * Check the expression can be passed as a valid operator 
     *
     * @param     string     $operator            Operator name
     * @param     boolean    $allowExpression     Allow expressions including values, like >10
     * @return    boolean
     */
    static function isValidOperator($operator, $allowExpression=true)
    {
    	$operator = trim($operator);
		$result = isset(self::$_mOperators[$operator]) || in_array($operator, self::$_mOperators) || ($allowExpression && preg_match('/^(!|not\b)?\s*(' . join('|', array_map('preg_quote', self::$_mOperators)) . ')\s*(' . Validator::PREG_VALUE . ')$/i', $operator));
		return $result;
    }
        
    
	/**
     * Returns the function for comparing the values
     *
     * @param     string     $operator    Operator name
     * @return    string     Operator to use for validation
     */
    function operatorFunction($operator=null)
    {
    	if (!isset($operator) && isset($this) && $this instanceof self) $operator = $this->_operator;
    	$operator = self::findOperator($operator);
    	if (isset(self::$_mOperatorFunctions[$operator])) return self::$_mOperatorFunctions[$operator];
    	
    	switch (true) {
    		case $operator == 'a_any': $cmp = '> 0';
    		case $operator == 'a_all': if (!isset($cmp)) $cmp = '==count($a)';
	    		$compareFn = create_function('$a, $b', 'return count(array_intersect($a, $b)) ' . $cmp . ';');
	    		break;
	    		
	    	case strpos($operator, ';') !== false:
    			$compareFn = "";
    			foreach (explode(';', $operator) as $operator) $compareFn .= ($operator=='!' ? '$a = !$a; ' : '$a = $a' . $operator . '$b; ');
    			$compareFn = create_function('$a, $b', $compareFn . 'return $a;');
    			break;
    			
	    	default:
    			$compareFn = create_function('$a, $b', 'return $a ' . $operator . ' $b;');
    	}
    	
        self::$_mOperatorFunctions[$operator] = $compareFn;
        return $compareFn;
    }
    

	/**
     * Class constructor
     *
     * @param    string     $operator         Compareson operator
     * @param    string     $valueCompare     Value to compare with. Null is compare among eachother
     * @return   void
     */
	function __construct($operator="==", $valueCompare=null)
	{
		if (!isset(self::$_mOperators[$operator]) && !in_array($operator, self::$_mOperators) && preg_match('/^(!|not\b)?\s*(' . join('|', array_map('preg_quote', self::$_mOperators)) . ')\s*(' . Validator::PREG_VALUE . ')$/i', $operator, $matches)) {
			$this->_operator = $matches[2];
			$this->_valueCompare = Validator::parseValue($matches[3]);
			$this->negate = (bool)$matches[1];
		} else {
			if (isset($operator)) $this->_operator = $operator;
			if (isset($valueCompare)) $this->_valueCompare = $valueCompare;
		}
	}

	/**
     * Factory Clone method
     *
     * @param    string     $valueCompare     Value to compare with. Null is compare among eachother
     * @return   void
     */
	function __factoryClone($valueCompare=null)
	{
		if (isset($valueCompare)) $this->_valueCompare = $valueCompare;
	}
		
	
    /**
     * Compare the values.
     * Return null if $value or $valueCompare is null or an empty string
     *
     * @param    mixed    $value          A value or an array with values
     * @param    mixed    $operator       The operator. Uses $this->operator by default
     * @param    mixed    $valueCompare   Compare this value to $value: $value $operator $valueCompare -> 10 > 9
     * @return   boolean
     */
    function validate($value, $valueCompare=null)
    {
    	if (!isset($valueCompare)) $valueCompare = $this->_valueCompare;
    	if ($value === null || $value === "" || $valueCompare === null || $valueCompare === "") return null;

    	$compareFn = self::operatorFunction($this->_operator);
    	if (empty($compareFn)) throw new Validator_Exception("Unable to perform compare for operator '" . $this->_operator . "'");
    	
		$valueCompare = self::_convertDateLocal($valueCompare, $value);
		$value = self::_convertDateLocal($value, $valueCompare);
    	$value = is_array($value) ? $value : array($value);
		
    	if (preg_match('/^a_/', $this->_operator)) {
    		if (!$compareFn($value, is_array($valueCompare) ? $valueCompare : array($valueCompare))) return $this->negate;
    	} else {
	    	foreach ($value as $key=>$val) {
	    		$valCmp = !is_array($valueCompare) ? $valueCompare : (isset($valueCompare[$key]) ? $valueCompare[$key] : null);
	    		if (!$compareFn($val, $valCmp)) return $this->negate;
	    	}
    	}
    	
    	return !$this->negate;
    }
    
    /**
     * Convert a Date_Local object to an int or ISO string
     *
     * @param    mixed    $value          A value or an array with values
     * @param    mixed    $valueBaseOn    The value to determine to convert to int or string
     * @return   mixed
     */
    static protected function _convertDateLocal($value, $valueBaseOn)
    {
    	if (is_array($valueBaseOn)) $valueBaseOn = reset($valueBaseOn);
    	
    	foreach (is_array($value) ? $value : array($value) as $key=>$val) {
    		if (class_exists('Date_Local') && $val instanceof Date_Local) {
    			$val = $val->getDate(is_int($valueBaseOn) ? Date_Local::FORMAT_UNIXTIME : Date_Local::FORMAT_ISO);
    			is_array($value) ? $value[$key] = $val : $value = $val;
    		}
    	}
    	
    	return $value;
    }
    
    /**
     * Return the javascript for validation
     *
     * @return   array      array(args, function body)
     */
    function getScript($field)
    {
    	$operator = self::findOperator($this->_operator);

    	switch (true) {
    		case $operator == 'a_all': $script_chkcnt = "&& (++cnt == value.length)";
    		case $operator == 'a_any':
    			$script = "
if(typeof valcmp != 'object' || valcmp.lenght == undefined) value = Array(valcmp);
for(var i=0, var cnt=0; i<value.length; i++) for(var j=0; j<valcmp.length; j++) if((typeof(value[i]) != 'object' ? value[i] : value[i].valueOf()) == (typeof(valcmp[i]) != 'object' ? valcmp[j] : valcmp[j].valueOf()) $script_chkcnt) return " . ($this->negate ? 'false' : 'true') . ";
";
	    		break;
	    		
	    	case strpos($operator, ';') !== false:
	    		$script_cmp = "var result = value[i];";
	    		foreach (explode(';', $operator) as $operator) $script_cmp .= ($operator=='!' ? 'result = !result; ' : "result = result $operator cmp;");
	    	default:
	    		if (!isset($script_cmp)) $script_cmp = "var result = (typeof(value[i]) != 'object' ? value[i] : value[i].valueOf()) == (typeof(valcmp) != 'object' ? valcmp : valcmp.valueOf());";
    			$script = "
for(var i=0, var cnt=0; i<value.length; i++) {
	var cmp == (typeof valcmp != 'object' && valcmp.lenght == undefined) ? valcmp : valcmp[i];
	$script_cmp
	if (result) return " . ($this->negate ? 'false' : 'true') . ";
}";
    			break;
    	}

    	$script = "
if (value == null || value == '') return null;
if (valcmp == undefined) valcmp = " . Validator::valueToJS($this->valueCompare) . ";
if(typeof value != 'object' || value.lenght == undefined) value = Array(value);
$script
return " . ($this->negate ? 'true' : 'false') .  ";
";

    	return array('value, valcmp', $script);
    }
    
}

/* --------------- PEAR_ConfigBin ----------------- */
if (class_exists('Q_ClassConfig', false)) Q_ClassConfig::extractBin('Validator_Compare');

?>