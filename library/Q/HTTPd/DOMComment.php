<?php
namespace Q;

/**
 * Represents a comment in an NCSA HTTPd configuration document.
 * 
 * @package HTTPd
 */
class HTTPd_DOMComment extends \DOMComment
{
    /**
     * Path from where the document is loaded. 
     */
    public $uriDocument;
        
	/**
	 * Line number of the loaded document.
	 * @var int
	 */
	public $_lineno;
	
	
	/**
	 * Gets line number for where the directive is defined. 
	 *  
	 * @return int
	 */
	public function getLineNo()
	{
		return $this->lineno;
	}
	
	/**
	 * Cast object to string
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return '#' . str_replace("\n", "\\\n", $this->nodeValue);
	}
}