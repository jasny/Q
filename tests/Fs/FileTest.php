<?php
use Q\Fs, Q\Fs_Item, Q\Fs_File, Q\Fs_Exception, Q\ExecException;

require_once 'Fs/ItemTest.php';

/**
 * Fs_File test case.
 */
class Fs_FileTest extends Fs_ItemTest
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
    	$this->file = sys_get_temp_dir() . '/q-fs_filetest.' . md5(uniqid());
    	if (!file_put_contents($this->file, 'Test case for Fs_Item')) $this->markTestSkipped("Could not write to '$this->file'.");
    	
        $this->Fs_Item = new Fs_File($this->file);
        parent::setUp();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();
        
        $this->cleanup($this->file);
    	$this->Fs_Item = null;
    }
	
    
    /**
     * Tests Fs_Item->isUploadedFile()
     */
    public function testIsUploadedFile()
    {
        $this->assertFalse($this->Fs_Item->isUploadedFile());
    }

    /**
     * Tests Fs_Item->getContents()
     */
    public function testGetContents()
    {
        $this->assertEquals('Test case for Fs_Item', $this->Fs_Item->getContents());
    }

    /**
     * Tests Fs_Item->putContents()
     */
    public function testPutContents()
    {
        $this->Fs_Item->putContents('Test put contents');
        $this->assertEquals('Test put contents', file_get_contents($this->file));
    }

    /**
     * Tests Fs_Item->output()
     */
    public function testOutput()
    {
		ob_start();
    	try {
    		$this->Fs_Item->output();
    	} catch (Exception $e) {
			ob_end_clean();
            throw $e;    		
    	}
    	
    	$output = ob_get_contents();
    	ob_end_clean();
    	
    	$this->assertEquals('Test case for Fs_Item', $output);
    }

    /**
     * Tests Fs_Item->open()
     */
    public function testOpen()
    {
        $fp = $this->Fs_Item->open();
        $this->assertTrue(is_resource($fp), "File pointer $fp");
        $this->assertEquals('Test case for Fs_Item', fread($fp, 1024));
    }

    
    /**
     * Tests Fs_Item->exec()
     */
    public function testExec()
    {
    	file_put_contents($this->file, 'echo "Test $*"' . "\n");
    	chmod($this->file, 0770);
    	
    	$out = $this->Fs_Item->exec("abc", 222);
    	$this->assertEquals("Test abc 222\n", $out);
    }

    /**
     * Tests Fs_Item->__invoke()
     */
    public function test__invoke()
    {
    	file_put_contents($this->file, 'echo "Test $*"' . "\n");
    	chmod($this->file, 0770);
    	
        $file = $this->Fs_Item;
    	$out = $file("abc", 222);
    	$this->assertEquals("Test abc 222\n", $out);
	}
    
    /**
     * Tests Fs_Item->exec() with a file that does not exist
     */
    public function testExec_NotExists()
    {
    	$file = new Fs_File('/does/not/exist.' . md5(uniqid()));
    	
    	$this->setExpectedException('Q\Fs_Exception', "Unable to execute '$file': File does not exist");
    	$file->exec();
    }
    
    /**
     * Tests Fs_Item->exec() with a file that is not executable
     */
    public function testExec_NotExecutable()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': No permission to execute file");
    	$this->Fs_Item->exec();
    }

    /**
     * Tests Fs_Item->exec() where the script has an error
     */
    public function testExec_ExecException()
    {
    	file_put_contents($this->file, 'echo "Test $1"; echo "Warning about something" >&2; echo "' . $this->file .  ': Error $2" >&2; exit $3' . "\n");
    	chmod($this->file, 0770);
    	
    	$warnings = array();
    	set_error_handler(function ($code, $message) use (&$warnings) { $warnings[] = compact('code', 'message'); }, E_USER_NOTICE | E_USER_WARNING);
    	
    	try {
	    	$this->Fs_Item->exec("abc", "def", 22);
	    	
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
     * Tests Fs_Item::chmod() for a non existent file
     */
    public function testChmod_NonExistent()
    {
    	$file = new Fs_File('/does/not/exist.' . md5(uniqid()));
    	$this->setExpectedException('Q\Fs_Exception', "Unable to change mode of '$file': File does not exist");
    	$file->chmod(0777);
    }

    /**
     * Tests Fs_Item::chmod() for a file I can't touch
     */
    public function testChmod_Fail()
    {
    	if (is_writable('/etc/passwd')) $this->markTestSkipped("Want to test this on '/etc/passwd', but I actually have permission to change that file. Run this script as an under-privileged user.");
    	
    	$file = new Fs_File('/etc/passwd');
    	$this->setExpectedException('Q\Fs_Exception', "Failed to change mode of '$file': Operation not permitted");
    	$file->chmod(0777);
    }
    
    /**
     * Tests Fs_Item::chown() for a non existent file
     */
    public function testChown_NonExistent()
    {
    	$file = new Fs_File('/does/not/exist.' . md5(uniqid()));
    	$this->setExpectedException('Q\Fs_Exception', "Unable to change owner of '$file' to user '0': File does not exist");
    	$file->chown(0);
    }

    /**
     * Tests Fs_Item::chown() for a file I can't touch
     */
    public function testChown_Fail()
    {
    	if (is_writable('/etc/passwd')) $this->markTestSkipped("Want to test this on '/etc/passwd', but I actually have permission to change that file. Run this script as an under-privileged user.");
    	
    	$file = new Fs_File('/etc/passwd');
    	$this->setExpectedException('Q\Fs_Exception', "Failed to change owner of '$file' to user '0': Operation not permitted");
    	$file->chown(0);
    }
    
    /**
     * Tests Fs_Item::chgrp() for a non existent file
     */
    public function testChgrp_NonExistent()
    {
    	$file = new Fs_File('/does/not/exist.' . md5(uniqid()));
    	$this->setExpectedException('Q\Fs_Exception', "Unable to change group of '$file' to '0': File does not exist");
    	$file->chgrp(0);
    }
    
    /**
     * Tests Fs_Item::chgrp() for a file I can't touch
     */
    public function testChgrp_Fail()
    {
    	if (is_writable('/etc/passwd')) $this->markTestSkipped("Want to test this on '/etc/passwd', but I actually have permission to change that file. Run this script as an under-privileged user.");
    	
    	$file = new Fs_File('/etc/passwd');
    	$this->setExpectedException('Q\Fs_Exception', "Failed to change group of '$file' to '0': Operation not permitted");
    	$file->chgrp(0);
    }
    
    
    /**
     * Tests Fs_Item->copy()
     */
    public function testCopy()
    {
        $new = $this->Fs_Item->copy($this->file . '.x');
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals($this->file . '.x', (string)$new);
        $this->assertEquals('Test case for Fs_Item', $new->getContents());
    }
	
    /**
     * Tests Fs_Item->copyTo()
     */
    public function testCopyTo()
    {
    	mkdir($this->file . '.y');
        $new = $this->Fs_Item->copyTo($this->file . '.y');
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals($this->file . '.y/' . basename($this->file), (string)$new);
        $this->assertEquals('Test case for Fs_Item', $new->getContents());
    }

    /**
     * Tests Fs_Item->rename()
     */
    public function testRename()
    {
        $new = $this->Fs_Item->rename($this->file . '.x');
        
        $this->assertSame($this->Fs_Item, $new);
        $this->assertEquals($this->file . '.x', (string)$this->Fs_Item);
        $this->assertEquals('Test case for Fs_Item', $this->Fs_Item->getContents());
    }
    
    /**
     * Tests Fs_Item->rename()
     */
    public function testMoveTo()
    {
    	mkdir($this->file . '.y');
        $new = $this->Fs_Item->moveTo($this->file . '.y');
        
        $this->assertSame($this->Fs_Item, $new);
        $this->assertEquals($this->file . '.y/' . basename($this->file), (string)$this->Fs_Item);
        $this->assertEquals('Test case for Fs_Item', $this->Fs_Item->getContents());
    }
    
    /**
     * Tests Fs_Item::delete()
     */
    public function testDelete()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$this->Fs_Item->delete();
    	$this->assertFalse(file_exists($this->file));
    }
}
