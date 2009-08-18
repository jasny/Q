<?php
namespace Q;

/**
 * Interface to specify a class can encrypt data.
 * 
 * @package Crypt
 */
interface Encrypt
{
	/**
	 * Encrypt value.
	 *
	 * @param string $value
	 * @param string $salt   Salt or crypted hash
	 * @return string
	 */
	 public function encrypt($value, $salt=null);
}
