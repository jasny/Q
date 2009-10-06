<?php
use Q\Fs, Q\Fs_Node, Q\Fs_Symlink_Broken, Q\Fs_Exception, Q\ExecException;

require_once 'TestHelper.php';
require_once 'Fs/NodeTest.php';
require_once 'Q/Fs/Symlink/Broken.php';

/**
 * Fs_Symlink_Broken test case.
 */
class Fs_Symlink_BrokenTest extends Fs_NodeTest
{
	/**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-fs_symlink_filetest-' . md5(uniqid() );
        if (!symlink($this->file . '.orig', $this->file)) $this->markTestSkipped("Could not create symlink '{$this->file}'.");
    	
        $this->Fs_Node = new Fs_Symlink_Broken($this->file);
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
     * Tests Fs_Node->create()
     */
    public function testCreate()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to create file: Unable to dereference link '{$this->file}'");
    	$this->Fs_Node->create(0660);
    }

    /**
     * Tests Fs_Node->realpath()
     */
    public function testRealpath()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to resolve realpath of '{$this->file}': File is a broken symlink.");
    	$file = $this->Fs_Node->realpath();
    }
    
    /**
     * Tests Fs_Node->stat()
     */
    public function testStat()
    {
        $this->setExpectedException('Q\Fs_Exception', "Failed to stat {$this->file}: stat failed for {$this->file}");
        $this->Fs_Node->stat();
    }

    /**
     * Tests Fs_Node->clearStatCache()
     */
    public function testClearStatCache()
    {
        $this->marktestIncomplete("Find a way to change the simlink stat and not the file stat");
    }

    /**
     * Tests Fs_Node->touch()
     */
    public function testTouch()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to touch file: Unable to dereference link '{$this->file}'");
        $this->Fs_Node->touch(strtotime('2009-01-01 12:00:00'));
    }
    
    /**
     * Tests Fs_Node->touch() with atime
     */
    public function testTouch_atime()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to touch file: Unable to dereference link '{$this->file}'");
        $this->Fs_Node->touch(strtotime('2009-01-01 12:00:00'));
    }

    /**
     * Tests Fs_Node->touch() with DateTime
     */
    public function testTouch_DateTime()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to touch file: Unable to dereference link '{$this->file}'");
        $this->Fs_Node->touch(strtotime('2009-01-01 12:00:00'));
    }

    /**
     * Tests Fs_Node->touch() with strings
     */
    public function testTouch_string()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to touch file: Unable to dereference link '{$this->file}'");
        $this->Fs_Node->touch(strtotime('2009-01-01 12:00:00'));
    }
    
    /**
     * Tests Fs_Node->getAttribute()
     */
    public function testGetAttribute()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get attribute 'dev' of '{$this->file}': File is a broken link");
    	$this->Fs_Node->getAttribute('dev');
    }

    /**
     * Tests Fs_Node->getAttribute() for calculated info
     */
    public function testGetAttribute_Calculated()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get attribute 'mode' of '{$this->file}': File is a broken link");
        $this->Fs_Node->getAttribute('mode');
    }

    /**
     * Tests Fs_Node->getAttribute() getting posix info
     */
    public function testGetAttribute_Posix()
    {        
        $this->setExpectedException('Q\Fs_Exception', "Unable to get attribute 'owner' of '{$this->file}': File is a broken link");
        $this->Fs_Node->getAttribute('owner');
    }

    /**
     * Tests Fs_Node->getAttribute() getting posix info
     * this test is not so good because owner of the symlink is the same as the owner of the file
     */
    public function testGetAttribute_lstat_Posix()
    {        

    	$stat = lstat($this->file);
        
        $userinfo = (extension_loaded('posix') && ($info = posix_getpwuid($stat['uid']))) ? $info['name'] : $stat['uid']; 
        $groupinfo = (extension_loaded('posix') && ($info = posix_getgrgid($stat['gid']))) ? $info['name'] : $stat['gid'];
        
        $this->assertEquals($userinfo, $this->Fs_Node->getAttribute('owner', Fs::NO_DEREFERENCE), 'owner');
        $this->assertEquals($groupinfo, $this->Fs_Node->getAttribute('group', Fs::NO_DEREFERENCE), 'group');
    }
    
    /**
     * Tests Fs_Node->setAttribute()
     */
    public function testSetAttribute()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to change mode of '{$this->file}': Unable to dereference symlink");
    	$this->Fs_Node->setAttribute('mode', 0777);
    }

    /**
     * Tests Fs_Node->offsetGet()
     */
    public function testOffsetGet()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get attribute 'ino' of '{$this->file}': File is a broken link");
    	$this->Fs_Node['ino'];
    }

    /**
     * Tests Fs_Node->offsetSet()
     */
    public function testOffsetSet()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to change mode of '{$this->file}': Unable to dereference symlink");
        $this->Fs_Node['mode'] = 0777;
    }

    /**
     * Tests Fs_Node->diskTotalSpace()
     */
    public function testDiskTotalSpace()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get total disk space of '{$this->file}': File is not a directory");
        $this->Fs_Node->diskTotalSpace();
    }

    /**
     * Tests Fs_Node->diskFreeSpace()
     */
    public function testDiskFreeSpace()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get free disk space of '{$this->file}': File is not a directory");
        $this->Fs_Node->diskFreeSpace();
    }
    
    /**
     * Tests Fs_Node->isDeletable()
     */
    public function testIsDeletable()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get attribute 'uid' of '{$this->file}': File is a broken link");
    	$this->Fs_Node->isDeletable();
    }

    /**
     * Tests Fs_Node::chmod()
     */
    public function testChmod()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to change mode of '{$this->file}': Unable to dereference symlink");
        $this->Fs_Node->chmod(0766);
        exit;
    }
    
    /**
     * Tests Fs_Node::chmod() with string
     */
    public function testChmod_string()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to change mode of '{$this->file}': Unable to dereference symlink");
        $this->Fs_Node->chmod('u=rwx,go+rw-x');
    }
    
    /**
     * Tests Fs_Node::chmod() with invalid string
     */
    public function testChmod_invalidString()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to change mode of '{$this->file}': Unable to dereference symlink");
        $this->Fs_Node->chmod('incorrect mode');
    }
    
    /**
     * Tests Fs_Node::chown() with user:group
     */
    public function testChown_Security()
    {
        $this->setExpectedException('Q\SecurityException', "Won't change owner of '{$this->file}' to user 'myusr:mygrp': To change both owner and group, user array(owner, group) instead");
        $this->Fs_Node->chown('myusr:mygrp');
    }

    /**
     * Tests Fs_Node->exists()
     */
    public function testExists()
    {
        $this->assertFalse($this->Fs_Node->exists());
    }

    /**
     * Tests Fs_Node->isWritable()
     */
    public function testIsWritable()
    {
    	$this->assertTrue($this->Fs_Node->isWritable());
    }
    
    /**
     * Tests Fs_Node->isWritable()
     */
    public function testIsWritable_Fail()
    {
    	if (is_writable('/usr/bin')) $this->markTestSkipped("Can't run this test, because I have write privileges to '/usr/bin'");
    	
    	symlink('/usr/bin/' . basename($this->file), "{$this->file}.x");
    	$file = new Fs_Symlink_Broken("{$this->file}.x");
    	
        $this->assertFalse($file->isWritable());
    }
}
