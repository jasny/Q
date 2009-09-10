<?php
namespace Q;

/**
 * Interface to indicate class can handle the incomming request.
 * 
 * @package Route
 */
interface Router
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
