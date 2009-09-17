<?php

/**
 * Indicates that a class knows how to be transformed.
 * 
 * @package Transform
 */
interface Transformable
{
	/**
	 * Check if object can be transformed into a specific format.
	 * 
	 * @param string $format
	 * @return boolean
	 */
	public function canTransfromTo($format);
	
	/**
	 * Get object in specified format.
	 * 
	 * @param string $format
	 * @return mixed
	 */
	public function getAs($format);
	
	/**
	 * Get object in specified format.
	 * 
	 * @param string $format
	 * @return mixed
	 */
	public function outputAs($format);
	
	/**
	 * Get object in specified format.
	 * 
	 * @param string $format
	 * @param string $file
	 * @return mixed
	 */
	public function saveAs($format, $file);
}
