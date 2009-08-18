<?php

/**
 * Interface for output handlers.
 * 
 * You probably don't need to create custom output handlers, instead create a Cache or Transform class.
 */
interface Output_Handler
{
    /**
     * Callback for output handling
     *
     * @param string|array $buffer  Usually a string, might be an array if output sections are used and data is grabbed from cache.
     * @param int          $flags   Combination of PHP_OUTPUT_% constants
     * @return string|array
     */
    public function callback($buffer, $flags);
}