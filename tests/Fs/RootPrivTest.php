<?php
use Q\Fs, Q\Fs_Node;

require_once 'Q/Fs.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Fs test case that need root privileges
 */
class Fs_RootPrivTest extends PHPUnit_Framework_TestCase
{
    /**
     * File name
     * @var string
     */
    protected $file;
    
	
    /**
     * Remove tmp files (recursively)
     * 
     * @param string  $path
     */
    protected static function cleanup($path)
    {
    	if (file_exists($path) || is_link($path)) unlink($path);
    	if (file_exists("$path.x") || is_link("$path.x")) unlink("$path.x");
    	
    	if (is_dir("$path.y")) {
    		static::cleanup("$path.y/" . basename($path));
    		rmdir("$path.y");
		}
    }
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
    	$this->file = sys_get_temp_dir() . '/q-fs_filetest.' . md5(uniqid());
        parent::setUp();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        $this->cleanup($this->file);
    	$this->Fs_Node = null;
    }    
    
    
    /**
     * Tests Fs_Node::chown()
     */
    public function testChown()
    {
        if (!function_exists('posix_getuid')) $this->markTestSkipped("Posix functions not available; I don't know if I'm root.");
    	if (posix_getuid() !== 0) $this->markTestSkipped("Can only chown as root.");

    	$sysusr = posix_getpwuid(3);
    	if (!$sysusr) $this->markTestSkipped("The system has no user with uid 3, which is used in the test.");
    	
        clearstatcache(false, $this->file);
        $this->Fs_Node->chown($sysusr['name']);
        $this->assertEquals(3, fileowner($this->file));
        
        clearstatcache(false, $this->file);
        $this->Fs_Node->chown(0);
        $this->assertEquals(0, fileowner($this->file));
    }
	
    /**
     * Tests Fs_Node::chgrp()
     */
    public function testChgrp()
    {
        if (!function_exists('posix_getuid')) $this->markTestSkipped("Posix functions not available; I don't know if I'm root.");
    	if (posix_getuid() !== 0) $this->markTestSkipped("Can only chown as root.");

    	$sysgrp = posix_getgrgid(2);
    	if (!$sysgrp) $this->markTestSkipped("The system has no group with gid 2, which is used in the test.");
    	
        clearstatcache(false, $this->file);
        $this->Fs_Node->chgrp($sysgrp['name']);
        $this->assertEquals(2, filegroup($this->file));
        
        clearstatcache(false, $this->file);
        $this->Fs_Node->chown(0);
        $this->assertEquals(0, filegroup($this->file));
	}

    /**
     * Tests Fs_Node::chown() with user:group
     */
    public function testChown_Chgrp()
    {
        if (!function_exists('posix_getuid')) $this->markTestSkipped("Posix functions not available: I don't know if I'm root.");
    	if (posix_getuid() !== 0) $this->markTestSkipped("Can only chown as root.");

    	$sysusr = posix_getpwuid(3);
    	if (!$sysusr) $this->markTestSkipped("The system has no user with uid 3, which is used in the test.");

    	$sysgrp = posix_getgrgid(2);
    	if (!$sysgrp) $this->markTestSkipped("The system has no group with gid 2, which is used in the test.");
    	
        clearstatcache(false, $this->file);
        $this->Fs_Node->chown(array($sysusr['name'], $sysgrp['name']));
        $this->assertEquals(3, fileowner($this->file));
        $this->assertEquals(2, filegroup($this->file));
        
        clearstatcache(false, $this->file);
        $this->Fs_Node->chown(array(0, 0));
        $this->assertEquals(0, fileowner($this->file));
        $this->assertEquals(0, filegroup($this->file));
    }
}