<?php
namespace Q;

/**
 * A class which represents a condition by comparing an answer to the value of a node
 * 
 * @package ConditionalTree
 */
class ConditionalTree_Condition extends Validation::Compare
{
	/**
	 * Unique id as known in external system like DB
	 * @var mixed
	 */
	protected $_id;

	/**
	 * The unique id of the node which value is compared
	 */
	protected $_nodeId;
	
	/**
	 * Class constructor
	 *
	 * @param ConditionalTree_Node $node      Node or id of node
	 * @param string                 $operator  Comparison operator (eg '==', '!=', '>', '<=', etc)
	 * @param int                    $value     Value to compare with
	 * @param mixed                  $id        Unique id
	 */
	function __construct($node, $operator, $value, $id=null)
	{
		$this->_nodeId = is_object($node) ? $node->id() : $node;
		$this->_id = isset($id) ? $id : $id = uniqid();
		
		parent::__construct($operator, $value);
	}
	
	/**
	 * Get the unique identifier of this node
	 *
	 * @return mixed
	 */
	function id()
	{
		return $this->_id;
	}
	
	/**
	 * Get the unique identifier of this node
	 *
	 * @param array|int $answer  Array with all given answers of the answer for the specific node
	 * @return mixed
	 */
	function validate($answers)
	{
		if (is_array($answers) && !isset($answers[$this->_nodeId])) return false;
		return parent::validate(is_array($answers) ? $answers : $answers[$this->_nodeId]);
	}
}

?>
