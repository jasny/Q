<?php
use Q\Fs, Q\Fs_Node, Q\Fs_Fifo, Q\Fs_Exception, Q\ExecException;

require_once 'Fs/NodeTest.php';
require_once 'Q/Fs/Fifo.php';

/**
 * Fs_Fifo test case.
 */
class Fs_FifoTest extends Fs_NodeTest
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-fs_fifotest-' . md5(uniqid());
        
        if (!posix_mkfifo($this->file, 0777)) $this->markTestSkipped("Could not create fifo file '{$this->file}'.");
        $this->Fs_Node = new Fs_Fifo($this->file);
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
     * Test creating an Fs_Fifo for a dir
     */
/*
    public function testConstruct_Dir()
    {
    	$this->setExpectedException('Q\Fs_Exception', "File '" . __DIR__ . "' is not a regular file, but a directory");
    	new Fs_Fifo(__DIR__);
    }
*/
    /**
     * Test creating an Fs_Fifo for a symlink
     */
    public function testConstruct_Symlink()
    {
    	if (!symlink(__FILE__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is a symlink");
    	new Fs_Fifo("{$this->file}.x");
    }

    /**
     * Test creating an Fs_Fifo for a symlink to a dir
     */
/*
    public function testConstruct_SymlinkDir()
    {
    	if (!symlink(__DIR__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is not a regular file, but a symlink to a directory");
    	new Fs_Fifo("{$this->file}.x");
    }
*/    
    
    /**
     * Tests Fs_Node->getContents()
     */
    public function testGetContents()
    {
    	$this->markTestSkipped("Difficult to test. @todo");
    	
    	$fp = fopen($this->file, "w");
    	stream_set_blocking($fp, 0);
    	fwrite($fp, "This is a test for Fs_Fifo\0");
    	
        $this->assertEquals("This is a test for Fs_Fifo", $this->Fs_Node->getContents());
    }

    /**
     * Tests Fs_Node->putContents()
     */
    public function testPutContents()
    {
        $this->markTestSkipped("Difficult to test. @todo");
    	
        $this->Fs_Node->putContents('Test put contents');
        $this->assertEquals('Test put contents', file_get_contents($this->file));
    }

    /**
     * Tests Fs_Node->output()
     */
    public function testOutput()
    {
        $this->markTestSkipped("Difficult to test. @todo");
    	
        ob_start();
        try {
            $this->Fs_Node->output();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Test case for Fs_Fifo', $output);
    }

    /**
     * Tests Fs_Node->open()
     */
    public function testOpen()
    {
        $this->markTestSkipped("Difficult to test. @todo");
    	
        $fp = $this->Fs_Node->open();
        $this->assertTrue(is_resource($fp), "File pointer $fp");
        $this->assertEquals('Test case for Fs_Fifo', fread($fp, 1024));
    }

    /**
     * Tests Fs_Node::chgrp() for a non existent file
     */
    public function testChgrp_NonExistent()
    {
        $file = new Fs_Fifo('/does/not/exist.' . md5(uniqid()));
        $this->setExpectedException('Q\Fs_Exception', "Unable to change group of '$file' to '0': File does not exist");
        $file->chgrp(0);
    }

    /**
     * Tests Fs_Node::chgrp() for a file I can't touch
     */
    public function testChgrp_Fail()
    {
        $this->markTestSkipped("Difficult to test. @todo");
    	if (is_writable('/etc/passwd')) $this->markTestSkipped("Want to test this on '/etc/passwd', but I actually have permission to change that file. Run this script as an under-privileged user.");
        $file = new Fs_Fifo('/etc/passwd');
        $this->setExpectedException('Q\Fs_Exception', "Failed to change group of '$file' to '0': Operation not permitted");
        $file->chgrp(0);
    }

    
    /**
     * Tests Fs_Node->create()
     */
    public function testCreate()
    {
        $new = new Fs_Fifo("{$this->file}.x");
        umask(0022);
    	$new->create(0660);
        
        $this->assertType('Q\Fs_Fifo', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        
    	$this->assertTrue(file_exists("{$this->file}.x"));
    	$this->assertEquals('fifo', filetype("{$this->file}.x"));
    	$this->assertEquals('0640', sprintf('%04o', fileperms("{$this->file}.x") & 0777));
    }

    /**
     * Tests Fs_Node->create() with existing file
     */
    public function testCreate_Exists()
    {
        $this->setExpectedException("Q\Fs_Exception", "Unable to create '{$this->file}': File already exists");
        $this->Fs_Node->create();
    }

    /**
     * Tests Fs_Node->create() with existing file no error
     */
    public function testCreate_Preserve()
    {
    	$this->Fs_Node->create(0660, Fs::PRESERVE);
    }
    
    /**
     * Tests Fs_Node->create() with existing file
     */
    public function testCreate_NoDir()
    {
    	$filename = "{$this->file}.y/" . basename("{$this->file}.x");
    	$new = new Fs_Fifo($filename);
    	
    	$this->setExpectedException("Q\Fs_Exception", "Unable to create '$filename': Directory '" . dirname($filename) . "' does not exist");
    	$new->create(0660);
    }
    
    /**
     * Tests Fs_Node->create(), creating dir
     */
    public function testCreate_Recursive()
    {
        $this->markTestSkipped("Difficult to test. @todo");
    	
    	$filename = "{$this->file}.y/" . basename("{$this->file}.x");
        $new = new Fs_Fifo($filename);
        umask(0022);
    	$new->create(0660, Fs::RECURSIVE);
        
    	$this->assertTrue(is_file($filename));
        $this->assertEquals('', file_get_contents($filename));
    	$this->assertEquals('0640', sprintf('%04o', fileperms($filename) & 0777));
    	$this->assertEquals('0750', sprintf('%04o', fileperms("{$this->file}.y") & 0777));
    }
    
    /**
     * Tests Fs_Node->copy()
     */
    public function testCopy()
    {
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}': File is a named pipe");
    	$new = $this->Fs_Node->copy("{$this->file}.x");
    }

    /**
     * Tests Fs_Node->copyTo()
     */
    public function testCopyTo()
    {
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}': File is a named pipe");
        $new = $this->Fs_Node->copyTo("{$this->file}.x");
    }

    /**
     * Tests Fs_Node->rename()
     */
    public function testRename()
    {
        $this->markTestSkipped("Difficult to test. @todo");
    	$new = $this->Fs_Node->rename("{$this->file}.x");

        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals('Test case for Fs_Fifo', file_get_contents("{$this->file}.x"));

        $this->assertFalse(file_exists($this->file));
    }
	
    /**
     * Tests Fs_Node->rename() with existing file
     */
    public function testRename_Exists()
    {
        posix_mkfifo("{$this->file}.x", 0777);
    	$this->setExpectedException("Q\Fs_Exception", "Unable to rename '{$this->file}' to '{$this->file}.x': Target already exists");
        $this->Fs_Node->rename("{$this->file}.x");

        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertFalse(file_exists($this->file));
    }
	
    /**
     * Tests Fs_Node->rename() overwriting existing file
     */
    public function testRename_OverwriteFile()
    {
        $this->markTestSkipped("Difficult to test. @todo");
    	
    	file_put_contents("{$this->file}.x", "Another file");
        $new = $this->Fs_Node->rename("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_Fifo', $new);
        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals('Test case for Fs_Fifo', file_get_contents("{$this->file}.x"));

        $this->assertFalse(file_exists($this->file));
    }

    /**
     * Tests Fs_Node->moveTo()
     */
    public function testMoveTo()
    {
        $this->markTestSkipped("Difficult to test. @todo");
    	
        mkdir("{$this->file}.y");
        $new = $this->Fs_Node->moveTo("{$this->file}.y");
        
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$new);
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Fifo', file_get_contents("{$this->file}.y/" . basename($this->file)));

        $this->assertFalse(file_exists($this->file));
    }
    
    /**
     * Tests Fs_Node::delete()
     */
    public function testDelete()
    {
        if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
        
        $this->Fs_Node->delete();
        $this->assertFalse(file_exists($this->file));
    }
}
