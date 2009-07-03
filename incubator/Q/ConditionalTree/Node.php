<?php
namespace Q;

/**
 * A class which represents a condition by comparing the value of a node.
 * 
 * @package ConditionalTree
 */
class ConditionalTree_Node
{
	/**
	 * Link to other nodes
	 * @var array
	 */
	protected $_transitions = array();

	/**
	 * Node attributes
	 * @var array
	 */
	protected $_attributes = array();

	/**
	 * Index of all nodes
	 * @var ConditionalTree_Index
	 */
	protected $_index;

	/**
	 * Class constructor
	 *
	 * @param array $attributes  Attributes of the node, including id
	 */
	function __construct($attributes=null)
	{
		if (!is_array($attributes)) $attributes = isset($attributes) ? array('id'=>$attributes) : array();
		if (!isset($attributes['id'])) $attributes['id'] = uniqid();
		
		$this->_attributes = $attributes;
	}

	/**
	 * Create a new iterator, pointing to this node.
	 * An iterator allows to step through the tree.
	 *
	 * @param array  $answers    Answers on (previous) steps in tree
	 * @param string $answerKey  Attribute name for keys in answers array
	 * @return ConditionalTree_Iterator
	 */
	function createIterator($answers=null, $answerKey='value')
	{
	   return new ConditionalTree_Iterator($this, $answers, $answerKey);
	}
	
	/**
	 * Get the unique identifier of this node
	 *
	 * @return mixed
	 */
	function id()
	{
		return $this->_attributes['_id'];
	}
		
	/**
	 * Get an attribute.
	 *
	 * @param string $name
	 * @return mixed
	 */
	function getAttribute($name)
	{
		return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
	}
	
	/**
	 * Magic get method: get an attribute.
	 *
	 * @param string $name
	 * @return mixed
	 */
	function __get($name)
	{
		return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
	}
	
	/**
	 * Get an index with all nodes in the tree.
	 * Won't have an index if the node isn't part of a tree.
	 *
	 * @return ConditionalTree_Index
	 */
	function index()
	{
		return $this->_index;
	}
	
	/**
	 * Set the transition to the next node.
	 * Note: To decide the next node, the transitions will be checked in the reverse order they were set. 
	 * 
	 * @param ConditionalTree_Node|mixed $node        Next node or id of next node
	 * @param array                        $conditions  Set with conditiona which needs to be true to do this transition
	 */
	function setTransition($node, $conditions=null)
	{
		if (!isset($this->_index)) $this->_index = ConditionalTree_Index();

		if (!is_array($conditions)) $conditions = empty($conditions) ? array() : array($conditions);
		
		foreach ($conditions as $key=>$condition) {
			if ($condition instanceof ConditionalTree_Condition) continue;

			unset($conditions[$key]);
			$condition = $this->_index->loadCondition($condition);
			if (is_array($condition)) $conditions = array_merge($conditions, $condition);
			  else $conditions[$key] = $condition;
		}
		
		$transition = (object)array('node'=>$node, 'conditions'=>$conditions);
		
		if (empty($conditions)) {
			if (isset($this->_transitions[0])) throw new Exception("Unable to create transition from node '" . $this->id() . "' to node '" . ($node instanceof self ? $node->id() : $node) . ". A default transition already exists.");
			array_unshift($this->_transitions, $transition);
		} else {
			$transkey = reset($conditions)->id();
			if (isset($this->_transitions[$transkey])) throw new Exception("Unable to create transition from node '" . $this->id() . "' to node '" . ($node instanceof self ? $node->id() : $node) . ". A transition for condition '$transkey' already exists.");
			$this->_transitions[$transkey] = $transition;
		}
		
		if (is_object($node) && $node instanceof self) {
			if ($node->index() === null) $this->_index->addNode($node);
			  elseif ($this->_index != $node->index()) $this->_index->merge($node->index());
		}
	}

	/**
	 * Get the next node, validating conditions to decide the correct transition.
	 *
	 * @param array $answers  Array with all given answers
	 * @return ConditionalTree_Node
	 */
	function next($answers)
	{
		if (empty($this->_transitions)) return null;
		
		foreach ($this->_transitions as $key=>$transition) {
			foreach ($transition->conditions as $condition) {
				if (!$condition->validate($answers)) continue 2;
			}
			
			if (!($transition->node instanceof self)) {
				$transition->node = $this->index()->loadNode($transition->node);
				$this->_transitions[$key] = $transition;
			}
			
			return $transition->node;
		}
		
		trigger_error("Unexpected end of tree: No default node defined and all conditions have failed.", E_USER_WARNING);
		return null;
	}

	/**
	 * Get the all possible next nodes.
	 *
	 * @return array
	 */
	function nextNodes()
	{
		if (empty($this->_transitions)) return null;
		
		$nodes = array();
		foreach ($this->_transitions as $key=>$transition) {
			if (isset($nodes[is_object($transition->node) ? $transistion->node->id() : $transistion->node])) continue;
			
			if (!($transition->node instanceof self)) {
				$transition->node = $this->index()->loadNode($transition->node);
				$this->_transitions[$key] = $transition;
			}
			$nodes[$transition->node->id()] = $transition->node;
		}
		
		return array_values($nodes);
	}
	
	/**
	 * See if this node is the root node of the tree.
	 *
	 * @return boolean
	 */
	function isFirst()
	{
		if (!isset($this->_index)) return true;
		return $this->_index->firstNode()->id() === $this->id();
	}
	
	/**
	 * See if this node is the final node of the tree.
	 *
	 * @return boolean
	 */
	function isLast()
	{
		return empty($this->_transitions);
	}
	
	/**	
	 * DO NOT USE!!! Not protected, because used by the index to set the index of this node to itself.
	 * @ignore 
	 */
	function _setIndex($index)
	{
		$this->_index = $index;
	}
}

?>