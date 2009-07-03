<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * The iterator which can be used to walk through the tree.
 * 
 * @package ConditionalTree
 */
class ConditionalTree_Iterator implements IteratorAggregate
{
	/**
	 * All nodes stepped through to the last node (= the current step).
	 * Array of ConditionalTree_Node objects.
	 * 
	 * @var array
	 */
	protected $nodepath;
	
	/**
	 * The answers given for each step.
	 * @var array
	 */
	public $answers=array();

	/**
	 * Attribute name for keys in answers array.
	 * @var array
	 */
	public $answerkey='id';
		
	
	/**
	 * Class constructor
	 *
	 * @param ConditionalTree_Node $node       Last node (= the current step)
	 * @param array                $answers    The answers given for each step
	 * @param string               $answerkey  Attribute name for keys in answers array
	 */
	public function __construct(ConditionalTree_Node $node, $answers=null, $answerkey='value')
	{
		$this->nodepath = array($node->index()->firstNode());
		
		if (isset($answers)) $this->answers =& $answers;
		if (isset($answerkey)) $this->answerkey = $answerkey;
		
		if ($this->getNode()->id() !== $node->id()) $this->walk($node);		
	}

	/**
	 * Return an iterator to walk through the nodes (as required by the IteratorAggregate interface)
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->nodepath);
	}

	
	/**
	 * Check if a node is the current or a previous step.
	 *
	 * @param mixed  $node       ConditionalTree_Node or attribute value
	 * @param string $attribute  Attribute name (only used if $node is a value)
	 * @return boolean
	 */
	public function hasNode($node, $attribute='id')
	{
		if ($node instanceof ConditionalTree_Node) return in_array($node, $this->nodepath, true);
		
		foreach ($this->nodepath as $curnode) {
			if (isset($curnode) && $curnode->getAttribute($attribute) == $node) return true;
		}
		return false;
	}
	
	/**
	 * Return a node from current or previous steps.
	 *
	 * @param int $pos  Index of $this->nodepath. Leave null to get current step
	 * @return ConditionalTree_Node
	 */
	public function getNode($pos=null)
	{
		if (!isset($pos)) $pos = sizeof($this->nodepath)-1;
		return isset($this->nodepath[$pos]) ? $this->nodepath[$pos] : null;
	}

	/**
	 * Return number of steps taken.
	 *
	 * @return  int
	 */
	public function count()
	{
		return sizeof($this->nodepath);
	}

	/**
	 * Step to and return the next node.
	 *
	 * @param mixed $answer  The answer to the current step, NULL to keep the answer or '' to clear the answer.
	 * @return ConditionalTree_Node
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
	 * @return ConditionalTree_Node
	 */
	public function stepBack()
	{
		array_pop($this->nodepath);
		return $this->getNode();
	}
		
	/**
	 * Step through the tree, until $node is reached. Leave NULL to walk to end.
	 * Walk will step back if $node is alread in nodePath
	 *
	 * @param mixed $node  Walk until this node reached. ConditionalTree_Node, id of node or NULL
	 */
	public function walk($node=null)
	{
		if (isset($node)) {
			$node_id = is_object($node) && $node instanceof ConditionalTree_Node ? $node->id() : $node;
			
			foreach ($this->nodepath as $key=>$curnode) {
				if ($curnode->id() === $node_id) {
					$this->nodepath = array_splice($this->nodepath, 0, $key+1);
					return;
				}
			}
		} else {
			$curnode = $this->getNode();
		}
		
		while (!empty($curnode) && (!isset($node) || $curnode->id() != $node)) {
			$curnode = $this->step();
		}
		
		if (empty($curnode) && isset($node)) throw new Exception("Node not found walking through the tree, using given answers.");
	}
}

?>