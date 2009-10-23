<?php
use Q\Fs, Q\Fs_Node, Q\Fs_Dir, Q\Fs_Symlink_Dir, Q\Fs_Exception, Q\ExecException;

require_once 'Fs/DirTest.php';
require_once 'Q/Fs/Symlink/Dir.php';

/**
 * Fs_Symlink_Dir test case.
 */
class Fs_Symlink_DirTest extends Fs_DirTest
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-fs_filetest-' . md5(uniqid());
        if (!mkdir($this->file . '.orig')) $this->markTestSkipped("Could not create directory '{$this->file}.orig'");
        if (!file_put_contents("{$this->file}.orig/" . basename($this->file), 'Test case for Fs_Dir')) $this->markTestSkipped("Could not create file '{$this->file}.orig/ " . basename($this->file) . "'");
    	if (!symlink($this->file . '.orig', $this->file)) $this->markTestSkipped("Could not create symlink '{$this->file}'.");
        
        $this->Fs_Node = new Fs_Symlink_Dir($this->file);
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
     * Test creating an Fs_Symlink_Dir for a non-symlink
     */
    public function testConstruct_Symlink()
    {
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.orig' is not a symlink");
    	new Fs_Symlink_Dir("{$this->file}.orig");
    }
    
    /**
     * Test Fs_Symlink_Dir->copy() with the Fs::NO_DEFERENCE option
     */
    public function testCopy_NoDereference()
    {
        $new = $this->Fs_Node->copy("{$this->file}.x", Fs::NO_DEREFERENCE);
        
        $this->assertType('Q\Fs_Symlink_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertTrue(is_link("{$this->file}.x"));
        $this->assertEquals("{$this->file}.orig", readlink("{$this->file}.x"));
        
        $this->assertTrue(is_link($this->file));
        $this->assertEquals("{$this->file}.orig", readlink($this->file));
    }

    /**
     * Tests Fs_Node->create()
     */
    public function testCreate()
    {
    	unlink("{$this->file}.orig/" . basename($this->file));
    	rmdir("{$this->file}.orig");
        umask(0022);
    	$this->Fs_Node->create(0770);
        
    	$this->assertTrue(is_dir("{$this->file}.orig"));
    	$this->assertEquals('0750', sprintf('%04o', fileperms("{$this->file}.orig") & 0777));
    }
    
    /**
     * Tests Fs_Node->create(), creating dir
     */
    public function testCreate_Recursive()
    {
        $target = "{$this->file}.y/" . basename($this->file) . ".orig";
        
        umask(0022);
        $new = Fs::symlink($target, "{$this->file}.x", 0, 'dir');
        $this->assertType('Q\Fs_Symlink_Dir', $new);
    	$new->create(0770, Fs::RECURSIVE);
        
    	$this->assertTrue(is_dir("{$this->file}.y/" . basename($this->file) . ".orig"));
    	$this->assertEquals('0750', sprintf('%04o', fileperms($target) & 0777));
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
        $this->assertTrue(file_exists("{$this->file}.orig"));
    }

    /**
     * Skip
     */
    public function testDelete_NotEmpty()
    {}
    
    /**
     * Tests Fs_Node::delete() doing a recursive delete
     */
    public function testDelete_Recursive()
    {
        if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
        
        $this->Fs_Node->delete(Fs::RECURSIVE);
        $this->assertFalse(file_exists($this->file));
        $this->assertTrue(file_exists("{$this->file}.orig"));
    }
    
    
    /**
     * Tests Fs_Node->getContents()
     */
    public function testGetContents()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to get the contents of '{$this->file}': File is a symlink to a directory");
    	$this->Fs_Node->getContents();
    }

    /**
     * Tests Fs_Node->putContents()
     */
    public function testPutContents()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to write data to '{$this->file}': File is a symlink to a directory");
    	$this->Fs_Node->putContents('test');
	}

    /**
     * Tests Fs_Node->output()
     */
    public function testOutput()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to get the contents of '{$this->file}': File is a symlink to a directory");
    	$this->Fs_Node->getContents();
	}
	
    /**
     * Tests Fs_Node->open()
     */
    public function testOpen()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to open '{$this->file}': File is a symlink to a directory");
        $this->Fs_Node->open();
    }

    /**
     * Tests Fs_Node->exec()
     */
    public function testExec()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': This is not a regular file, but a symlink to a directory");
        $this->Fs_Node->exec();
    }

    /**
     * Tests Fs_Node->__invoke()
     */
    public function test__invoke()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': This is not a regular file, but a symlink to a directory");
        $file = $this->Fs_Node;
        $file();
	}
}
