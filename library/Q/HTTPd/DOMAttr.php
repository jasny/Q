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
	    if (preg_match('/["\'\s\n]/', $this->nodeValue)) $quote = '"';
		return $quote . str_replace("\n", "\\\n", addcslashes($this->nodeValue, '"')) . $quote;
	}
}