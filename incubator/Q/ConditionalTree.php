<?php

namespace Q;

require_once 'Q/ConditionalTree/Node.php';

/**
 * Create a tree of nodes, where each node represends a question.
 * Based on the answers given for specific questions a path through the tree is determined.
 *
 * @package ConditionalTree
 */
class ConditionalTree
{
	/**
	 * All the (loaded) nodes of the tree
	 * @var array
	 */
	protected $_nodes = array();
	
	/**
	 * Statement object to get nodes from a db
	 * @var DB_Statement 
	 */
	protected $_queryNode;
	
	/**
	 * Statement object to get transitions from a db
	 * @var DB_Statement 
	 */
	protected $_queryTransition;
	
	/**
	 * Statement object to get conditions from a db
	 * @var DB_Statement 
	 */
	protected $_queryCondition;
	
	/**
	 * Class constructor
	 *
	 * @param string|Statement $queryNode
	 * @param unknown_type $queryTransition
	 * @param unknown_type $queryCondition
	 */
	function __construct($queryNode=null, $queryTransition=null, $queryCondition=null)
	{
		if (isset($queryNode)) $this->setQueryNode($queryNode, $queryTransition);
		if (isset($queryCondition)) $this->setQueryCondition($queryCondition);
	}

	
	/**
	 * Return the loaded nodes
	 * 
	 * @return array
	 */
	public function getNodes()
	{
		return $this->_nodes;
	}

	
	/**
	 * Set queries to autoload the nodes
	 *
	 * @param DB_Statement|string $queryNode
	 * @param DB_Statement|string $queryTransition
	 * @param DB_Statement|string $queryCondition
	 */
	function setQueries($queryNode, $queryTransition, $queryCondition=null)
	{
		if (!isset($queryNode)) throw new Exception("Unable to set autoload queries: \$queryNode is empty");
		if (!isset($queryTransition)) throw new Exception("Unable to set autoload queries: \$queryTransition is empty");
		if (!class_exists('DB', false)) throw new Exception("Sorry autoloading nodes and conditions can only be done with DB."); 
		
		$this->_queryNode = $queryNode instanceof DB_Statement ? $queryNode : DB::i()->prepare($queryNode);
		if ($this->_queryNode->countPlaceholders() < 1) $this->_queryNode->addCriteria(0, DB::placeholder());
	 	  
		if ($queryTransition instanceof DB_Statement) $this->_queryTransition = $queryTransition->commit();
	 	  else $this->_queryTransition = DB::i()->prepare($queryTransition);
		if ($this->_queryTransition->countPlaceholders() < 1) $this->_queryTransition->addCriteria(0, DB_placeholder());
	 	  
	 	if (isset($queryCondition)) $this->setConditionQuery($queryCondition);
	}

	function setConditionQuery($queryCondition)
	{
		if (!isset($queryCondition)) throw new Exception("Unable to set autoload queries: \$queryCondition is empty");
		if (!class_exists('DB', false)) throw new Exception("Sorry autoloading nodes and conditions can only be done with DB."); 
		
		if ($queryCondition instanceof DB_Statement) $this->_queryCondition = $queryCondition->commit();
	 	  else $this->_queryCondition = DB::i()->prepare($queryCondition);
		if ($this->_queryCondition->countPlaceholders() < 1) $this->_queryCondition->addCriteria(0, DB_placeholder());
	}
	
	function firstNode()
	{
		return reset($this->_nodes);
	}
		
	function getNode($id, $attribute=null, $load=true)
	{
		if (!isset($attribute) || $attribute == 'id') {
			$node = isset($this->_nodes[$id]) ? $this->_nodes[$id] : null;
		} else {
			foreach ($this as $curnode) if ($curnode->getAttribute($attribute) == $id) {$node = $curnode; break;}
		}

		if ($load && !isset($node)) $node = $this->loadNode($id, $attribute, true);
		return isset($node) ? $node : null;
	}
	
	function addNode($node)
	{
		if (!($node instanceof ConditionalTree_Node)) $node = $this->loadNode($node);

		$id = $node->id();			
		if (isset($this->_nodes[$id])) trigger_error("A node with id " . $node->id() . " was already in the index. This might give unexpected results.", E_USER_WARNING);

		$this->_nodes[$id] = $node;
		$node->_setIndex($this);
	}
	
	function merge(ConditionalTree $index)
	{			
		foreach ($index->getNodes() as $node) $this->addNode($node);
	}
	
	
	function loadNode($id, $attribute=null, $forceLoad=false)
	{
		if (!$forceLoad) {
			$node = $this->getNode($id, $attribute, false);
			if (isset($node)) return $node;
		}
		
		if (!isset($this->_queryNode)) throw new ConditionalTree_BadMethodCall_Exception("Unable to load node '$id': Query statement for loading nodes isn't set.");		
		
		$this->_queryNode->addCriteria(isset($attribute) ? $attribute : 0, $id);
		$this->_queryNode->setLimit(1);		
		$result = $this->_queryNode->query();		
		$this->_queryNode->reset();
		if ($result->numRows() == 0) throw new ConditionalTree_OutOfRange_Exception("Unable to load node '$id': Node not found");
		
		$attributes = $result->fetchRow(DB::FETCHMODE_ASSOC);
		$attributes['id'] = $id; #Just to make sure
		$node = new ConditionalTree_Node($attributes, $this);
		
		$this->_queryTransition->addCriteria(0, $id);
		$result = $this->_queryTransition->query();
		$this->_queryTransition->reset();
		
		while (($row = $result->fetchOrdered())) {
			try {
				$node->setTransition($row[1], isset($row[2]) ? $row[2] : null);
			} catch (Exception $e) {
				trigger_error("Unable to set transition from node '" . $node->id() . "' to node '" . $row[1] . "'" . (isset($row[2]) ? " with condition '" . $row[2] . "'" : "") . ":\n" . $e->getMessage(), $e instanceof ConditionalTree_Setup_Exception ? E_USER_NOTICE : E_USER_WARNING);
			}
		}
		
		return $node;
	}
	
	function loadCondition($id)
	{
		if (!isset($this->_queryCondition)) throw new ConditionalTree_BadMethodCall_Exception("Unable to load condition '$id': \$queryCondition is not set.");
		
		$this->_queryCondition->addCriteria(0, $id);
		$result = $this->_queryCondition->query();
		$this->_queryCondition->reset();
		if ($result->numRows() == 0) throw new ConditionalTree_OutOfRange_Exception("Unable to load condition '$id': Condition not found");
		
		$conditions = array();
		while (($row = $result->fetchRow(DB::FETCHMODE_ORDERED))) {
			$conditions[] = new ConditionalTree_Condition($row[1], $row[2], $row[3], $row[0]);
		}
		return (sizeof($conditions) == 1) ? reset($conditions) : $conditions;
	}
}

?>