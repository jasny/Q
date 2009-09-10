<?php
namespace Q;

/**
 * Interface to indicate a class that can transform data.
 * 
 * @package Transform
 */
interface Transformer
{
    /**
     * Class constructor.
     * 
     * @param array $options  Transform options
     */
    public function __construct($options=array());

	/**
	 * Get a transformer that does the reverse action.
	 * 
	 * @param Transformer $chain
	 * @return Transformer
	 */
	public function getReverse($chain=null);
    
    
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

    /**
     * Do the transformation and save the result to a file.
     *
     * @param string $filename
     * @param mixed  $data      Data to tranform
     */
    public function save($filename, $data); 
}
