<?php
namespace Q;

/**
 * Composite for validators, where any are required to be valid.
 * 
 * Null is a special case:
 *  true  or null = true
 *  false or null = null
 *  null  or null = null
 *  
 * @package Validate
 */
class Validate_Or extends Validate
{
}
