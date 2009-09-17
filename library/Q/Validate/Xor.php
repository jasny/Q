<?php
namespace Q;

/**
 * Composite for validators, where an uneven number are required to be valid.
 * 
 * Null is a special case:
 *  true  xor null = null
 *  false xor null = null
 *  null  xor null = null
 *  
 * @package Validate
 */
class Validate_Xor extends Validate
{
}
