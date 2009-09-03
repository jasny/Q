<?php
namespace Q;

/**
 * Interface to indicate a class that can transform data
 * 
 * @package Transform
 */
interface Transform_Handler
{
    /**
     * Class constructor
     * 
     * @param array $options  Transform options
     */
    public function __construct($options=array());

    /**
     * Do the transformation and return the result.
     *
     * @param mixed $data  Data to transform
     * @return mixed
     */
    public function process($data);
    
    /**
     * Do the transformation and output the result.
     *
     * @param mixed $data  Data to tranform
     */
    public function output($data);   
}
