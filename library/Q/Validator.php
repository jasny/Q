<?php

/**
 * Interface to indicate a class can be used to validate.
 *
 * @package Validate
 */
interface Validator
{
    /**
     * Validate a value.
     *
     * @param mixed $value  Value to be validated
     * @return boolean
     */
    public function validate($value);
}
