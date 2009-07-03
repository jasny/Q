<?php
namespace Q;

/**
 * Interface to specify a crypt class can also decrypt.
 * 
 * @package Crypt
 */
interface Decrypt
{
	/**
	 * Decrypt encrypted value.
	 *
	 * @param string $value
	 * @return string
	 */
    public function decrypt($value);
}
?>