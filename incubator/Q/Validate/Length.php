<?php
require_once('Validator.php');

/**
 * Validates values using range comparison
 *
 * @category     Validation
 * @package      Validator
 * @author       Arnold Daniels <arnold@bean-it.nl>
 * @version      1.0
 * @since        PHP5
 */
class Validator_Length extends Validator
{
	/**
     * Description for types, used to create description
     * @var     string
     */
	public static $typeDescription = array(
        'range'=> array("", "-"),
        'length' =>	"",
        'minlength' =>	"min",
        'maxlength' =>	"max",
	);
		
	/**
     * Type of rule: 'range', 'length', 'minlength' or 'maxlength'
     * @var     string
     */
    private $_type;

	/**
     * Int for length, array for range
     * @var     mixed
     */
    public $size;

    
	/**
     * Propery get method for $this->type
     * @var     string
     */
    protected function __getType()
    {
    	return $_type;
    }

	/**
     * Propery get method for $this->maxLength
     * @return     int
     */
    protected function __getMaxLength()
    {
    	switch ($this->_type) {
    		case 'length':
    		case 'maxlength':	return $this->size;
    		case 'range':		return $this->size[1];
    		default:			return null;
    	}
    }

	/**
     * Propery get method for $this->maxLength
     * @return     int
     */
    protected function __getMinLength()
    {
    	switch ($this->_type) {
    		case 'length':
    		case 'minlength':	return $this->size;
    		case 'range':		return $this->size[0];
    		default:			return null;
    	}
    }
    
	/**
     * Propery get method for $this->description
     * @return    string
     */
    function __getDescription()
    {
    	if (is_array($this->size)) {
    		return (!empty(self::$typeDescription[$this->_type][0]) ? self::$typeDescription[$this->_type][0] . " " : "") . $this->size[0] . (!empty(self::$typeDescription[$this->_type][1]) ? self::$typeDescription[$this->_type][1] . " " : "") . $this->size[1];
    	} else {
            return (!empty(self::$typeDescription[$this->_type]) ? self::$typeDescription[$this->_type] . " " : "") . $this->size;
    	}
    }    
    
    /**
     * Class constructor
     * Type can ONLY be set upon creation
     *
     * @param    mixed      $type          Type of rule: 'range', 'length', 'minlength' or 'maxlength'
     * @param    mixed      $size          Int for length, array for range
     * @param    integer    $maxsize       Second argument for range (instead of array)
     */
	function __construct($type=null, $size=null, $maxsize=null)
	{
		if (isset($maxsize) && !is_array($size)) $size = array($size, $maxsize);

		$this->_type = $type;
		if (!empty($size)) $this->size = $size;
	}

    /**
     * Called to set properties after rule is cloned in factory
     *
     * @param    mixed      $size          Int for length, array for range
     * @param    integer    $maxsize       Second argument for range (instead of array)
     */
	public function __factoryClone($size=null, $maxsize=null)
	{
		if (isset($maxsize) && !is_array($size)) $size = array($size, $maxsize);
		if (!empty($size) && $this->size != null) trigger_error(__CLASS__ . ": Size already set: " . $this->size . " " . (string)$this . ". Size is overwritten.", E_USER_NOTICE);
		if (!empty($size)) $this->size = $size;
	}


	/**
     * Validates a value using a range comparison
     *
     * @param     string    $value      Value to be checked
     * @param     string    $type       Type of rule: 'range', 'length', 'minlength' or 'maxlength'. Leave NULL to use $this->_type.
     * @param     mixed     $size       Int for length, array for range. Leave NULL to use $this->size.
     * @param     integer   $maxsize    Second argument for range (instead of array)
     * @return    boolean   true if value is valid
     */
    function validate($value, $size=null, $maxsize=null)
    {
    	if ($value === null || $value === "") return null;
    	
    	if (!isset($size) && isset($this) && $this instanceof self) $size = $this->size;
    	 elseif (isset($maxsize) && !is_array($size)) $size = array($size, $maxsize);
    	
    	$length = strlen($value);
        switch (!empty($this->_type) ? $this->_type : (is_array($size) ? 'range' : 'length')) {
            case 'range':       $result = ($length >= $size[0] && $length <= $size[1]); break;
            case 'length':		$result = ($length == $size); break;
            case 'minlength':	$result = ($length >= $size); break;
            case 'maxlength':	$result = ($length <= $size); break;
            default:           	trigger_error("Unknown type for length validation '" . $type . "'", E_USER_WARNING);
            					return false;
        }
        
        return $this->negate xor $result;
    }


    /**
     * Returns the javascript test
     *
     * @return   string
     */
    function getScript($field)
    {
    	/*$neg = ($this->negate ? "!" : "");
        switch ($this->_type) {
            case 'range':      	return "return $neg ({jsLength} >= " . $this->size[0] . " && {jsLength} <= " . $this->size[1] . ")";
            case 'length':		return "return $neg ({jsLength} == " . $this->size . ")";
            case 'minlength':	return "return $neg ({jsLength} >= " . $this->size . ")";
            case 'maxlength':	return "return $neg ({jsLength} <= " . $this->size . ")";
            default:           	trigger_error("Unknown type for length validation '" . $this->_type . "'", E_USER_WARNING);
        }*/
    }

}

/* --------------- PEAR_ConfigBin ----------------- */
if (class_exists('Q_ClassConfig', false)) Q_ClassConfig::extractBin('Validator_Length');
?>