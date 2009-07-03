<?php

namespace Q;

/**
 * FileVar object should be treaded as files, not file names.
 * 
 * @package RPC
 */
class RPC_FileVar
{
	/**
	 * File name
	 * @var string
	 */
	protected $filename;

	/**
	 * Class constructor
	 *
	 * @param string $filename
	 */
	public function __construct($filename)
	{
		$this->filename = (string)$filename;
	}
	
	/**
	 * Cast object to string
	 */
	public function __toString()
	{
		return $this->filename;
	}
}

?>