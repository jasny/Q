<?php
namespace Q;

/**
 * Represents an argument of a directive in an NCSA HTTPd configuration document.
 * 
 * @package HTTPd
 * @subpackage HTTPd_DOM
 * 
 * @todo Explicitly set quoting style. 
 */
class HTTPd_DOMAttr extends \DOMAttr
{
	/**
	 * Cast object to string.
	 * 
	 * @return string
	 */
	public function __toString()
	{
	    $value = str_replace("\n", "\\\n", $this->nodeValue);
	    return preg_match('/["\'\s]/', $this->nodeValue) && !($value[0] == '[' && substr($value, -1, 1) == ']') ? '"' . addcslashes($this->nodeValue, '"') . '"' : $value;
	}
}