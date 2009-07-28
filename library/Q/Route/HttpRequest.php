<?php
namespace Q;

require_once 'Q/Route.php';
require_once 'Q/Exception.php';
require_once 'Q/CommonException.php';

/**
 * Route HTTP request to controller or command.
 * 
 * @package Route
 * 
 * @todo Add mapping support for Route_HttpRequest.
 */
class Route_HttpRequest extends Route
{
    /** No automatic authorization */
    const AUTHZ_NONE = 0;
    /** Authorize on method */
    const AUTHZ_CTL = 1;
    /** Authorize on controller method combination */
    const AUTHZ_METHOD = 2;
    

    /**
     * Path to controllers.
     * If the path is specified the controller name will be used as filename.
     * 
     * @var string
     */
    public $path;
    
    
    /**
     * Always use this controller.
     * If you set this, you should stop and think about if you actually need a router.
     * 
     * @var string|object
     */
    public $controller;

    /**
     * Default controller if controller can't be determined.
     * @var string
     */
    public $defaultController;

    /**
     * Always use this method.
     * @var string
     */
    public $method;
        
    
    /**
     * Prefix to the controller name for the controller class.
     * @var string
     */
    public $controllerPrefix = '';

    /**
     * Suffix to the controller name for the controller class.
     * @var string
     */
    public $controllerSuffix = '';
    
    
    /**
     * GET variable for the controller.
     * Defaults back to 1st argument of almost pretty url.
     * 
     * @var string
     */
    public $controllerVar;
    
    /**
     * GET variable for the method.
     * Defaults back to HTTP method.
     * 
     * @var string
     */
    public $methodVar;
    
    /**
     * Delimiter for if GET variable holds both controller and method.
     * You probably also need to put the same value in $controllerVar and $methodVar.
     *  
     * @var string
     */
    public $varDelimiter;
    
    
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
    public $authzGroupPrefix = '';
    
    /**
     * Character to use between controller name and method for AUTHZ_METHOD.
     * @var string
     */
    public $authzGroupGlue = '.';
    
        
    /**
     * Get controller name.
     * 
     * @return string
     */
    protected function getController()
    {
        if ($this->controller) return $this->controller;
        
        if (isset($this->controllerVar) && isset($_GET[$this->controllerVar])) {
            $ctl = $_GET[$this->controllerVar];
        } elseif (($args = HTTP::getPathArgs())) {
            $ctl = $args[0];
        }
        
        if (isset($this->varDelimiter) && !empty($ctl) && ($pos = strpos($this->varDelimiter, $ctl)) !== false) $ctl = substr($ctl, 0, $pos);
        return empty($ctl) ? $this->defaultController : $ctl;
    }
    
    /**
     * Get method name.
     * 
     * @return string
     */
    protected function getMethod()
    {
        if ($this->method) return $this->method;
        if (!isset($this->methodVar) || !isset($_GET[$this->methodVar])) return $_SERVER['REQUEST_METHOD'];

        $method = $_GET[$this->methodVar];
        if (isset($this->varDelimiter) && !empty($method) && ($pos = strpos($this->varDelimiter, $method)) !== false) $method = substr($method, $pos+1);
        return $method;
    }
    
    /**
     * Authorize for controller
     */
    protected function authz($ctl, $method)
    {
        if (!$this->authz) return;
        
        load_class('Q\Auth');
        Auth::getInterface($this->auth)->authz($this->authzGroupPrefix . ($this->authz == self::AUTHZ_METHOD ? $ctl . $this->authzGroupGlue . $method : $ctl));
    }
    
    
    /**
     * Handle request
     */
    public function handle()
    {
        $ctl = $this->getController();
        if (empty($ctl)) throw new NotFoundException("No controller selected.");
        if (!is_object($ctl) && preg_match('/[^\w-]/', $ctl)) throw new SecurityException("Invalid controller name '{$ctl}'.");
        
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
                
        if (!is_callable(array($controller, $method))) throw new InvalidMethodException("Method '{$method}' is not implemented for controller '{$ctl}'.");
        $controller->$method();
    }
}
