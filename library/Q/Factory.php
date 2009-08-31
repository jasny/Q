<?php

/**
 * Interface to inditate a class has a factory method with DSN support.
 *
 * @package Pattern
 */
interface Factory
{
	/**
	 * Factory method.
	 *
	 * @param string|array $dsn      Authuration options, may be serialized as assoc set (string)
	 * @param array        $options  Other options (will be overwriten by DSN)
	 * @return object
	 */
	public function with($dsn, $options=array());
}