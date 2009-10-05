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
        $this->file = sys_get_temp_dir() . '/q-fs_filetest-' . md5(uniqid());
        if (!file_put_contents($this->file, 'Test case for Fs_File')) $this->markTestSkipped("Could not write to '{$this->file}'.");
        $this->Fs_Node = new Fs_File($this->file);
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
     * Test creating an Fs_File for a dir
     */
    public function testConstruct_Dir()
    {
    	$this->setExpectedException('Q\Fs_Exception', "File '" . __DIR__ . "' is not a regular file, but a directory");
    	new Fs_File(__DIR__);
    }

    /**
     * Test creating an Fs_File for a symlink
     */
    public function testConstruct_Symlink()
    {
    	if (!symlink(__FILE__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is a symlink");
    	new Fs_File("{$this->file}.x");
    }

    /**
     * Test creating an Fs_File for a symlink to a dir
     */
    public function testConstruct_SymlinkDir()
    {
    	if (!symlink(__DIR__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is not a regular file, but a symlink to a directory");
    	new Fs_File("{$this->file}.x");
    }
    
    
    /**
     * Tests Fs_Node->getContents()
     */
    public function testGetContents()
    {
        $this->assertEquals('Test case for Fs_File', $this->Fs_Node->getContents());
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
        $this->assertEquals('Test case for Fs_File', $output);
    }

    /**
     * Tests Fs_Node->open()
     */
    public function testOpen()
    {
        $fp = $this->Fs_Node->open();
        $this->assertTrue(is_resource($fp), "File pointer $fp");
        $this->assertEquals('Test case for Fs_File', fread($fp, 1024));
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
        
        $this->assertEquals(array(array('code'=>E_USER_WARNING, 'message'=>"Exec '{$this->file}': Warning about something"), array('code'=>E_USER_WARNING, 'message'=>"Exec '{$this->file}': Error def")), $warnings);
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
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        
    	$this->assertTrue(is_file("{$this->file}.x"));
        $this->assertEquals('', file_get_contents("{$this->file}.x"));
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
    	$new = new Fs_File($filename);
    	
    	$this->setExpectedException("Q\Fs_Exception", "Unable to create '$filename': Directory '" . dirname($filename) . "' does not exist");
    	$new->create(0660);
    }
    
    /**
     * Tests Fs_Node->create(), creating dir
     */
    public function testCreate_Recursive()
    {
    	$filename = "{$this->file}.y/" . basename("{$this->file}.x");
        $new = new Fs_File($filename);
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
        $new = $this->Fs_Node->copy("{$this->file}.x");
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertTrue(file_exists($this->file));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.x"));
    }

    /**
     * Tests Fs_Node->copy() with existing file
     */
    public function testCopy_FileExists()
    {
        file_put_contents("{$this->file}.x", "Another file");
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}' to '{$this->file}.x': Target already exists");
        $this->Fs_Node->copy("{$this->file}.x");

        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals("Another file", file_exists("{$this->file}.x"));
        $this->assertTrue(file_exists($this->file));
    }

    /**
     * Tests Fs_Node->copy() with existing dir
     */
    public function testCopy_DirExists()
    {
        mkdir("{$this->file}.x");
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}' to '{$this->file}.x': Target already exists");
        $this->Fs_Node->copy("{$this->file}.x");

        $this->assertTrue(is_dir("{$this->file}.x"));
        $this->assertTrue(file_exists($this->file));
    }

    /**
     * Tests Fs_Node->copy() overwriting existing file
     */
    public function testCopy_OverwriteFile()
    {
    	file_put_contents("{$this->file}.x", "Another file");
        $new = $this->Fs_Node->copy("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.x"));

        $this->assertTrue(file_exists($this->file));
    }

    /**
     * Tests Fs_Node->copy() overwriting existing dir
     */
    public function testCopy_OverwriteDir()
    {
    	mkdir("{$this->file}.x");
        $new = $this->Fs_Node->copy("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.x"));

        $this->assertTrue(file_exists($this->file));
    }

    /**
     * Tests Fs_Node->copy() overwriting existing dir
     */
    public function testCopy_NonEmptyDir()
    {
    	mkdir("{$this->file}.x");
    	file_put_contents("{$this->file}.x/" . basename($this->file) . ".y", "Another file");
    	
    	$this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}' to '{$this->file}.x': Target is a non-empty directory");
        $new = $this->Fs_Node->copy("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertTrue(file_exists($this->file));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file) . ".y"));
    }
    
    /**
     * Tests Fs_Node->copyTo()
     */
    public function testCopyTo()
    {
        mkdir("{$this->file}.y");
        $new = $this->Fs_Node->copyTo("{$this->file}.y");
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$new);
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.y/" . basename($this->file)));

        $this->assertTrue(file_exists($this->file));
    }

    /**
     * Tests Fs_Node->copyTo() with missing dir
     */
    public function testCopyTo_NoDir()
    {
		$this->setExpectedException('Q\Fs_Exception', "Unable to copy '{$this->file}' to '{$this->file}.y/': Directory does not exist");
        $new = $this->Fs_Node->copyTo("{$this->file}.y");
        
        $this->assertTrue(file_exists($this->file));
	}

    /**
     * Tests Fs_Node->copyTo() creating dir
     */
    public function testCopyTo_Recursive()
    {
        $new = $this->Fs_Node->copyTo("{$this->file}.y", Fs::RECURSIVE);
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.y/" . basename($this->file)));

        $this->assertTrue(file_exists($this->file));
    }
    
    /**
     * Tests Fs_Node->rename()
     */
    public function testRename()
    {
        $new = $this->Fs_Node->rename("{$this->file}.x");

        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.x"));

        $this->assertFalse(file_exists($this->file));
    }
	
    /**
     * Tests Fs_Node->rename() with existing file
     */
    public function testRename_Exists()
    {
        file_put_contents("{$this->file}.x", "Another file");
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
    	file_put_contents("{$this->file}.x", "Another file");
        $new = $this->Fs_Node->rename("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.x"));

        $this->assertFalse(file_exists($this->file));
    }

    /**
     * Tests Fs_Node->rename() overwriting existing file
     */
    public function testRename_OverwriteDir()
    {
    	mkdir("{$this->file}.x");
        $new = $this->Fs_Node->rename("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_File', $new);
        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.x"));

        $this->assertFalse(file_exists($this->file));
    }
    
    /**
     * Tests Fs_Node->copy() overwriting existing dir
     */
    public function testRename_NonEmptyDir()
    {
    	mkdir("{$this->file}.x");
    	file_put_contents("{$this->file}.x/" . basename($this->file) . ".y", "Another file");
    	
    	$this->setExpectedException("Q\Fs_Exception", "Unable to rename '{$this->file}' to '{$this->file}.x': Target is a non-empty directory");
        $new = $this->Fs_Node->rename("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertTrue(file_exists($this->file));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file) . ".y"));
    }
    
    /**
     * Tests Fs_Node->moveTo()
     */
    public function testMoveTo()
    {
        mkdir("{$this->file}.y");
        $new = $this->Fs_Node->moveTo("{$this->file}.y");
        
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$new);
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.y/" . basename($this->file)));

        $this->assertFalse(file_exists($this->file));
    }

    /**
     * Tests Fs_Node->moveTo() with missing dir
     */
    public function testMoveTo_NoDir()
    {
		$this->setExpectedException('Q\Fs_Exception', "Unable to move '{$this->file}' to '{$this->file}.y/': Directory does not exist");
        $new = $this->Fs_Node->moveTo("{$this->file}.y");

        $this->assertTrue(file_exists($this->file));
    }
    
    /**
     * Tests Fs_Node->moveTo() creating dir
     */
    public function testMoveTo_Recursive()
    {
        $new = $this->Fs_Node->moveTo("{$this->file}.y", Fs::RECURSIVE);
        
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$new);
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_File', file_get_contents("{$this->file}.y/" . basename($this->file)));

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
