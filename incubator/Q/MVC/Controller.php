<?
require_once "Action.php";

/**
 * Base class for Controllers.
 * Maps action names to classes, performs authorization and triggers appropriate action.
 */
class MVC_Controller
{
	/**
	 * Name of this controller.
	 * @var string
	 */
	public $name;

	/**
	 * FrontOffice object that called this Controller
	 * @var FrontOffice
	 */
	public $fo;
	
	
    /**
     * Aliasses for actions, set in child class.
     * @var array
     */
    public $action_alias = array();

	/**
	 * Path to action classes for this controller.
	 * Set to false to disable using action objects.
	 * 
	 * @var string
	 */
	public $action_path=false;   
	    
	/**
	 * Prefix for the classnames of the actions for this controller.
	 * @var string
	 */
	public $action_class_prefix;    
    
	/**
	 * Path to view files for this controller.
	 * @var string
	 */
	public $view_path;   

	
	/**
	 * The current action, used when execute() is called without a parameter.
	 * @var string
	 */
    public $action;

	/**
	 * All the arguments supplied to the contructor.
	 * @var array
	 */
    public $args = array();    

    
	/**
	 * Class constructor
	 *
	 * @param array $args Input argments
	 */
	public function __construct($args=array())
	{
		$this->args = $args;
		
		$ref = new ReflectionObject($this);
	    foreach ($ref->getMethods() as $method) {
            if (preg_match('/@access\\s+action\\b/im', $method->getDocComment())) {
                $this->action_methods[] = $method->getName();
            }
	    }
	}
	
	/**
	 * Magic methods: Execute action
	 */
	public function __call($action, $args)
	{
	    return $this->execute($action, $args);
	}
	
	/**
	 * Check if a specific action may be executed by this user.
	 *
	 * @param string $action
	 * @return boolean
	 */
	public function auth($action)
	{
		return true;
	}
	
	/**
	 * Execute action
	 * 
	 * @param string  $action
	 * @param array   $args
	 * @param boolean $handle_exceptions
	 */
	public function execute($action=null, $args=null, $handle_exceptions=true)
	{
		if (!isset($action)) $action = $this->action;

		if (!$handle_exceptions) return $this->_execute($action, $args);
		
   		try {
			$return = $this->_execute($action, $args);   			
	        
   		} catch (MVC::ActionDone $e) {
   			return null;
		} catch (ExpectedException $e) {
	        $this->errors[] = $e->getMessage();
   		} catch (Exception $e) {
   			Jasny_ErrorHandler::i()->handleException($e);
   			$this->errors[] = "Failed to perform action '$action': An unexpected error occured";
   		}

		return $return;
	}
		
	/**
	 * Execute the action
	 *
	 * @param string $action  The action to execute
	 * @param array  $args    Will be passed as argumentes to the action method
	 */
	public function _execute($action, $args=array())
	{
		if (isset($this->action_alias[$action])) $action = $this->action_alias[$action];
		
		if (in_array($action, $this->action_methods)) {
			return call_user_func_array(array($this, $action), (array)$args);
		}
		
		$ob = $this->loadAction($action, $data);
		if (isset($ob)) {
			return call_user_func_array(array($ob, 'execute'), $args);
		}
		
		throw new Exception("Action '$action' is not implemented");
	}
	
	
	/**
	 * Load an action object
	 *
	 * @param string 	$action
	 * @return Action
	 */
	protected function loadAction($action)
	{
		if ($this->action_path === false) return null;
		
		$class = str_replace('%name%', $this->name, $this->action_class_prefix);
		$file = (!empty($this->action_path) ? str_replace('%name%', $this->name, $this->action_path) . '/' : '') . str_replace("_", "/", $class) . "$action.php";
		if (!file_exists($file)) return null;
		
		require_once $file;
		return new $class($action, $this);
	}
	
	/**
	 * Load a specific view
	 *
	 * @param string $view
	 */
	public function view($view)
	{
		
	}
}
?>