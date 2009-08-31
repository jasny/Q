<?php
namespace Q;

/**
 * Interface to inditate a class supports named interfaces.
 *
 * @package Pattern
 */
interface Multiton
{
    /**
     * Get specific named interface.
     * Returns a Mock object if interface doesn't exist.
     * 
     * @param string $name
     * @return object|Mock
     */
    static public function getInterface($name);
    
	/**
	 * Magic method to retun specific instance.
	 * Returns a Mock object if interface doesn't exist.
	 *
	 * @param string $name
	 * @param string $args
	 * @return object|Mock
	 */
	static public function __callstatic($name, $args);
    
	/**
	 * Register object as instance.
	 * 
	 * @param string $name
	 */
	public function asInstance($name);
}