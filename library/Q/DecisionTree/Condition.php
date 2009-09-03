<?php
namespace Q;

/**
 * A class which represents a condition by comparing an answer to the value of a node
 * 
 * @package DecisionTree
 */
class DecisionTree_Condition
{
	/**
	 * Unique id as known in external system like DB
	 * @var mixed
	 */
	protected $id;

	/**
	 * The unique id of the node which value is compared
	 */
	protected $nodeId;
	
	/**
	 * Class constructor
	 *
	 * @param DecisionTree_Node $node      Node or id of node
	 * @param string            $operator  Comparison operator (eg '==', '!=', '>', '<=', etc)
	 * @param mixed             $value     Value to compare with
	 * @param mixed             $id        Unique id
	 */
	function __construct($node, $operator, $value, $id=null)
	{
		$this->nodeId = is_object($node) ? $node->id() : $node;
		$this->id = isset($id) ? $id : $id = uniqid();
		
		parent::__construct($operator, $value);
	}
	
	/**
	 * Get the unique identifier of this node
	 *
	 * @return mixed
	 */
	function getId()
	{
		return $this->id;
	}
	
	/**
	 * Get the unique identifier of this node
	 *
	 * @param mixed $answer  Array with all given answers of the answer for the specific node
	 * @return boolean
	 */
	function validate($answers)
	{
		if (is_array($answers) && !isset($answers[$this->_nodeId])) return false;
		return parent::validate(!is_array($answers) ? $answers : $answers[$this->_nodeId]);
	}
}
