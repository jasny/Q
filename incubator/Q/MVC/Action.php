<?

/**
 * Base class for controller actions.
 * Can be used to define actions as command pattern.
 * 
 * @package MVC
 */
abstract class Action
{
	/**
	 * Name of this action.
	 * @var string
	 */
	protected $name;
	
	/**
	 * Calling controller
	 * @var Controller
	 */
	protected $ctl;

	
	/**
	 * Class constructor
	 *
	 * @param string     $name
	 * @param Controller $ctl
	 */
	function __construct($name, $ctl)
	{
		$this->name = $name;
		$this->ctl = $ctl;
	}
	
	
	/**
	 * Perform the authorization that is needed for this action
	 *
	 * @return boolean
	 */
	public function auth()
	{
		return $ctl->auth($this->name);
	}
	
	/**
	 * Execute this action
	 */
	abstract protected function _execute();

	
	/**
	 * Check for authorization, execute this action and return ActionResult
	 */
	public function execute()
	{
		if (!$this->auth()) throw new ActionFailed_NotAllowed();
		return $this->_execute();
	}
}

?>