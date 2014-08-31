<?php
namespace Q;

require_once('Q\Validate.php');

/**
 * Validate is value is empty.
 *
 * @package Validate
 */
class Validator_Empty extends Validator
{
	/**
     * Checks if a value is empty
     *
     * @param mixed  $value
     * @return boolean
     */
    function validate($value)
    {
    	return $this->negate xor empty($value);
    }
}
