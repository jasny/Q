<?php
namespace Q;

require_once 'Q/HandleRequest.php';
require_once 'Q/Exception.php';
require_once 'Q/RestException.php';

/**
 * Route HTTP request to controller or command; This is basically the FrontController pattern.
 * Default is to act as a REST server. Set ctlParam and methodParam when using GET params.
 * 
 * @package Route
 */
class Route_HTTP extends Route
{
    /**
     * Get controller name.
     * 
     * @return string
     */
    protected function getController()
    {
        if ($this->controller) return $this->controller;
        
        if (isset($this->ctlParam) && isset($_GET[$this->ctlParam])) {
            $ctl = $_GET[$this->ctlParam];
        } else {
            $args = HTTP::getPathArgs();
            if (!empty($args)) $ctl = $args[0];
        }
        
        if (isset($this->paramDelim) && !empty($ctl) && ($pos = strpos($this->paramDelim, $ctl)) !== false) $ctl = substr($ctl, 0, $pos);
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
        if (!isset($this->methodParam) || !isset($_GET[$this->methodParam])) return $_SERVER['REQUEST_METHOD'];

        $method = $_GET[$this->methodParam];
        if (isset($this->paramDelim) && !empty($method) && ($pos = strpos($this->paramDelim, $method)) !== false) $method = substr($method, $pos+1);
        return $method;
    }
}
