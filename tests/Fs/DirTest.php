<?php
use Q\Fs, Q\Fs_Node, Q\Fs_Dir, Q\Fs_Exception, Q\ExecException;

require_once 'Fs/NodeTest.php';
require_once 'Q/Fs/Dir.php';

/**
 * Fs_Dir test case.
 */
class Fs_DirTest extends Fs_NodeTest
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-fs_filetest-' . md5(uniqid());
        if (!mkdir($this->file)) $this->markTestSkipped("Could not create directory '{$this->file}'");
        if (!file_put_contents("{$this->file}/" . basename($this->file), 'Test case for Fs_Dir')) $this->markTestSkipped("Could not create file '{$this->file}/ " . basename($this->file) . "'");

        $this->Fs_Node = new Fs_Dir($this->file);
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
     * Test creating an Fs_Dir for a file
     */
    public function testConstruct_File()
    {
    	$this->setExpectedException('Q\Fs_Exception', "File '" . __FILE__ . "' is not a directory, but a file");
    	new Fs_Dir(__FILE__);
    }

    /**
     * Test creating an Fs_Dir for a symlink
     */
    public function testConstruct_Symlink()
    {
    	if (!symlink(__DIR__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is a symlink");
    	new Fs_Dir("{$this->file}.x");
    }

    /**
     * Test creating an Fs_Dir for a symlink to a file
     */
    public function testConstruct_SymlinkFile()
    {
    	if (!symlink(__FILE__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is not a directory, but a symlink to a file");
    	new Fs_Dir("{$this->file}.x");
    }
    
    
    /**
     * Tests Fs_Node->getContents()
     */
    public function testGetContents()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to get the contents of '{$this->file}': File is a directory");
    	$this->Fs_Node->getContents();
    }

    /**
     * Tests Fs_Node->putContents()
     */
    public function testPutContents()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to write data to '{$this->file}': File is a directory");
    	$this->Fs_Node->putContents('test');
	}

    /**
     * Tests Fs_Node->output()
     */
    public function testOutput()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to get the contents of '{$this->file}': File is a directory");
    	$this->Fs_Node->getContents();
	}
	
    /**
     * Tests Fs_Node->open()
     */
    public function testOpen()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to open '{$this->file}': File is a directory");
        $this->Fs_Node->open();
    }

    /**
     * Tests Fs_Node->exec()
     */
    public function testExec()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': This is not a regular file, but a directory");
        $this->Fs_Node->exec();
    }

    /**
     * Tests Fs_Node->__invoke()
     */
    public function test__invoke()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': This is not a regular file, but a directory");
        $file = $this->Fs_Node;
        $file();
	}
	
	
    /**
     * Tests Fs_Node::chmod() for a non existent file
     */
    public function testChmod_NonExistent()
    {
        $file = new Fs_Dir('/does/not/exist.' . md5(uniqid()));
        $this->setExpectedException('Q\Fs_Exception', "Unable to change mode of '$file': File does not exist");
        $file->chmod(0777);
    }

    /**
     * Tests Fs_Node::chown() for a non existent file
     */
    public function testChown_NonExistent()
    {
        $file = new Fs_Dir('/does/not/exist.' . md5(uniqid()));
        $this->setExpectedException('Q\Fs_Exception', "Unable to change owner of '$file' to user '0': File does not exist");
        $file->chown(0);
    }

    /**
     * Tests Fs_Node::chgrp() for a non existent file
     */
    public function testChgrp_NonExistent()
    {
        $file = new Fs_Dir('/does/not/exist.' . md5(uniqid()));
        $this->setExpectedException('Q\Fs_Exception', "Unable to change group of '$file' to '0': File does not exist");
        $file->chgrp(0);
    }
	
    
    /**
     * Tests Fs_Node->create()
     */
    public function testCreate()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$new = new Fs_Dir("{$this->file}.x");
        umask(0022);
    	$new->create(0770);
        
    	$this->assertTrue(is_dir($new));
    	$this->assertEquals('0750', sprintf('%04o', fileperms($new) & 0777));
    }

    /**
     * Tests Fs_Node->create() with existing file
     */
    public function testCreate_Exists()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$this->setExpectedException("Q\Fs_Exception", "Unable to create directory '{$this->file}': File already exists");
        $this->Fs_Node->create();
    }

    /**
     * Tests Fs_Node->create() with existing file no error
     */
    public function testCreate_Preserve()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$this->Fs_Node->create(0770, Fs::PRESERVE);
    }
    
    /**
     * Tests Fs_Node->create() with existing file
     */
    public function testCreate_Recursive()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$filename = "{$this->file}.y/" . basename($this->file);
        $new = new Fs_Dir($filename);
        umask(0022);
    	$new->create(0770, Fs::RECURSIVE);
        
    	$this->assertTrue(is_dir($filename));
    	$this->assertEquals('0750', sprintf('%04o', fileperms($filename) & 0777));
    	$this->assertEquals('0750', sprintf('%04o', fileperms(dirname($filename)) & 0777));
    }
    
    
    /**
     * Tests Fs_Node->copy()
     */
    public function testCopy()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$new = $this->Fs_Node->copy("{$this->file}.x");
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        
        $this->assertTrue(is_dir("{$this->file}.x"));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));
	
        $this->assertTrue(is_dir($this->file));
        $this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
    }

    /**
     * Tests Fs_Node->copy() with existing file
     */
    public function testCopy_FileExists()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	file_put_contents("{$this->file}.x", "Another file");
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}' to '{$this->file}.x': Target already exists");
        $this->Fs_Node->copy("{$this->file}.x");

        $this->assertTrue(file_exists("{$this->file}.x"));
        $this->assertEquals("Another file", file_exists("{$this->file}.x"));
        
        $this->assertTrue(is_dir($this->file));
        $this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
    }

    /**
     * Tests Fs_Node->copy() with existing dir
     */
    public function testCopy_DirExists()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	mkdir("{$this->file}.x");
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}' to '{$this->file}.x': Target already exists");
        $this->Fs_Node->copy("{$this->file}.x");

        $this->assertTrue(is_dir("{$this->file}.x"));
        $this->assertFalse(file_exists("{$this->file}.x/" . basename($this->file)));
        
        $this->assertTrue(is_dir($this->file));
        $this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
    }

    /**
     * Tests Fs_Node->copy() overwriting existing file
     */
    public function testCopy_OverwriteFile()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	file_put_contents("{$this->file}.x", "Another file");
        $new = $this->Fs_Node->copy("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        
        $this->assertTrue(is_dir("{$this->file}.x"));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));

    	$this->assertTrue(is_dir($this->file));
    	$this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
    }

    /**
     * Tests Fs_Node->copy() overwriting existing dir
     */
    public function testCopy_OverwriteDir()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	mkdir("{$this->file}.x");
        $new = $this->Fs_Node->copy("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        
        $this->assertTrue(is_dir("{$this->file}.x"));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));
    	
    	$this->assertTrue(is_dir($this->file));
    	$this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
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
     * Tests Fs_Node->copy() merging existing dir
     */
    public function testCopy_Merge()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	mkdir("{$this->file}.x");
    	file_put_contents("{$this->file}.x/" . basename($this->file) . ".y", "Another file");
    	$new = $this->Fs_Node->copy("{$this->file}.x", Fs::MERGE);
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertTrue(is_dir("{$this->file}.x"));
        
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));

        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file) . '.y'));
        $this->assertEquals('Another file', file_get_contents("{$this->file}.x/" . basename($this->file) . '.y'));
        
    	$this->assertTrue(is_dir($this->file));
    	$this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
	}

    /**
     * Tests Fs_Node->copy() merging existing dir, overwriting files
     */
    public function testCopy_Merge_Overwrite()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	mkdir("{$this->file}.x");
    	file_put_contents("{$this->file}/" . basename($this->file) . ".x", "I am the file");
    	file_put_contents("{$this->file}.x/" . basename($this->file) . ".x", "I am scared");
    	file_put_contents("{$this->file}.x/" . basename($this->file) . ".y", "Another file");
    	$new = $this->Fs_Node->copy("{$this->file}.x", Fs::MERGE | Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        $this->assertTrue(is_dir("{$this->file}.x"));

        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));

        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file) . '.x'));
        $this->assertEquals('I am the file', file_get_contents("{$this->file}.x/" . basename($this->file) . '.x'));

        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file) . '.y'));
        $this->assertEquals('Another file', file_get_contents("{$this->file}.x/" . basename($this->file) . '.y'));
        
    	$this->assertTrue(is_dir($this->file));
    	$this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
	}
	
    /**
     * Tests Fs_Node->copyTo()
     */
    public function testCopyTo()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	mkdir("{$this->file}.y");
        $new = $this->Fs_Node->copyTo("{$this->file}.y");
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$new);
        
        $this->assertTrue(is_dir("{$this->file}.y/" . basename($this->file)));
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file) . "/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.y/" . basename($this->file) . "/" . basename($this->file)));

        $this->assertTrue(is_dir($this->file));
        $this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
    }

    /**
     * Tests Fs_Node->copyTo() with missing dir
     */
    public function testCopyTo_NoDir()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$this->setExpectedException('Q\Fs_Exception', "Unable to copy '{$this->file}' to '{$this->file}.y/': Directory does not exist");
        $new = $this->Fs_Node->copyTo("{$this->file}.y");
        
        $this->assertTrue(is_dir($this->file));
        $this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
	}

    /**
     * Tests Fs_Node->copyTo() creating dir
     */
    public function testCopyTo_Recursive()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$new = $this->Fs_Node->copyTo("{$this->file}.y", Fs::RECURSIVE);
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$new);

        $this->assertTrue(is_dir("{$this->file}.y/" . basename($this->file)));
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file) . "/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.y/" . basename($this->file) . "/" . basename($this->file)));

    	$this->assertTrue(is_dir($this->file));
    	$this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
    }
    
    /**
     * Tests Fs_Node->rename()
     */
    public function testRename()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$new = $this->Fs_Node->rename("{$this->file}.x");

        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);
        
        $this->assertTrue(is_dir("{$this->file}.x"));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));

    	$this->assertFalse(file_exists($this->file));
	}
	
    /**
     * Tests Fs_Node->rename() with existing file
     */
    public function testRename_Exists()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	file_put_contents("{$this->file}.x", "Another file");
        $this->setExpectedException("Q\Fs_Exception", "Unable to rename '{$this->file}' to '{$this->file}.x': Target already exists");
        $this->Fs_Node->rename("{$this->file}.x");

        $this->assertTrue(is_dir("{$this->file}.x"));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));
        
        $this->assertFalse(file_exists($this->file));
    }
	
    /**
     * Tests Fs_Node->rename() overwriting existing file
     */
    public function testRename_OverwriteFile()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	file_put_contents("{$this->file}.x", "Another file");
        $new = $this->Fs_Node->rename("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);

        $this->assertTrue(is_dir("{$this->file}.x/"));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));
		
    	$this->assertFalse(file_exists($this->file));
	}

    /**
     * Tests Fs_Node->rename() overwriting existing file
     */
    public function testRename_OverwriteDir()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	mkdir("{$this->file}.x");
        $new = $this->Fs_Node->rename("{$this->file}.x", Fs::OVERWRITE);
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.x", (string)$new);

        $this->assertTrue(is_dir("{$this->file}.x/"));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.x/" . basename($this->file)));
		
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
        
        $this->assertTrue(is_dir($this->file));
        $this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
        $this->assertTrue(file_exists("{$this->file}.x/" . basename($this->file) . ".y"));
    }
    
    /**
     * Tests Fs_Node->moveTo()
     */
    public function testMoveTo()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	mkdir("{$this->file}.y");
        $new = $this->Fs_Node->moveTo("{$this->file}.y");
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$new);
        
        $this->assertTrue(is_dir("{$this->file}.y/" . basename($this->file)));
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file) . "/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.y/" . basename($this->file) . "/" . basename($this->file)));

        $this->assertFalse(file_exists($this->file));
	}

    /**
     * Tests Fs_Node->moveTo() with missing dir
     */
    public function testMoveTo_NoDir()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
    	$this->setExpectedException('Q\Fs_Exception', "Unable to move '{$this->file}' to '{$this->file}.y/': Directory does not exist");
        $new = $this->Fs_Node->moveTo("{$this->file}.y");
        
        $this->assertFalse(is_dir($this->file));
        $this->assertTrue(file_exists("{$this->file}/" . basename($this->file)));
    }
    
    /**
     * Tests Fs_Node->moveTo() creating dir
     */
    public function testMoveTo_Recursive()
    {
    	if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
    	
        $new = $this->Fs_Node->moveTo("{$this->file}.y", Fs::RECURSIVE);
        
        $this->assertType('Q\Fs_Dir', $new);
        $this->assertEquals("{$this->file}.y/" . basename($this->file), (string)$new);
        
        $this->assertTrue(is_dir("{$this->file}.y/" . basename($this->file)));
        $this->assertTrue(file_exists("{$this->file}.y/" . basename($this->file) . "/" . basename($this->file)));
        $this->assertEquals('Test case for Fs_Dir', file_get_contents("{$this->file}.y/" . basename($this->file) . "/" . basename($this->file)));

        $this->assertFalse(file_exists($this->file));
	}
    
    /**
     * Tests Fs_Node::delete()
     */
    public function testDelete()
    {
        if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");

        unlink("{$this->file}/" . basename($this->file)); // Make the dir empty
        
        $this->Fs_Node->delete();
        $this->assertFalse(file_exists($this->file));
    }

    /**
     * Tests Fs_Node::delete() when the dir is not empty
     */
    public function testDelete_NotEmpty()
    {
        if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");

        $this->setExpectedException('Q\Fs_Exception', "Failed to delete '{$this->file}': Directory not empty");
        $this->Fs_Node->delete();
    }
    
    /**
     * Tests Fs_Node::delete() doing a recursive delete
     */
    public function testDelete_Recursive()
    {
        if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");

		mkdir("{$this->file}/" . basename($this->file) . ".x");
		file_put_contents("{$this->file}/" . basename($this->file) . ".x/" . basename($this->file), 'Some file');
        
        $this->Fs_Node->delete(Fs::RECURSIVE);
        $this->assertFalse(file_exists($this->file));
    }
    
    
 	/**
 	 * Test Fs_Dir->__get().
 	 * More tests for Fs_Dir->get().
 	 * 
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public final function test__get()
 	{
 		$filename = basename($this->file);
    	$file = $this->Fs_Node->$filename;
    	
    	$this->assertType('Q\Fs_File', $file);
    	$this->assertEquals("{$this->file}/$filename", (string)$file);
	}

 	/**
 	 * Test Fs_Dir->__isset().
 	 * More tests for Fs_Dir->has().
 	 *  
 	 * @param string $name
 	 * @return Fs_Node
 	 */
 	public function test__isset()
 	{
 		$filename = basename($this->file);
    	$this->assertTrue(isset($this->Fs_Node->$filename));
    	
 		$filename = 'doesnt_exist.' . md5(uniqid());
    	$this->assertFalse(isset($this->Fs_Node->$filename));
 	}
 	
    /**
     * Tests Fs::has()
     */
    public function testHas()
    {
        $this->assertTrue($this->Fs_Node->has(basename($this->file)));
    }

    /**
     * Tests Fs::has() with a directory
     */
    public function testHas_Dir()
    {
    	mkdir("{$this->file}/" . basename($this->file) . '.x');
        $this->assertTrue($this->Fs_Node->has(basename($this->file) . '.x'));
    }
    
    /**
     * Tests Fs::has() with a non-existing file
     */
    public function testHas_NotExists()
    {
 		$filename = 'doesnt_exist.' . md5(uniqid());
    	$this->assertFalse($this->Fs_Node->has($filename));
    }
    
    /**
     * Tests Fs::has() with a broken symlink
     */
    public function testHas_Symlink_Broken()
    {
    	symlink('doesnt_exist.' . md5(uniqid()), "{$this->file}/" . basename($this->file) . '.x');
    	
        $this->assertTrue($this->Fs_Node->has(basename($this->file) . '.x'));
    }

    /**
     * Tests Fs::get()
     */
    public function testGet()
    {
    	$file = $this->Fs_Node->get(basename($this->file));
    	
    	$this->assertType('Q\Fs_File', $file);
    	$this->assertEquals("{$this->file}/" . basename($this->file), (string)$file);
    }
    
    /**
     * Tests Fs::get() with a directory
     */
    public function testGet_Dir()
    {
    	mkdir("{$this->file}/" . basename($this->file) . '.x');
    	$file = $this->Fs_Node->get(basename($this->file) . '.x');
    	
    	$this->assertType('Q\Fs_Dir', $file);
    	$this->assertEquals("{$this->file}/" . basename($this->file) . '.x', (string)$file);
    }
    
    /**
     * Tests Fs::has() with a non-existing file
     */
    public function testGet_NotExists()
    {
    	$filename = 'doesnt_exist.' . md5(uniqid());
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}/$filename' does not exist");
    	$file = $this->Fs_Node->get($filename);
	}

    /**
     * Tests Fs::has() with an absolute path
     */
    public function testGet_Absolute()
    {
    	$this->setExpectedException('Q\Exception', "Unable to get '/var/log' for '{$this->file}': Expecting a relative path.");
    	$file = $this->Fs_Node->get('/var/log');
	}

    /**
     * Tests Fs::has() with a symlink
     */
    public function testGet_Symlink_File()
    {
    	symlink("{$this->file}/" . basename($this->file), "{$this->file}/" . basename($this->file) . '.x');
    	$file = $this->Fs_Node->get(basename($this->file) . '.x');
    	
    	$this->assertType('Q\Fs_Symlink_File', $file);
    	$this->assertEquals("{$this->file}/" . basename($this->file) . '.x', (string)$file);
	}
	
    /**
     * Tests Fs::has() with a broken symlink
     */
    public function testGet_Symlink_Broken()
    {
    	symlink('doesnt_exist.' . md5(uniqid()), "{$this->file}/" . basename($this->file) . '.x');
    	$file = $this->Fs_Node->get(basename($this->file) . '.x');
    	
    	$this->assertType('Q\Fs_Symlink_Broken', $file);
    	$this->assertEquals("{$this->file}/" . basename($this->file) . '.x', (string)$file);
	}
    
	
    /**
     * Tests Fs_Node->file()
     */
    public function testFile()
    {
        $file = $this->Fs_Node->file('test');
        
        $this->assertType('Q\Fs_File', $file);
        $this->assertEquals("{$this->file}/test", (string)$file);
	}
    
    /**
     * Tests Fs_Node->dir()
     */
    public function testDir()
    {
        $file = $this->Fs_Node->dir('test');
        
        $this->assertType('Q\Fs_Dir', $file);
        $this->assertEquals("{$this->file}/test", (string)$file);
	}
	
    /**
     * Tests Fs_Node->block()
     */
    public function testBlock()
    {
        $file = $this->Fs_Node->block('test');
        
        $this->assertType('Q\Fs_Block', $file);
        $this->assertEquals("{$this->file}/test", (string)$file);
	}

    /**
     * Tests Fs_Node->char()
     */
    public function testChar()
    {
        $file = $this->Fs_Node->char('test');
        
        $this->assertType('Q\Fs_Char', $file);
        $this->assertEquals("{$this->file}/test", (string)$file);
	}
	
    /**
     * Tests Fs_Node->socket()
     */
    public function testSocket()
    {
        $file = $this->Fs_Node->socket('test');
        
        $this->assertType('Q\Fs_Socket', $file);
        $this->assertEquals("{$this->file}/test", (string)$file);
	}
    
    /**
     * Tests Fs_Node->fifo()
     */
    public function testFifo()
    {
        $file = $this->Fs_Node->fifo('test');
        
        $this->assertType('Q\Fs_Fifo', $file);
        $this->assertEquals("{$this->file}/test", (string)$file);
	}
	
	
	/**
	 * Test traversing through a directory
	 */
	public function testTraverse()
	{
		symlink("{$this->file}/" . basename($this->file), "{$this->file}/" . basename($this->file) . '.x');
		mkdir("{$this->file}/" . basename($this->file) . '.y');
		
		foreach ($this->Fs_Node as $key=>$file) {
			$files[$key] = $file;
		}
				
		$this->assertArrayHasKey(basename($this->file), $files);
		$this->assertType('Q\Fs_File', $files[basename($this->file)]);
		$this->assertEquals("{$this->file}/" . basename($this->file), (string)$files[basename($this->file)]);
		
		$this->assertArrayHasKey(basename($this->file) . '.x', $files);
		$this->assertType('Q\Fs_Symlink_File', $files[basename($this->file) . '.x']);
		$this->assertEquals("{$this->file}/" . basename($this->file) . '.x', (string)$files[basename($this->file) . '.x']);
		
		$this->assertArrayHasKey(basename($this->file) . '.y', $files);
		$this->assertType('Q\Fs_Dir', $files[basename($this->file) . '.y']);
		$this->assertEquals("{$this->file}/" . basename($this->file) . '.y', (string)$files[basename($this->file) . '.y']);

		$this->assertEquals(3, count($files), array_keys($files));
	}
	
	/**
	 * Test traversing through a directory, having to rewind
	 */
	public function testTraverse_Rewind()
	{
		symlink("{$this->file}/" . basename($this->file), "{$this->file}/" . basename($this->file) . '.x');
		mkdir("{$this->file}/" . basename($this->file) . '.y');

		next($this->Fs_Node);
		next($this->Fs_Node);
		
		$files = array();
		foreach ($this->Fs_Node as $key=>$file) {
			$files[$key] = $file;
		}
		
		$this->assertArrayHasKey(basename($this->file), $files);
		$this->assertType('Q\Fs_File', $files[basename($this->file)]);
		$this->assertEquals("{$this->file}/" . basename($this->file), (string)$files[basename($this->file)]);
		
		$this->assertArrayHasKey(basename($this->file) . '.x', $files);
		$this->assertType('Q\Fs_Symlink_File', $files[basename($this->file) . '.x']);
		$this->assertEquals("{$this->file}/" . basename($this->file) . '.x', (string)$files[basename($this->file) . '.x']);
		
		$this->assertArrayHasKey(basename($this->file) . '.y', $files);
		$this->assertType('Q\Fs_Dir', $files[basename($this->file) . '.y']);
		$this->assertEquals("{$this->file}/" . basename($this->file) . '.y', (string)$files[basename($this->file) . '.y']);

		$this->assertEquals(3, count($files), array_keys($files));
	}
	
	/**
	 * Test traversing through a directory, after deleting files
	 */
	public function testTraverse_Empty()
	{
		$this->cleanup("{$this->file}/" . basename($this->file));
		
		$files = array();
		foreach ($this->Fs_Node as $key=>$file) {
			$files[$key] = $file;
		}
		
		$this->assertEquals(0, count($files), array_keys($files));
	}
	
	/**
	 * Test traversing through a directory, after deleting files
	 */
	public function testTraverse_Again()
	{
		symlink("{$this->file}/" . basename($this->file), "{$this->file}/" . basename($this->file) . '.x');
		mkdir("{$this->file}/" . basename($this->file) . '.y');

		$files = array();
		foreach ($this->Fs_Node as $key=>$file) {
			$files[$key] = $file;
		}

		$this->assertEquals(3, count($files), array_keys($files));
		$this->cleanup("{$this->file}/" . basename($this->file));
		
		$files = array();
		foreach ($this->Fs_Node as $key=>$file) {
			$files[$key] = $file;
		}
		$this->assertEquals(0, count($files), array_keys($files));
	}
	
	/**
	 * Test traversing through a directory 
	 */
	public function testCount()
	{
		symlink("{$this->file}/" . basename($this->file), "{$this->file}/" . basename($this->file) . '.x');
		mkdir("{$this->file}/" . basename($this->file) . '.y');
		
		$this->assertEquals(3, count($this->Fs_Node));
	}

	/**
	 * Test traversing through a directory, after deleting files
	 */
	public function testCount_Empty()
	{
		$this->cleanup("{$this->file}/" . basename($this->file));
		$this->assertEquals(0, count($this->Fs_Node));
	}

	/**
	 * Test traversing through a directory 
	 */
	public function testCount_Again()
	{
		symlink("{$this->file}/" . basename($this->file), "{$this->file}/" . basename($this->file) . '.x');
		mkdir("{$this->file}/" . basename($this->file) . '.y');
		
		$this->assertEquals(3, count($this->Fs_Node));
		
		$this->cleanup("{$this->file}/" . basename($this->file));
		$this->assertEquals(0, count($this->Fs_Node));
	}
}
