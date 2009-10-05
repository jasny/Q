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
     * Comparison operator
     * May only be set at construction
     * @var  string
     */
	private $_valueCompare;

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

        $operator = $this->_operator;
        if (empty($operator)) throw new Exception ("Unable to perform compare : empty operator is not valid");
          elseif (isset(self::$_mOperators[$operator])) $operator = self::$_mOperators[$operator];
// ??            elseif (!in_array($operator, self::$_mOperators)) throw new Exception ("Unable to perform compare : specified operator is not valid");

        if (isset(self::$_mOperatorFunctions[$operator])) 
        {
        	$compareFn = self::$_mOperatorFunctions[$operator];
        }else {
            if ($operator == 'a_any') {
                $cmp = '> 0';
                $compareFn = create_function('$a, $b', 'return $a ' . $operator . ' $b;');
            }elseif ($operator == 'a_all') {
                if (!isset($cmp)) $cmp = '==count($a)';
                $compareFn = create_function('$a, $b', 'return count(array_intersect($a, $b)) ' . $cmp . ';');
            }elseif(strpos($operator, ';') !== false) {
                $compareFn = "";
                foreach (explode(';', $operator) as $operator) $compareFn .= ($operator=='!' ? '$a = !$a; ' : '$a = $a' . $operator . '$b; ');
                $compareFn = create_function('$a, $b', $compareFn . 'return $a;');
            }else {
                $compareFn = create_function('$a, $b', 'return $a ' . $operator . ' $b;');
            }
            self::$_mOperatorFunctions[$operator] = $compareFn;
        }

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
}

?>