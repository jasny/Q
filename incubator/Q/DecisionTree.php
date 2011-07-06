<?php
namespace Q;

require_once 'Q/DecisionTree/Node.php';

/**
 * Create a tree of nodes, where each node represends a question.
 * Based on the answers given for specific questions a path through the tree is determined.
 * 
 * Note: Nodes can have multiple incomming paths, making sink nodes. Real decision trees don't have sink nodes.
 * Can make a decision based on an answer of a previous node.
 *
 * @package DecisionTree
 * 
 * @todo Fix DecisionTree
 * @todo Make unit tests for DecisionTree
 */
class DecisionTree
{
	/**
	 * All the (loaded) nodes of the tree
	 * @var array
	 */
	protected $nodes = array();
	
	/**
	 * Statement to load nodes.
	 * The first argument should be id, other arguments will be additional properties of the node. 
	 * 
	 * @var DB_Statement 
	 */
	protected $nodeQuery;
	
	/**
	 * Statement to load transitions.
	 * Should result in array(target node id, condition node  
	 * 
	 * @var DB_Statement 
	 */
	protected $transitionQuery;
	
	/**
	 * Statement to load conditions.
	 * @var DB_Statement 
	 */
	protected $conditionQuery;
	
	
	/**
	 * Load a tree from the database.
	 * 
	 * @param string $table
	 * @param int    $root
	 * @return DecisionTree
	 */
	public static function load($table, $root)
	{
		// TODO implement DecisionTree::load()
	}
	
	/**
	 * Class constructor.
	 *
	 * @param mixed|DecisionTree_Node $root              Root node of the tree
	 * @param string|DB_Statement     $nodeQuery         Statement to load nodes
	 * @param string|DB_Statement     $transitionQuery   Statement to load transitions
	 * @param string|DB_Statement     $conditionQuery    Statement to load conditions
	 */
	public function __construct($root, $nodeQuery=null, $transitionQuery=null, $conditionQuery=null)
	{
		if (isset($nodeQuery) || isset($transitionQuery) || isset($conditionQuery)) {
			if (load_class('DB')) throw new Exception("Unable to load tree from the database; Q\\DB is not available.");
			if (!isset($nodeQuery) || !isset($transitionQuery)) throw new Exception("Specify both node and transition query, to load the tree from the database.");
		
			$this->nodeQuery = $nodeQuery instanceof DB_Statement ? $nodeQuery : DB::i()->prepare($nodeQuery);
		 	
			if ($transitionQuery instanceof DB_Statement) $this->transitionQuery = $transitionQuery->commit();
		 	  else $this->transitionQuery = DB::i()->prepare($transitionQuery);
			
		 	if (isset($this->conditionQuery)) {
				if ($conditionQuery instanceof DB_Statement) $this->conditionQuery = $conditionQuery->commit();
			 	  else $this->conditionQuery = DB::i()->prepare($conditionQuery);
		 	}
		}
		
		if ($root instanceof DecisionTree_Node) $this->getNode($root);
		  else $this->addNode($root);
	}
	
	
	/**
	 * Get the root node of the tree.
	 * 
	 * @return DecisionTree_Node
	 */
	public function getRoot()
	{
		return reset($this->nodes);
	}

	/**
	 * Get an iterator to walk through the tree.
	 * 
	 * @return DecisionTree_Iterator
	 */
	public function createIterator()
	{
		return new DecisionTree_Iterator($this);
	}

	/**
	 * Use a named iterator; It will be saved in $_SESSION.
	 * 
	 * @return DecisionTree_Iterator
	 */
	public function useIterator($name)
	{
		if (!isset($_SESSION["Q\\DecisionTree_Iterator:$name"])) $_SESSION["Q\\DecisionTree_Iterator:$name"] = $this->createIterator();
		return $_SESSION["Q\\DecisionTree_Iterator:$name"];
	}
	
	
	/**
	 * Add a node to the tree.
	 * 
	 * @param DecisionTree_Node $node
	 */
	function addNode(DecisionTree_Node $node)
	{
		$id = $node->getId();			
		if (isset($this->nodes[$id])) throw new Exception("Node with id " . $node->id() . " is already part of the tree.");

		$node->_bind($this);
		$this->nodes[$id] = $node;
	}
	
	/**
	 * Merge the nodes of another tree into this tree.
	 * 
	 * @param DecisionTree $tree
	 */
	function merge(DecisionTree $tree)
	{			
		foreach ($index->getNodes() as $node) {
			$this->addNode($node);
		}
	}
	
	/**
	 * Get a specific node from the tree.
	 * 
	 * @param mixed $id  Node identifier.
	 * @return DecisionTree_Node
	 */	
	function getNode($id)
	{
		if (isset($this->nodes[$id])) return $this->nodes[$id];

		$node = $this->nodeQuery->reset()->addCriteria(0, $id)->setLimit(1)->execute()->fetchObject('DecisionTree_Node');		
		if (!$node) throw new Exception("Unable to load node '$id': Node not found");
		$node->_bind($this);
		
		$result = $this->transitionQuery->reset()->addCriteria(0, $id)->execute();
		
		while (($row = $result->fetchOrdered())) {
			$node->setTransition($row[1], isset($row[2]) ? $row[2] : null);
		}
		
		$this->nodes[$i] = $node;
		
	}

	/**
	 * Load a condition used for a transition.
	 * 
	 * @param mixed $id
	 * @return DecisionTree_Condition
	 */
	function getCondition($id)
	{
		if (!isset($this->conditionQuery)) throw new Exception("Unable to load condition '$id': Condition query is not set.");
		
		$this->conditionQuery->reset()->addCriteria(0, $id)->execute();
		if ($result->numRows() == 0) throw new Exception("Unable to load condition '$id': Condition not found.");
		
		$conditions = array();
		while (($row = $result->fetchRow(DB::FETCHMODE_ORDERED))) {
			$conditions[] = new DecisionTree_Condition($row[1], $row[2], $row[3], $row[0]);
		}
		return (sizeof($conditions) == 1) ? reset($conditions) : $conditions;
	}
}

