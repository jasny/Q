<?php
namespace Q;

/**
 * Composite for validators, where all are required to be valid.
 * 
 * Null is a special case:
 *  true  and null = null
 *  false and null = false
 *  null  and null = null
 *  
 * @package Validate 
 */
class Validate_And extends Validate
{
    /**
     * Validate a value.
     *
     * @param mixed $value  Value to be validated
     * @return boolean
     */
    public function validate($value)
    {}
}
