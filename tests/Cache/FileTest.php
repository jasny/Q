<?php
use Q\Cache_File;

require_once 'Cache/MainTest.php';
require_once 'Q/Cache/File.php';

/**
 * Cache_File test case.
 */
class Cache_FileTest extends Cache_MainTest
{
    /**
     * Clean up files
     */
    protected function cleanup()
    {
	    $dir = dirname(__DIR__) . '/tmp/cache_file';
	    foreach (scandir($dir) as $file) {
	        if (is_file("$dir/$file")) unlink("$dir/$file");
	    }
		rmdir($dir);
    }
    
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
	    $dir = dirname(__DIR__) . '/tmp/cache_file';
	    if (file_exists($dir)) $this->cleanup(); 
	    mkdir($dir, 0770, true);
	    
		$this->Cache = new Cache_File(array('path'=>$dir));
		parent::setUp();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
	    $this->cleanup();
		parent::tearDown();
	}	
}
