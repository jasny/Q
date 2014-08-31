<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * The iterator which can be used to walk through the tree.
 * 
 * You need to use step to go to the next node based on the given answers. Using foreach will give you all
 * the nodes you already stepped through.
 * 
 * @package DecisionTree
 */
class DecisionTree_Iterator implements \IteratorAggregate, \Countable
{
	/**
	 * The tree for which this is the iterator.
	 * @var DecisionTree
	 */
	protected $tree;
	
	/**
	 * All nodes stepped through to the current step.
	 * @var DecisionTree_Node[]
	 */
	protected $nodepath=array();
	
	/**
	 * The answers given for each step.
	 * @var array
	 */
	protected $answers=array();
	
	
	/**
	 * Class constructor.
	 *
	 * @param DecisionTree_Node $tree       
	 */
	public function __construct(DecisionTree $tree)
	{
		$this->tree = $tree;
	}

	/**
	 * IteratorAggregate; Return an iterator for foreach functionality.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->nodepath);
	}
	
	/**
	 * Countable; Return number of steps taken.
	 *
	 * @return  int
	 */
	public function count()
	{
		return count($this->nodepath);
	}
	
	
	/**
	 * Check if a node is the current or a previous step.
	 *
	 * @param DecisionTree_Node|mixed $node  Node or node id
	 * @return boolean
	 */
	public function passedNode($node)
	{
		return $node instanceof DecisionTree_Node ? in_array($node, $this->nodepath, true) : $this->nodepath[$node];
	}
	
	/**
	 * Return a node from current or previous steps.
	 *
	 * @param int $pos  Index of node path; null for current step.
	 * @return DecisionTree_Node
	 */
	public function getStep($step=null)
	{
		if (!isset($pos)) $pos = sizeof($this->nodepath)-1;
		return isset($this->nodepath[$pos]) ? $this->nodepath[$pos] : null;
	}
	

	/**
	 * Step to and return the next node.
	 *
	 * @param mixed $answer  The answer to the current step, NULL to keep the answer or '' to clear the answer.
	 * @return DecisionTree_Node
	 */
	public function step($answer=null)
	{
		$node = $this->getNode();
		
		if ($answer === '') unset($this->answers[$node->getAttribute($this->answerkey)]);
		 elseif (isset($answer)) $this->answers[$node->getAttribute($this->answerkey)] = $answer;
		
		$next = $node->next($this->answers);
		$this->nodepath[] = $next;
		return $next;
	}
	
	/**
	 * Step back and return the previous node.
	 *
	 * @return DecisionTree_Node
	 */
	public function stepBack()
	{
		array_pop($this->nodepath);
		return $this->getNode();
	}
		
	/**
	 * Step through the tree until $node is reached or to the end (if $node is null).
	 * Walk will step back if $node is alread in the node path.
	 *
	 * @param mixed $node  Walk until this node reached; DecisionTree_Node, node id or NULL
	 */
	public function walk($node=null)
	{
		if (isset($node)) {
			$node_id = is_object($node) && $node instanceof DecisionTree_Node ? $node->getId() : $node;
			
			foreach ($this->nodepath as $key=>$curnode) {
				if ($curnode->getId() === $node_id) {
					$this->nodepath = array_splice($this->nodepath, 0, $key+1);
					return;
				}
			}
		} else {
			$curnode = $this->getNode();
		}
		
		while (!empty($curnode) && (!isset($node) || $curnode->getId() != $node)) {
			$curnode = $this->step();
		}
		
		if (empty($curnode) && isset($node)) throw new Exception("Node not found walking through the tree using given answers.");
	}
}
