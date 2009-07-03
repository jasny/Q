<?

/**
 * Interface for FrontOffice objects 
 * to handle requests and dispatch Controller objects
 *
 * @package MVC
 */

abstract class FrontOffice
{
	/**
	 * Default menu item
	 *
	 * @var string
	 */
	static public $defaultItem = "startpagina";

	/**
	 * Sets the output type for Views
	 *
	 * @var string
	 */
	public $output = 'Html';
	
	/**
	 * Holds current module
	 *
	 * @var string
	 */
	protected $_proactive_module;
	
	/**
	 * Holds current module
	 *
	 * @var string
	 */
	protected $_controller;

	/**
	 * Holds current action
	 *
	 * @var string
	 */
	protected $_action;

	/**
	 * Holds current XMLMenu item
	 *
	 * @var string
	 */
	protected $_menuItem;

	/**
	 * Holds current XMLMenu action
	 *
	 * @var string
	 */
	protected $_menuAction;

	/**
	 * Holds additional request
	 *
	 * @var array
	 */
	protected $_requestVars;
	
	/**
	 * Singleton instance
	 *
	 * @var FrontOffice
	 */
	static private $_instance=null;	

	/**
	 * Singleton method - use this after factory()
	 *
	 * @return FrontOffice
	 */
	public function i()
	{
		if (!self::$_instance) trigger_error("The FrontOffice was not initialized", E_USER_ERROR);
		return self::$_instance;
	}
	
	
	/**
	 * Factory method - use this one for the initial request
	 * as it sets the Singleton instance
	 *
	 * @return FrontOffice
	 */
	static public function factory($type="URL")
	{
		if (!self::$_instance){		
			$class = __CLASS__.'_'.$type;
			if (!class_exists($class)) trigger_error("Unknown FrontOffice `$type` requested", E_USER_ERROR);
			self::$_instance = new $class();
		}
		return self::$_instance;
	}
	
	
	/**
	 * Check whether the FrontOffice is open
	 *
	 * @return boolean
	 */
	static public function isLoaded()
	{
		return (self::$_instance !== null);
	}
	
	
	/**
	 * Execute action
	 *
	 * @return boolean
	 */
	public function dispatch()
	{
		QuickApp::init(QuickApp::INIT_XMLMENU);
		if (!$this->_menuItem) $this->_menuItem = self::$defaultItem;

		// set current item/action
		XMLMenu::curitem($this->_menuItem);
		if ($this->_menuAction) XMLMenu::curaction($this->_menuAction);

		// authorize framework privileges before loading the controller
		//if (XMLMenu::getAttribute('access') == 'public') {
		//	QuickApp::init(QuickApp::INIT_AUTH_ALL &~ QuickApp::INIT_PRIVILEGES);
		//	QuickApp::$defaultExec &= ~QuickApp::INIT_PRIVILEGES;
		//} else 
		QuickApp::init(QuickApp::INIT_AUTH_ALL);

		// Page attribute triggers `Static` action (loads a View)
		if (XMLMenu::getAttribute('page')) {
			$action = new Action_Static(XMLMenu::getAttribute('page'), $this->_requestVars);
			$result = $action->execute();
			return $result->execute();
		} 	

		
		// Follow the MVC structure in XMLMenu
		$this->_proactive_module 	= XMLMenu::getAttribute('module', null, 'ancestor-or-self::*[@module != ""][position()=1]');
		$this->_controller 			= XMLMenu::getAttribute('controller', null, 'ancestor-or-self::*[@controller != ""][position()=1]');
		$this->_action 				= isset($this->_menuAction) ? $this->_menuAction : XMLMenu::getAttribute('action', null, 'ancestor-or-self::*[@action != ""][position()=1]');
	
		// Load controller
		$cclass = self::getControllerClass($this->_proactive_module, $this->_controller);
		if (!class_exists($cclass)) throw new Controller_Unknown_Exception("Unknown Controller object `".$this->_controller."` requested");
		$controller = new $cclass($this);
		
		// Controller must extend Controller class
		if (!($controller instanceof Controller)) throw new Controller_Illegal_Exception("Controller `$cclass` is not a valid Controller object");
		
		// Execute the action
		return $controller->execute($this->_action, $this->_requestVars);
	}

	
	/**
	 * Generate the classname for a current Module/Controller
	 *
	 * @params 	string 	$module
	 * @params 	string 	$controller
	 * @params 	string 	$action
	 * 
	 * @return 	string
	 */
	static public function getControllerClass($module, $controller)
	{
		$class = array();
		
		$class[] = ucfirst(strtolower($module));
		$class[] = 'Controller_'.ucfirst(strtolower($controller));
		
		return implode('_', $class);
	}
	
	/**
	 * Returns current module
	 *
	 * @return string
	 */
	public function curModule()
	{
		return $this->_proactive_module;
	}

	/**
	 * Returns current module
	 *
	 * @return IController
	 */
	public function curController()
	{
		return $this->_controller;
	}
	
	/**
	 * Returns current module
	 *
	 * @return string
	 */
	public function curAction()
	{
		return $this->_action;
	}


	/**
	 * @todo implement!
	 * PREVIOUS ACTION - Session Stack
	 */
	public function previousAction()
	{
		
	}
	
}

if (class_exists('ClassConfig', false)) ClassConfig_extractBin('FrontOffice');

?>