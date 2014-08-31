<?php
require_once('Validator.php');

/**
 * Validates values using callback functions or methods
 *
 * @category     Validation
 * @package      Validator
 * @author       Arnold Daniels <arnold@bean-it.nl>
 * @version      1.0
 * @since        PHP5
 */
class Validator_Callback extends Validator 
{
	/**
     * Callback
     * Format same as of call_user_func
     * @var     callback
     */
    private $_callback = null;

    
    /**
     * Function name for javascript
     * @var     string
     */
    private $_jsFunction = null;

	/**
     * Extra arguments for callback function
     * @var     array
     */
	public $args;


	/**
	 * Class constructor.
	 * Set callback function and script function for validation.
	 * Addition arguments will be passed to function
     *
     * @param    callback   $callback      Callback function. Format same as of call_user_func
     * @param    string     $jsFunction    Function name for javascript
     */
    function __construct($callback)
    {
    	if (!isset($callback)) return;
    	$args = func_get_args();
    	$args = array_splice($args, 2);
    	
    	$this->_callback = $callback;
    	$this->args = $args;
    }

    /**
     * Initialize class. Called to set properties after rule is cloned in factory
     * Arguments will be passed to function (added after existing)
     */
	public function __factoryClone()
	{
		if (isset($args)) $this->args += $args;
	}

    
    /**
     * Validates a value using a callback
     * Addition arguments will be passed to function (added after existing)
     *
     * @param     mixed     $value    Value to be validated
     * @return    boolean   Return true if valid
     */
    function validate($value)
    {
        if (!isset($this->_callback)) throw new Validator_Exception('No callback function');

        $args = func_get_args();
        $args = array_merge($this->args, $args);
    	$result = call_user_func_array($callback, $args);

        return isset($result) ? $this->negate xor $result : null;
    }


    /**
     * Returns the javascript test
     *
     * @return    string
     */
    function getScript()
    {
        if (!$this->_jsFunction) return null;

        foreach ($args as $key=>$arg) $args[$key] = Validator::valueToJs($arg);
        $args = "'" . join("', '", $this->args) . "'";

		$params = '{jsVar}' . (isset($args) ? ", {$args}" : '');
        return "var result " . $this->_jsFunction . "({$params}); return ({jsVar1} == null || {jsVar1} == '') ? null : " . ($this->negate ? '!' : '') . "result;";
    }
}
?>