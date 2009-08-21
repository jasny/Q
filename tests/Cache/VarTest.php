<?php
use Q\Cache_Var;

require_once 'Cache/MainTest.php';
require_once 'Q/Cache/Var.php';

/**
 * Cache_Var test case.
 */
class Cache_VarTest extends Cache_MainTest
{
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		$this->Cache = new Cache_Var();
		parent::setUp();
	}
}
