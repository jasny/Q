<?php 
namespace Q;

require_once 'Q/Route/Handlers.php';
require_once 'Q/Exception.php';

/**
 * Route request.
 * Use the driver name as static magic function to create a route handler.
 * 
 * @package Route
 */
abstract class Route implements Route_Handler
{
    /**
     * Registered Route classes.
     * 
     * @var array
     */
    static public $drivers = array(
        'http'=>'Q\Route_HttpRequest',
        'httpRequest'=>'Q\Route_HttpRequest'
    );
    
    /**
     * Magic method for static calls; Creates a route handler.
     * 
     * @param string $func
     * @param array  $args  Options array as $args[0]
     * @return Route_Handler
     */
    public static function __callStatic($func, $args)
    {
        if (!isset(self::$drivers[$func])) throw new Exception("Unknown driver '$func'.");
        
        $class = $drivers[$func];
        return new $class(isset($args[0]) ? $args[0] : array());
    }
    
    
    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options)
    {
        foreach ($options as $key=>$value) {
            $this->$key = $value;
        }
    }
}
