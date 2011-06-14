<?php
use Q\Fs, Q\Fs_Node, Q\Fs_File, Q\Fs_Symlink_File, Q\Fs_Exception, Q\ExecException;

require_once __DIR__ . '/../../init.php';
require_once 'Fs/FileTest.php';
require_once 'Q/Fs/Symlink/File.php';

/**
 * Fs_Symlink_File test case.
 */
class Fs_Symlink_FileTest extends Fs_FileTest
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-fs_symlink_filetest-' . md5(uniqid());
        if (!file_put_contents("{$this->file}.orig", 'Test case for Fs_File')) $this->markTestSkipped("Could not write to '{$this->file}.orig'.");
        if (!symlink($this->file . '.orig', $this->file)) $this->markTestSkipped("Could not create symlink '{$this->file}'.");
        
        $this->Fs_Node = new Fs_Symlink_File($this->file);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->cleanup($this->file);
        $this->Fs_Node = null;
    }

    
    /**
     * Test creating an Fs_Symlink_File for a non-symlink
     */
    public function testConstruct_Symlink()
    {
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.orig' is not a symlink");
    	new Fs_Symlink_File("{$this->file}.orig");
    }
    
    /**
     * Test Fs_Symlink_File->copy() with the Fs::NO_DEFERENCE option
     */
    public function testCopy_NoDereference()
    {
        $new = $this->Fs_Node->copy("{$this->file}.x", Fs::NO_DEREFERENCE);
        
        $this->assertType('Q\Fs_Symlink_File', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertTrue(is_link("{$this->file}.x"));
        $this->assertEquals("{$this->file}.orig", readlink("{$this->file}.x"));
        $this->assertTrue(file_exists($this->file));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.x"));
    }

    /**
     * Tests Fs_Node->create()
     */
    public function testCreate()
    {
    	unlink("{$this->file}.orig");
        umask(0022);
    	$this->Fs_Node->create(0660);
        
    	$this->assertTrue(is_file("{$this->file}.orig"));
        $this->assertEquals('', file_get_contents("{$this->file}.orig"));
    	$this->assertEquals('0640', sprintf('%04o', fileperms("{$this->file}.orig") & 0777));
    }
    
    /**
     * Tests Fs_Node->create(), creating dir
     */
    public function testCreate_Recursive()
    {
        $target = "{$this->file}.y/" . basename($this->file) . ".orig";
        umask(0022);
        $new = Fs::symlink($target, "{$this->file}.x", 0, 'file');
        $this->assertType('Q\Fs_Symlink_File', $new);
    	$new->create(0660, Fs::RECURSIVE);
        
    	$this->assertTrue(is_file($target));
        $this->assertEquals('', file_get_contents($target));
    	$this->assertEquals('0640', sprintf('%04o', fileperms($target) & 0777));
    	$this->assertEquals('0750', sprintf('%04o', fileperms("{$this->file}.y") & 0777));
    }
    
    /**
     * Tests Fs_Node::delete()
     */
    public function testDelete()
    {
        if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
        
        $this->Fs_Node->delete();
        $this->assertFalse(file_exists($this->file));
        $this->assertTrue(file_exists($this->file . '.orig'));
    }
}
