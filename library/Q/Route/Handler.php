<?php
namespace Q;

/**
 * Interface to indicate class can route requests
 * 
 * @package Route
 */
interface Route_Handler
{
    /**
     * Class constructor
     * 
     * @param array $options  Routing options
     */
    public function __construct($options=array());
    
    /**
     * Route request
     * @return unknown_type
     */
    public function handle();
}