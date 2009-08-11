<?php
namespace Q;

/**
 * Interface to indicate class can handle the incomming request.
 * 
 * @package HandleRequest
 */
interface HandleRequest_Handler
{
    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options=array());
    
    /**
     * Route request
     */
    public function handle();
}