<?php 
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Controller.php';

require_once 'Q/Router.php';

/**
 * Base class to route requests.
 * 
 * The default router settings will be read from Q\Config::i()->route.
 * Additional routers can be defined as Q\Config::i()->route.NAME and used as Route::NAME() or Route::with(NAME)->handle(). 
 * 
 * @package Route
 */
abstract class Route implements Router
{
    /** No automatic authorization */
    const AUTHZ_NONE = 0;
    /** Authorize on method */
    const AUTHZ_CTL = 1;
    /** Authorize on controller method combination */
    const AUTHZ_METHOD = 2;
    
    
    /**
     * Registered Route classes.
     * 
     * @var array
     */
    static public $drivers = array(
        'http'=>'Q\Route_HTTP',
    	'rest'=>'Q\Route_HTTP',
        'console'=>'Q\Route_Console'
    );
    

    /**
     * Path to controllers.
     * If the path is specified the controller name will be used as filename.
     * 
     * @var string
     */
    public $path;
    
    /**
     * Check if object is actually a controller.
     * @var boolean
     */
    public $checkController = true;
    
    
    /**
     * Always use this controller.
     * @var string|object
     */
    public $controller;

    /**
     * Default controller if controller can't be determined.
     * @var string|object
     */
    public $defaultController;

    /**
     * Always use this method.
     * @var string
     */
    public $method;

    /**
     * Default method if method can't be determined.
     * @var string
     */
    public $defaultMethod;
    
    
    /**
     * Prefix to the controller name for the controller class.
     * @var string
     */
    public $controllerPrefix;

    /**
     * Suffix to the controller name for the controller class.
     * @var string
     */
    public $controllerSuffix;
    
    
    /**
     * GET variable for the controller.
     * Defaults back to 1st argument of almost pretty url.
     * 
     * @var string
     */
    public $ctlParam;
    
    /**
     * GET variable for the method.
     * Defaults back to HTTP method.
     * 
     * @var string
     */
    public $methodParam;
    
    /**
     * Delimiter for if GET variable holds both controller and method.
     * You probably also need to put the same value in $ctlParam and $methodParam.
     *  
     * @var string
     */
    public $paramDelim;
    
    
    /**
     * Authorization interface to use.
     * @var string
     */
    public $auth = 'i';
    
    /**
     * Authorization done by router.
     * @var int
     */
    public $authz = self::AUTHZ_NONE;

    /**
     * Prefix for authz group.
     * @var string
     */
    public $authzGroupPrefix;
    
    /**
     * Character to use between controller name and method for AUTHZ_METHOD.
     * @var string
     */
    public $authzGroupGlue = '.';
    
    
	/**
	 * Factory; Create a new router.
	 *
	 * @param string|array $dsn      Configuration options, may be serialized as assoc set (string)
	 * @param array        $options  Configuration options (which do not appear in DSN)
	 * @return Router
	 */
	static public function with($dsn, $options=array())
	{
		if (get_called_class() == __CLASS__) {
	    	$dsn_options = is_string($dsn) ? extract_dsn($dsn) : $dsn;
			$options = (array)$dsn_options + (is_array($options) || $options instanceof \ArrayObject ? $options : array($options));
		
		    if (load_class('Q\Config')) {
		        $cfg_options = Config::i()->get('route' . (empty($options['driver']) ? '' : '.' . $options['driver']));
	    		if ($cfg_options) {
	    		    if (!is_array($cfg_options) && !($options instanceof \ArrayObject)) $cfg_options = split_set(';', $cfg_options);
	    		    $options += $cfg_options;
	    		    if (isset($cfg_options['driver'])) $options['driver'] = $cfg_options['driver'];
	    		}
		    } 

			if (empty($options['driver'])) throw new Exception("Unable to create Route object: No driver specified.");
			if (!isset(self::$drivers[$options['driver']])) throw new Exception("Unable to create Route object: Unknown driver '{$options['driver']}'");
			$class = self::$drivers[$options['driver']];
			if (!load_class($class)) throw new Exception("Unable to create $class object: Class does not exist.");
		} else {
			$options = (is_array($dsn) ? $dsn : array($dsn)) + $options;
			$class = get_called_class();
		}
		
		return new $class($options);
	}
    
    /**
     * Magic method for static function calls; Create router and handle request.
     * 
     * @param string $name
     * @param array  $args
     */
    static public function __callStatic($name, $options)
    {
        $router = self::with($name, $options);
        $router->handle();
    }
    
    /**
     * Route request with default router settings.
     * 
     * @param array $options
     */
    static public function request($options=array())
    {
        $router = self::with(null, $options);
        $router->handle();
    }
    
    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options)
    {
    	if (isset($options['autz']) && is_string($options['autz']) && !ctype_digit($options['autz'])) {
    		switch ($options['autz']) {
    			case 'none':   $options['autz'] = self::AUTHZ_NONE; break;
    			case 'ctl':    $options['ctl'] = self::AUTHZ_CTL; break;
    			case 'method': $options['method'] = self::AUTHZ_METHOD; break;
    			default: throw new Exception("Invalid value '{$options['autz']}' specified for router option 'autz'.");
    		}
    	} 
    	
    	if (isset($options[0])) $options['controller'] = $options[0];
    	unset($options[0]);
    	
        foreach ($options as $key=>$value) {
            $this->$key = $value;
        }
    }
    
    
    /**
     * Get controller name or object.
     * 
     * @return string|object
     */
    abstract protected function getController();
    
    /**
     * Get method name.
     * 
     * @return string
     */
    protected function getMethod();
    
    
    /**
     * Authorize for controller
     */
    protected function authz($ctl, $method)
    {
        if (!$this->authz) return;
        
        if (!load_class('Q\Auth')) throw new Exception("Unable to authenticate; Can't load Q\Auth class.");
        Auth::getInterface($this->auth)->authz($this->authzGroupPrefix . ($this->authz == self::AUTHZ_METHOD ? $ctl . $this->authzGroupGlue . $method : $ctl));
    }
    
    
    /**
     * Handle request
     */
    public function handle()
    {
        $ctl = $this->getController();
        if (empty($ctl)) throw new NotFoundException("No controller selected.");
        if (!is_object($ctl) && preg_match('/\W/', $ctl)) throw new SecurityException("Invalid controller name '{$ctl}'.");
        
        $method = $this->getMethod();
        
        $this->authz($ctl, $method);

        if (!is_object($ctl)) {
            $class = $this->controllerPrefix . $ctl . $this->controllerSuffix;
    
            if (!class_exists($class, false)) {
                if (!empty($this->path)) {
                    if (file_exists("{$this->path}/{$ctl}.php")) require_once "{$this->path}/{$ctl}.php";
                } else {
                    load_class($class);
                }
                if (!class_exists($class)) throw new NotFoundException("Controller '{$ctl}' does not exist.");
            }
            
            $controller = new $class();
        } else {
            $controller = $ctl;
            $ctl = get_class($controller); 
        }

        if ($this->checkController && !($controller instanceof Controller)) throw new SecurityException("Loaded {$ctl} object is not a controller.");
        if (!is_callable(array($controller, $method))) throw new InvalidMethodException("Method '{$method}' is not implemented for controller '{$ctl}'.");
        $controller->$method();
    }    
}
