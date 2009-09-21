<?php
require_once 'Q/Fs/Item.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Fs_Item test case.
 */
abstract class Fs_ItemTest extends PHPUnit_Framework_TestCase
{
    /**
     * File name
     * @var string
     */
    protected $file;

    /**
     * @var Fs_Item
     */
    protected $Fs_Item;
    
	/**
	 * Any temporary files that ware created
	 * @var array
	 */
	protected $tmpfiles = array();
	
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
    }

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		foreach (array_reverse($this->tmpfiles, true) as $file) {
			if (is_link($file)) unlink($file);
			  elseif (is_dir($file)) rmdir($file);
			  elseif (file_exists($file)) unlink($file);
		}
		$this->tmpfiles = array();

        $this->Fs_Item = null;
	}
    
    /**
     * Tests Fs_Item->__toString()
     */
    public function test__toString()
    {
        $this->assertEquals($this->file, (string)$this->Fs_Item);
    }

    /**
     * Tests Fs_Item->path()
     */
    public function testPath()
    {
        $this->assertEquals($this->file, $this->Fs_Item->path());
    }

    /**
     * Tests Fs_Item->basename()
     */
    public function testBasename()
    {
        $this->assertEquals(basename($this->file), $this->Fs_Item->basename());
    }

    /**
     * Tests Fs_Item->extenstion()
     */
    public function testExtenstion()
    {
        $this->Fs_Item->extenstion(pathinfo($this->file, PATHINFO_EXTENSION), $this->Fs_Item->extension());
    }

    /**
     * Tests Fs_Item->filename()
     */
    public function testFilename()
    {
        $this->Fs_Item->extenstion(pathinfo($this->file, PATHINFO_FILENAME), $this->Fs_Item->filename());
	}

    /**
     * Tests Fs_Item->realpath()
     */
    public function testRealpath()
    {
        $this->assertEquals(realpath($this->file), $this->Fs_Item->realpath());
    }

    /**
     * Tests Fs_Item->up()
     */
    public function testUp()
    {
        $dir = $this->Fs_Item->up();
        $this->assertType('Fs_Dir', $dir);
        $this->assertEquals(dirname($this->file), (string)$dir);
        
        $this->assertEquals($dir, $this->Fs_Item->dirname());
    }
	
    
    /**
     * Tests Fs_Item->stat()
     */
    public function testStat()
    {
        $this->assertEquals(stat($this->file), $this->Fs_Item->stat());
        $this->assertEquals(lstat($this->file), $this->Fs_Item->stat(Fs::DONTFOLLOW));
    }
    
    /**
     * Tests Fs_Item->clearStatCache()
     */
    public function testClearStatCache()
    {
    	$stat = stat($this->file);
    	if (!@touch($this->file)) $this->markTestSkipped("Could not touch {$this->file}.");
    	if (!$stat == stat($this->file)) $this->markTestSkipped("Stats don't appear to be cached.");
    	
        $this->Fs_Item->clearStatCache();
        $this->assertNotEquals($stat, stat($this->file));
    }

    /**
     * Tests Fs_Item->touch()
     */
    public function testTouch()
    {
    	clearstatcache(false, $this->file);
    	$this->Fs_Item->touch(strtotime('2009-01-01 12:00'), strtotime('2009-01-01 18:00'));
    	$this->assertEquals(strtotime('2009-01-01 12:00'), filectime($this->file));
    	$this->assertEquals(strtotime('2009-01-01 18:00'), fileatime($this->file));
    }

    /**
     * Tests Fs_Item->touch() with DateTime
     */
    public function testTouch_DateTime()
    {
    	if (!class_exists('DateTime')) $this->markTestSkipped("DateTime is not available.");
    	
    	clearstatcache(false, $this->file);
    	$this->Fs_Item->touch(new DateTime('2009-01-01 12:00'), new DateTime('2009-01-01 18:00'));
    	$this->assertEquals(strtotime('2009-01-01 12:00'), filectime($this->file));
    	$this->assertEquals(strtotime('2009-01-01 18:00'), fileatime($this->file));
    }
    
    /**
     * Tests Fs_Item->getAttribute()
     */
    public function testGetAttribute()
    {
    	$stat = stat($this->file);
        $this->assertEquals($stat['dev'], $this->Fs_Item->getAttribute('dev'), 'dev');
        $this->assertEquals($stat['ino'], $this->Fs_Item->getAttribute('ino'), 'ino');
        $this->assertEquals($stat['mode'], $this->Fs_Item->getAttribute('mode'), 'mode');
        $this->assertEquals($stat['nlink'], $this->Fs_Item->getAttribute('nlink'), 'nlink');
        $this->assertEquals($stat['uid'], $this->Fs_Item->getAttribute('uid'), 'uid');
        $this->assertEquals($stat['gid'], $this->Fs_Item->getAttribute('gid'), 'gid');
        $this->assertEquals($stat['rdev'], $this->Fs_Item->getAttribute('rdev'), 'rdev');
        $this->assertEquals($stat['size'], $this->Fs_Item->getAttribute('size'), 'size');
        $this->assertEquals($stat['atime'], $this->Fs_Item->getAttribute('atime'), 'atime');
        $this->assertEquals($stat['mtime'], $this->Fs_Item->getAttribute('mtime'), 'mtime');
        $this->assertEquals($stat['ctime'], $this->Fs_Item->getAttribute('ctime'), 'ctime');
        $this->assertEquals($stat['blksize'], $this->Fs_Item->getAttribute('blksize'), 'blksize');
        $this->assertEquals($stat['blocks'], $this->Fs_Item->getAttribute('blocks'), 'blocks');
    }

    /**
     * Tests Fs_Item->setAttribute()
     */
    public function testSetAttribute()
    {
    	clearstatcache(false, $this->file);
    	$this;
	}

    /**
     * Tests Fs_Item->offsetExists()
     */
    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->Fs_Item['dev']), 'dev');
        $this->assertTrue(isset($this->Fs_Item['ino']), 'ino');
        $this->assertTrue(isset($this->Fs_Item['mode']), 'mode');
        $this->assertTrue(isset($this->Fs_Item['nlink']), 'nlink');
        $this->assertTrue(isset($this->Fs_Item['uid']), 'uid');
        $this->assertTrue(isset($this->Fs_Item['gid']), 'gid');
        $this->assertTrue(isset($this->Fs_Item['rdev']), 'rdev');
        $this->assertTrue(isset($this->Fs_Item['size']), 'size');
        $this->assertTrue(isset($this->Fs_Item['atime']), 'atime');
        $this->assertTrue(isset($this->Fs_Item['mtime']), 'mtime');
        $this->assertTrue(isset($this->Fs_Item['ctime']), 'ctime');
        $this->assertTrue(isset($this->Fs_Item['blksize']), 'blksize');
        $this->assertTrue(isset($this->Fs_Item['blocks']), 'blocks');
        
        $this->assertFalse(isset($this->Fs_Item['nonexistent_' . md5(uniqid())]), 'nonexistent_MD5');
    }

    /**
     * Tests Fs_Item->offsetGet()
     */
    public function testOffsetGet()
    {
    	$stat = stat($this->file);
        $this->assertEquals($stat['dev'], $this->Fs_Item['dev'], 'dev');
        $this->assertEquals($stat['ino'], $this->Fs_Item['ino'], 'ino');
        $this->assertEquals($stat['mode'], $this->Fs_Item['mode'], 'mode');
        $this->assertEquals($stat['nlink'], $this->Fs_Item['nlink'], 'nlink');
        $this->assertEquals($stat['uid'], $this->Fs_Item['uid'], 'uid');
        $this->assertEquals($stat['gid'], $this->Fs_Item['gid'], 'gid');
        $this->assertEquals($stat['rdev'], $this->Fs_Item['rdev'], 'rdev');
        $this->assertEquals($stat['size'], $this->Fs_Item['size'], 'size');
        $this->assertEquals($stat['atime'], $this->Fs_Item['atime'], 'atime');
        $this->assertEquals($stat['mtime'], $this->Fs_Item['mtime'], 'mtime');
        $this->assertEquals($stat['ctime'], $this->Fs_Item['ctime'], 'ctime');
        $this->assertEquals($stat['blksize'], $this->Fs_Item['blksize'], 'blksize');
        $this->assertEquals($stat['blocks'], $this->Fs_Item['blocks'], 'blocks');
	}
	
    /**
     * Tests Fs_Item->offsetSet()
     */
    public function testOffsetSet()
    {
        // TODO Auto-generated Fs_ItemTest->testOffsetSet()
        $this->markTestIncomplete("offsetSet test not implemented");
        $this->Fs_Item->offsetSet(/* parameters */);
    }

    /**
     * Tests Fs_Item->offsetUnset()
     */
    public function testOffsetUnset()
    {
        // TODO Auto-generated Fs_ItemTest->testOffsetUnset()
        $this->markTestIncomplete("offsetUnset test not implemented");
        $this->Fs_Item->offsetUnset(/* parameters */);
    }

    /**
     * Tests Fs_Item->exists()
     */
    public function testExists()
    {
        // TODO Auto-generated Fs_ItemTest->testExists()
        $this->markTestIncomplete("exists test not implemented");
        $this->Fs_Item->exists(/* parameters */);
    }

    /**
     * Tests Fs_Item->isExecutable()
     */
    public function testIsExecutable()
    {
        // TODO Auto-generated Fs_ItemTest->testIsExecutable()
        $this->markTestIncomplete("isExecutable test not implemented");
        $this->Fs_Item->isExecutable(/* parameters */);
    }

    /**
     * Tests Fs_Item->isReadable()
     */
    public function testIsReadable()
    {
        // TODO Auto-generated Fs_ItemTest->testIsReadable()
        $this->markTestIncomplete("isReadable test not implemented");
        $this->Fs_Item->isReadable(/* parameters */);
    }

    /**
     * Tests Fs_Item->isWritable()
     */
    public function testIsWritable()
    {
        // TODO Auto-generated Fs_ItemTest->testIsWritable()
        $this->markTestIncomplete("isWritable test not implemented");
        $this->Fs_Item->isWritable(/* parameters */);
    }

    /**
     * Tests Fs_Item->isCreatable()
     */
    public function testIsCreatable()
    {
        // TODO Auto-generated Fs_ItemTest->testIsCreatable()
        $this->markTestIncomplete("isCreatable test not implemented");
        $this->Fs_Item->isCreatable(/* parameters */);
    }

    /**
     * Tests Fs_Item->isDeletable()
     */
    public function testIsDeletable()
    {
        // TODO Auto-generated Fs_ItemTest->testIsDeletable()
        $this->markTestIncomplete("isDeletable test not implemented");
        $this->Fs_Item->isDeletable(/* parameters */);
    }

    /**
     * Tests Fs_Item->isHidden()
     */
    public function testIsHidden()
    {
        // TODO Auto-generated Fs_ItemTest->testIsHidden()
        $this->markTestIncomplete("isHidden test not implemented");
        $this->Fs_Item->isHidden(/* parameters */);
    }

    /**
     * Tests Fs_Item::chmod()
     */
    public function testChmod()
    {
        // TODO Auto-generated Fs_ItemTest::testChmod()
        $this->markTestIncomplete("chmod test not implemented");
        Fs_Item::chmod(/* parameters */);
    }

    /**
     * Tests Fs_Item::chown()
     */
    public function testChown()
    {
        // TODO Auto-generated Fs_ItemTest::testChown()
        $this->markTestIncomplete("chown test not implemented");
        Fs_Item::chown(/* parameters */);
    }

    /**
     * Tests Fs_Item::chgrp()
     */
    public function testChgrp()
    {
        // TODO Auto-generated Fs_ItemTest::testChgrp()
        $this->markTestIncomplete("chgrp test not implemented");
        Fs_Item::chgrp(/* parameters */);
    }

    /**
     * Tests Fs_Item->copy()
     */
    public function testCopy()
    {
        // TODO Auto-generated Fs_ItemTest->testCopy()
        $this->markTestIncomplete("copy test not implemented");
        $this->Fs_Item->copy(/* parameters */);
    }

    /**
     * Tests Fs_Item->rename()
     */
    public function testRename()
    {
        // TODO Auto-generated Fs_ItemTest->testRename()
        $this->markTestIncomplete("rename test not implemented");
        $this->Fs_Item->rename(/* parameters */);
    }

    /**
     * Tests Fs_Item->move()
     */
    public function testMove()
    {
        // TODO Auto-generated Fs_ItemTest->testMove()
        $this->markTestIncomplete("move test not implemented");
        $this->Fs_Item->move(/* parameters */);
    }

    /**
     * Tests Fs_Item->delete()
     */
    public function testDelete()
    {
        // TODO Auto-generated Fs_ItemTest->testDelete()
        $this->markTestIncomplete("delete test not implemented");
        $this->Fs_Item->delete(/* parameters */);
    }

    /**
     * Tests Fs_Item->unlink()
     */
    public function testUnlink()
    {
        // TODO Auto-generated Fs_ItemTest->testUnlink()
        $this->markTestIncomplete("unlink test not implemented");
        $this->Fs_Item->unlink(/* parameters */);
    }

    /**
     * Tests Fs_Item->__invoke()
     */
    public function test__invoke()
    {
        // TODO Auto-generated Fs_ItemTest->test__invoke()
        $this->markTestIncomplete("__invoke test not implemented");
        $this->Fs_Item->__invoke(/* parameters */);
    }

    /**
     * Tests Fs_Item::__set_state()
     */
    public function test__set_state()
    {
        // TODO Auto-generated Fs_ItemTest::test__set_state()
        $this->markTestIncomplete("__set_state test not implemented");
        Fs_Item::__set_state(/* parameters */);
    }
}

