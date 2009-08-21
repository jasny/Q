<?php
namespace Q;

require_once 'Q/Route.php';

/**
 * Route a command line call.
 * 
 * @package Route
 */
class Route_Console extends Route
{
    /**
     * Get controller name.
     * 
     * @return string
     */
    protected function getController()
    {
        if ($this->controller) return $this->controller;
        
        if (isset($this->ctlParam)) {
            $ctl = Console::getOpt($this->ctlParam);
        } else {
            $ctl = Console::getArg(0);
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

        $method = isset($this->methodParam) ? Console::getOpt($this->methodParam) : Console::getArg(isset($this->methodParam) && !isset($this->paramDelim) ? 1 : 0);
        
        if (isset($this->paramDelim) && !empty($method) && ($pos = strpos($this->paramDelim, $method)) !== false) $method = substr($method, $pos+1);
        return $method;
    }
}