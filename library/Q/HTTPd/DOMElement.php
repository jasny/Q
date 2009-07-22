<?php
namespace Q;

/**
 * Represents a directive or section in an NCSA HTTPd configuration document.
 * 
 * @package HTTPd
 */
class HTTPd_DOMElement extends \DOMElement
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
	 * Check if element is a section and not a normal directive.
	 * 
	 * @return boolean
	 */
	public function isSection()
	{
		return $this->firstChild !== null;
	}
	
	/**
	 * Gets line number for where the directive is defined. 
	 *  
	 * @return int
	 */
	public function getLineNo()
	{
		return $this->_lineno;
	}
	
	/**
	 * Cast object to string.
	 * 
	 * @return string
	 */
	public function __toString()
	{
	    if ($this->firstChild !== null) return parent::__toString() . "\n";
	    
		$str = $this->nodeValue;
		foreach ($this->childNodes as $node) {
			$str .= ' ' . (string)$node;
		}
		return $str . "\n";
	}


	/**
	 * Prepare inserting an argument by shifting the values of the existing Arguments.
	 * Returns the node that should be replaced.
	 * 
	 * @param int $postition
	 * @return HTTPd_DOMAttr
	 */
	protected function shiftArguments($position)
	{
		$cnt = $this->countArguments();
		if ($position > $cnt+1) throw new \DOMException("Can't set argument {$node->nodeName} when there is/are only $cnt argument(s).", DOM_HIERARCHY_REQUEST_ERR);
		
		$node_to = parent::appendChild($this->ownerDocument->createArgument($cnt+1, '')); 
		for ($i=$cnt+1; $i>$position; $i--) {
			$node_from = $this->getArgumentNode($i);
			$node_to->nodeValue = $node_from->nodeValue;
			$node_to = $node_from;
		}
		
		return $node_to;
	}
	
	/**
	 * Remove an argument by unshifting the values of the existing Arguments.
	 * 
	 * @param int $postition
	 */
	protected function unshiftArguments($position)
	{
		$cnt = $this->countArguments();
		if (!$this->hasArgument($position)) throw new \DOMException("Can't remove argument $position, it doens't exist.",  DOM_NOT_FOUND);
		
		$node_to = $this->getArgumentNode($position); 
		for ($i=$position+1; $i<=$cnt; $i++) {
			$node_from = $this->getArgumentNode($i);
			$node_to->nodeValue = $node_from->nodeValue;
			$node_from = $node_to;
		}
		
		parent::removeChild($node_to);
	}
	
	
	/**
	 * Count the number of arguments the element has.
	 * 
	 * @return int
	 */
	public function countArguments()
	{
		$count = 0;
		foreach ($this->attributes as $att) $count++;
		return $count;
	}
	
	/**
	 * Returns value of an argument.
	 * 
	 * @param int $position
	 * @return string
	 */	
	public function getArgument($position)
	{
		return $this->getAttribute("a{$position}");
	}

	/**
	 * Checks to see if argument exists.
	 * 
	 * @param int $position
	 * @return boolean
	 */	
	public function hasArgument($position)
	{
		return $this->hasAttribute("a{$position}");
	}

	/**
	 * Append an argument to the directive.
	 * 
	 * @param string $value
	 * @return HTTPd_DOMAttr
	 */	
	public function addArgument($value)
	{
		return parent::setAttribute('a' . ($this->countArguments()+1), $value);
	}

	/**
	 * Set the value of an argument.
	 * 
	 * @param int    $position
	 * @param string $value
	 * @return HTTPd_DOMAttr
	 */	
	public function setArgument($position, $value)
	{
		$cnt = $this->countArguments();
		
		if ($node->nodeName > $cnt+1) throw new \DOMException("Can't set argument {$node->nodeName} when there is/are only $cnt argument(s).", DOM_HIERARCHY_REQUEST_ERR);
		if ($node->nodeName == $cnt+1) return $this->addArgument($value);
		
		$node = $this->getArgumentNode($position);
		$node->nodeValue = $value;
		return $node;
	}
	
	/**
	 * Insert an argument on a specific position.
	 *
	 * @param int    $position
	 * @param string $value
	 * @return HTTPd_DOMAttr
	 */
	public function insertArgument($position, $value)
	{
		$node = $this->shiftArguments($node->nodeName);
		$node->nodeValue = $value;
		return $node;  
	}

	/**
	 * Remove an argument.
	 *
	 * @param int $position
	 */
	public function removeArgument($position)
	{
		$this->unshiftArguments($position);
	}

	
	/**
	 * Set attribute value
	 * 
	 * @param string $name   Node name; 'a' . $postion
	 * @param string $value
	 * @return HTTPd_DOMAttr
	 */
	final public function setAttribute($name, $value)
	{
		if (!preg_match('/^a\d+$/', $name)) throw new \DOMException("Invalid name '$name';Argument attributes should be named 'a', followed by an integer.", DOM_INVALID_CHARACTER_ERR);
		return $this->setAttribute((int)substr($name, 1), $value);
	}
	
	/**
	 * Add or replace an argument node.
	 * 
	 * @param HTTPd_DOMAttr $node
	 * @return HTTPd_DOMAttr
	 */
	public function setAttributeNode(HTTPd_DOMAttr $node)
	{
		if (!preg_match('/^a\d+$/', $node->nodeName)) throw new \DOMException("Invalid name '{$node->nodeName}'; Argument attributes should be named 'a', followed by an integer.", DOM_INVALID_CHARACTER_ERR);
		
		$cnt = $this->countArguments();
		$position = (int)substr($node->nodeName, 1);
		
		if ($position > $cnt+1) throw new \DOMException("Can't set argument {$position} when there is/are only $cnt argument(s).", DOM_HIERARCHY_REQUEST_ERR);
		if ($position == $cnt+1) return parent::appendChild($node);
		return parent::replaceChild($node, $this->getArgumentNode($node->nodeName));
	}
	
	/**
	 * Insert an attribute on a specific position.
	 * 
	 * @param string $name   Node name; 'a' . $postion
	 * @param string $value
	 * @return HTTPd_DOMAttr
	 */
	final public function insertAttribute($name, $value)
	{
		if (!preg_match('/^a\d+$/', $name)) throw new \DOMException("Invalid name '$name'; Argument attributes should be named 'a', followed by an integer.", DOM_INVALID_CHARACTER_ERR);
		return $this->insertAttribute((int)substr($name, 1), $value);
	}

	/**
	 * Insert an argument node on a specific position.
	 *
	 * @param HTTPd_DOMAttr $node
	 * @return HTTPd_DOMAttr
	 */
	public function insertAttributeNode(HTTPd_DOMAttr $node)
	{
		if (!preg_match('/^a\d+$/', $node->nodeName)) throw new \DOMException("Invalid name '{$node->nodeName}'; Argument attributes should be named 'a', followed by an integer.", DOM_INVALID_CHARACTER_ERR);
		return parent::replaceChild($this->shiftArguments((int)substr($node->nodeName, 1)), $node);
	}
		
	/**
	 * Remove an attribute.
	 * 
	 * @param string $name  Node name; 'a' . $postion
	 */
	final public function removeAttribute($name)
	{
		if (!preg_match('/^a\d+$/', $name)) throw new \DOMException("Invalid name '$name'; Argument attributes should be named 'a', followed by an integer.", DOM_INVALID_CHARACTER_ERR);
		$this->removeAttribute((int)substr($name, 1));
	}
	
	
	/**
	 * Adds a new directive at the end of the children.
	 * 
	 * @param HTTPd_DOMElement $newnode
	 * @return HTTPd_DOMElement
	 */
	public function appendChild(\DOMNode $newnode)
	{
		if ($newnode instanceof \DOMAttr) throw new \DOMException("Don't call Q\\HTTPd_DOMElement::" . __FUNCTION__ . "() to add an attribute, use setAttributeNode() instead.", DOM_HIERARCHY_REQUEST_ERR);
		if ($this->firstChild === null) throw new \DOMException("It's not possible to add children to {$this->nodeName} direcive.", DOM_HIERARCHY_REQUEST_ERR);
		
		if (!($newnode instanceof HTTPd_DOMElement) && !($newnode instanceof \DOMText)) throw new \DOMException("You may only add Q\\HTTPd_DOMElement nodes to a section.", DOM_HIERARCHY_REQUEST_ERR);
		return \DOMElement::appendChild($newnode);
	}

	/**
	 * Adds a new directive before a reference node.
	 * 
	 * @param HTTPd_DOMElement $newnode
	 * @param HTTPd_DOMElement $refnode
	 * @return HTTPd_DOMElement
	 */
	public function insertBefore(\DOMNode $newnode, \DOMNode $refnode=null)
	{
		if ($newnode instanceof \DOMAttr) throw new DOMException("Don't call Q\\HTTPd_DOMElement::" . __FUNCTION__ . "() to add an attribute, use setAttributeNode() instead.", DOM_HIERARCHY_REQUEST_ERR);
		if ($this->firstChild === null) throw new \DOMException("It's not possible to add children to {$this->nodeName} direcive.", DOM_HIERARCHY_REQUEST_ERR);
		
		if (!($newnode instanceof HTTPd_DOMElement) && !($newnode instanceof \DOMText)) throw new \DOMException("You may only add Q\\HTTPd_DOMElement nodes to a section.", DOM_HIERARCHY_REQUEST_ERR);
		return \DOMElement::insertBefore($newnode, $refnode);
	}

	/**
	 * Removes a directive from the list of children.
	 * 
	 * @param HTTPd_DOMElement $oldnode
	 * @return HTTPd_DOMElement
	 */
	public function removeChild(\DOMNode $oldnode)
	{
		if ($oldnode instanceof \DOMAttr) throw new \DOMException("Don't call Q\\HTTPd_DOMElement::" . __FUNCTION__ . "() to remove an attribute, use removeAttribute() instead.", DOM_HIERARCHY_REQUEST_ERR);
		return \DOMElement::removeChild($oldnode);
	}

	/**
	 * Replaces a directive.
	 * 
	 * @param HTTPd_DOMElement $newnode
	 * @param HTTPd_DOMElement $oldnode
	 * @return HTTPd_DOMElement
	 */
	public function replaceChild(\DOMNode $newnode, \DOMNode $oldnode)
	{
		if ($newnode instanceof \DOMAttr || $oldnode instanceof \DOMAttr) throw new \DOMException("Don't call Q\\HTTPd_DOMElement::" . __FUNCTION__ . "() to replace an attribute, use setAttribute() or setAttributeNode() instead.", DOM_HIERARCHY_REQUEST_ERR);
		if ($this->firstChild === null) throw new \DOMException("It's not possible to add children to {$this->nodeName} direcive.", DOM_HIERARCHY_REQUEST_ERR);
		
		if (!($newnode instanceof HTTPd_DOMElement) && !($newnode instanceof \DOMText)) throw new \DOMException("You may only add Q\\HTTPd_DOMElement nodes to a section.", DOM_HIERARCHY_REQUEST_ERR);
		return \DOMElement::replaceChild($newnode, $oldnode);
	}
}