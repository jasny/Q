<?php
namespace Q;

/**
 * Interface to inditate a class is a singleton.
 * 
 * The constructor should be made protected.
 * If you wan't to create multiple objects and have one or more registered in global space, implement Multiton instead.
 *
 * @package Pattern
 */
interface Singleton
{
    /**
     * Get the singleton object. 
     * 
     * @return object
     */
    static public function i();
    
	/**
     * Alias of Singleton::i()
     * 
     * @return object
     */
    static public function getInterface();
}
