<?php
use Q\Cache_APC;

require_once 'Cache/MainTest.php';
require_once 'Q/Cache/APC.php';

/**
 * Cache_APC test case.
 */
class Cache_APCTest extends Cache_MainTest
{
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		if (!extension_loaded('apc')) $this->markTestSkipped("Unable to test Cache_APC; APC extension is not loaded.");
		
		$this->Cache = new Cache_APC();
		parent::setUp();
	}
}
