<?php
use Q\Fs, Q\Fs_Node, Q\Fs_File, Q\Fs_Exception, Q\ExecException;

require_once 'Fs/NodeTest.php';
require_once 'Q/Fs/File.php';

/**
 * Fs_File test case.
 */
class Fs_FileTest extends Fs_NodeTest
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-fs_filetest.' . md5(uniqid());
        if (!file_put_contents($this->file, 'Test case for Fs_Node')) $this->markTestSkipped("Could not write to '{$this->file}'.");
        $this->Fs_Node = new Fs_File($this->file);
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
     * Test creating an Fs_File for a dir
     */
    public function testConstruct_Dir()
    {
    	$this->setExpectedException('Q\Fs_Exception', "File '" . __DIR__ . "' is not a regular file, but a directory");
    	new Fs_File(__DIR__);
    }

    /**
     * Test creating an Fs_File for a dir
     */
    public function testConstruct_Symlink()
    {
    	if (!symlink($this->file, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	
    	$this->setExpectedException('Q\Fs_Exception', "File '" . __DIR__ . "' is a symlink");
    	new Fs_File(__DIR__);
    }

    /**
     * Test creating an Fs_File for a symlink to a dir
     */
    public function testConstruct_SymlinkDir()
    {
    	if (!symlink(__DIR__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is not a regular file, but a symlink to a directory");
    	new Fs_File(__DIR__);
    }
    
    
    /**
     * Tests Fs_Node->getContents()
     */
    public function testGetContents()
    {
        $this->assertEquals('Test case for Fs_Node', $this->Fs_Node->getContents());
    }

    /**
     * Tests Fs_Node->putContents()
     */
    public function testPutContents()
    {
        $this->Fs_Node->putContents('Test put contents');
        $this->assertEquals('Test put contents', file_get_contents($this->file));
    }

    /**
     * Tests Fs_Node->output()
     */
    public function testOutput()
    {
        ob_start();
        try {
            $this->Fs_Node->output();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('Test case for Fs_Node', $output);
    }

    /**
     * Tests Fs_Node->open()
     */
    public function testOpen()
    {
        $fp = $this->Fs_Node->open();
        $this->assertTrue(is_resource($fp), "File pointer $fp");
        $this->assertEquals('Test case for Fs_Node', fread($fp, 1024));
    }

    /**
     * Tests Fs_Node->exec()
     */
    public function testExec()
    {
        file_put_contents($this->file, 'echo "Test $*"' . "\n");
        chmod($this->file, 0770);
        $out = $this->Fs_Node->exec("abc", 222);
        $this->assertEquals("Test abc 222\n", $out);
    }

    /**
     * Tests Fs_Node->__invoke()
     */
    public function test__invoke()
    {
        file_put_contents($this->file, 'echo "Test $*"' . "\n");
        chmod($this->file, 0770);
        $file = $this->Fs_Node;
        $out = $file("abc", 222);
        $this->assertEquals("Test abc 222\n", $out);
    }

    /**
     * Tests Fs_Node->exec() with a file that does not exist
     */
    public function testExec_NotExists()
    {
        $file = new Fs_File('/does/not/exist.' . md5(uniqid()));
        $this->setExpectedException('Q\Fs_Exception', "Unable to execute '$file': File does not exist");
        $file->exec();
    }

    /**
     * Tests Fs_Node->exec() with a file that is not executable
     */
    public function testExec_NotExecutable()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': No permission to execute file");
        $this->Fs_Node->exec();
    }

    /**
     * Tests Fs_Node->exec() where the script has an error
     */
    public function testExec_ExecException()
    {
        file_put_contents($this->file, 'echo "Test $1"; echo "Warning about something" >&2; echo "' . $this->file . ': Error $2" >&2; exit $3' . "\n");
        chmod($this->file, 0770);
        $warnings = array();
        set_error_handler(function ($code, $message) use (&$warnings) { $warnings[] = compact('code', 'message'); }, E_USER_NOTICE | E_USER_WARNING);
        try {
            @$this->Fs_Node->exec("abc", "def", 22);
            restore_error_handler();
            $this->fail('An expected Exception has not been raised.');
        } catch (ExecException $exception) {
            restore_error_handler();
            $this->assertEquals("Execution of '{$this->file}' exited with return code 22", $exception->getMessage());
            $this->assertEquals(22, $exception->getCode(), 'code');
            $this->assertEquals(22, $exception->getReturnVar(), 'return var');
            $this->assertEquals("Test abc\n", $exception->getStdout());
            $this->assertEquals("Warning about something\n{$this->file}: Error def\n", $exception->getStderr());
        } catch (Exception $exception) {
            restore_error_handler();
            throw $exception;
        }
        $this->assertEquals(array(array('code'=>E_USER_NOTICE, 'message'=>"Exec '{$this->file}': Warning about something"), array('code'=>E_USER_NOTICE, 'message'=>"Exec '{$this->file}': Error def")), $warnings);
    }

    /**
     * Tests Fs_Node::chmod() for a non existent file
     */
    public function testChmod_NonExistent()
    {
        $file = new Fs_File('/does/not/exist.' . md5(uniqid()));
        $this->setExpectedException('Q\Fs_Exception', "Unable to change mode of '$file': File does not exist");
        $file->chmod(0777);
    }

    /**
     * Tests Fs_Node::chmod() for a file I can't touch
     */
    public function testChmod_Fail()
    {
        if (is_writable('/etc/passwd')) $this->markTestSkipped("Want to test this on '/etc/passwd', but I actually have permission to change that file. Run this script as an under-privileged user.");
        $file = new Fs_File('/etc/passwd');
        $this->setExpectedException('Q\Fs_Exception', "Failed to change mode of '$file': Operation not permitted");
        $file->chmod(0777);
    }

    /**
     * Tests Fs_Node::chown() for a non existent file
     */
    public function testChown_NonExistent()
    {
        $file = new Fs_File('/does/not/exist.' . md5(uniqid()));
        $this->setExpectedException('Q\Fs_Exception', "Unable to change owner of '$file' to user '0': File does not exist");
        $file->chown(0);
    }

    /**
     * Tests Fs_Node::chown() for a file I can't touch
     */
    public function testChown_Fail()
    {
        if (is_writable('/etc/passwd')) $this->markTestSkipped("Want to test this on '/etc/passwd', but I actually have permission to change that file. Run this script as an under-privileged user.");
        $file = new Fs_File('/etc/passwd');
        $this->setExpectedException('Q\Fs_Exception', "Failed to change owner of '$file' to user '0': Operation not permitted");
        $file->chown(0);
    }

    /**
     * Tests Fs_Node::chgrp() for a non existent file
     */
    public function testChgrp_NonExistent()
    {
        $file = new Fs_File('/does/not/exist.' . md5(uniqid()));
        $this->setExpectedException('Q\Fs_Exception', "Unable to change group of '$file' to '0': File does not exist");
        $file->chgrp(0);
    }

    /**
     * Tests Fs_Node::chgrp() for a file I can't touch
     */
    public function testChgrp_Fail()
    {
        if (is_writable('/etc/passwd')) $this->markTestSkipped("Want to test this on '/etc/passwd', but I actually have permission to change that file. Run this script as an under-privileged user.");
        $file = new Fs_File('/etc/passwd');
        $this->setExpectedException('Q\Fs_Exception', "Failed to change group of '$file' to '0': Operation not permitted");
        $file->chgrp(0);
    }

    
    /**
     * Tests Fs_Node->create()
     */
    public function testCreate()
    {
        $new = new Fs_File("{$this->file}.x");
        umask(0022);
    	$new->create(0660);
        
    	$this->assertTrue(is_file($new));
        $this->assertEquals('', file_get_contents($new));
    	$this->assertEquals('0640', sprintf('%04o', fileperms($new) & 0777));
    }

    /**
     * Tests Fs_Node->create() with existing file
     */
    public function testCreate_Exitst()
    {
        $this->setExpectedException("Q\Fs_Exception", "Unable to create '{$this->file}': File already exists");
        $this->Fs_Node->create();
    }

    /**
     * Tests Fs_Node->create() with existing file no error
     */
    public function testCreate_Preserve()
    {
    	$this->Fs_Node->create(Fs::PRESERVE);
    }
    
    /**
     * Tests Fs_Node->create() with existing file
     */
    public function testCreate_Recursive()
    {
        $new = new Fs_File("{$this->file}.y/" . basename("{$this->file}.x"));
        umask(0022);
    	$new->create(0660, Fs::RECURSIVE);
        
    	$this->assertTrue(is_file($new));
        $this->assertEquals('', file_get_contents($new));
    	$this->assertEquals('0640', sprintf('%04o', fileperms($new) & 0777));
    	$this->assertEquals('0750', sprintf('%04o', fileperms(dirname($new)) & 0777));
    }
    
    /**
     * Tests Fs_Node->copy()
     */
    public function testCopy()
    {
        $new = $this->Fs_Node->copy("{$this->file}.x");
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertEquals('Test case for Fs_Node', $new->getContents());
    }

    /**
     * Tests Fs_Node->copy() with existing file
     */
    public function testCopy_Exitst()
    {
        file_put_contents("{$this->file}.x", "Another file");
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}' to '{$this->file}.x': Target already exists");
        $this->Fs_Node->copy("{$this->file}.x");
    }

    /**
     * Tests Fs_Node->copy() overwriting existing file
     */
    public function testCopy_Overwrite()
    {
    	file_put_contents("{$this->file}.x", "Another file");
        $new = $this->Fs_Node->copy("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertEquals('Test case for Fs_Node', $new->getContents());
    }
    
    /**
     * Tests Fs_Node->copyTo()
     */
    public function testCopyTo()
    {
        mkdir("{$this->file}.y");
        $new = $this->Fs_Node->copyTo("{$this->file}.y");
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals($this->file . '.y/' . basename($this->file), (string)$new);
        $this->assertEquals('Test case for Fs_Node', $new->getContents());
    }

    /**
     * Tests Fs_Node->copyTo() with missing dir
     */
    public function testCopyTo_NoDir()
    {
		$this->setExpectedException('Q\Fs_Exception', "Unable to copy '{$this->file}' to '{$this->file}.y/': Directory does not exist");
        $new = $this->Fs_Node->copyTo("{$this->file}.y");
    }

    /**
     * Tests Fs_Node->copyTo()
     */
    public function testCopyTo_Recursive()
    {
        $new = $this->Fs_Node->copyTo("{$this->file}.y", Fs::RECURSIVE);
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals($this->file . '.y/' . basename($this->file), (string)$new);
        $this->assertEquals('Test case for Fs_Node', $new->getContents());
    }
    
    /**
     * Tests Fs_Node->rename()
     */
    public function testRename()
    {
        $new = $this->Fs_Node->rename("{$this->file}.x");
        $this->assertSame($this->Fs_Node, $new);
        $this->assertEquals("{$this->file}.x", (string)$this->Fs_Node);
        $this->assertEquals('Test case for Fs_Node', $this->Fs_Node->getContents());
    }

    /**
     * Tests Fs_Node->rename()
     */
    public function testMoveTo()
    {
        mkdir("{$this->file}.y");
        $new = $this->Fs_Node->moveTo("{$this->file}.y");
        $this->assertSame($this->Fs_Node, $new);
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$this->Fs_Node);
        $this->assertEquals('Test case for Fs_Node', $this->Fs_Node->getContents());
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
