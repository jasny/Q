<?php
use Q\Fs, Q\Fs_Node;

require_once 'TestHelper.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Fs_Node test case.
 */
abstract class Fs_NodeTest extends PHPUnit_Framework_TestCase
{
    /**
     * File name
     * @var string
     */
    protected $file;

    /**
     * @var Fs_Node
     */
    protected $Fs_Node;
    
	
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
     * Tests Fs_Node->__toString()
     */
    public function test__toString()
    {
        $this->assertEquals($this->file, (string)$this->Fs_Node);
    }

    /**
     * Tests Fs_Node->path()
     */
    public function testPath()
    {
        $this->assertEquals($this->file, $this->Fs_Node->path());
    }

    /**
     * Tests Fs_Node->basename()
     */
    public function testBasename()
    {
        $this->assertEquals(basename($this->file), $this->Fs_Node->basename());
    }

    /**
     * Tests Fs_Node->extenstion()
     */
    public function testExtension()
    {
        $this->assertEquals(pathinfo($this->file, PATHINFO_EXTENSION), $this->Fs_Node->extension());
    }

    /**
     * Tests Fs_Node->filename()
     */
    public function testFilename()
    {
        $this->assertEquals(pathinfo($this->file, PATHINFO_FILENAME), $this->Fs_Node->filename());
	}

    /**
     * Tests Fs_Node->realpath()
     */
    public function testRealpath()
    {
    	$file = $this->Fs_Node->realpath();
        $this->assertType(preg_replace('/Symlink_/', '', get_class($this->Fs_Node)), $file);
        $this->assertEquals(realpath($this->file), (string)$file);
    }

    /**
     * Tests Fs_Node->up()
     */
    public function testUp()
    {
        $dir = $this->Fs_Node->up();
        $this->assertType('Q\Fs_Dir', $dir);
        $this->assertEquals(dirname($this->file), (string)$dir);
        
        $this->assertEquals($dir, $this->Fs_Node->dirname());
    }
	
    
    /**
     * Tests Fs_Node->stat()
     */
    public function testStat()
    {
    	$stat = stat($this->file);

        $stat['type'] = filetype(realpath($this->file));
        $stat['perms'] = Fs::mode2perms($stat['mode']);
    	
    	if (extension_loaded('posix')) {
	    	$userinfo = posix_getpwuid($stat['uid']);
	    	$groupinfo = posix_getgrgid($stat['gid']);
	    	$stat['owner'] = $userinfo['name'];
	    	$stat['group'] = $groupinfo['name'];
    	} else {
	    	$stat['owner'] = $stat['uid'];
	    	$stat['group'] = $stat['gid'];
    	}
    	
        $this->assertEquals($stat, $this->Fs_Node->stat());
    }

    /**
     * Tests Fs_Node->stat() as lstat
     */
    public function testStat_lstat()
    {
    	$stat = lstat($this->file);
		
        $stat['type'] = filetype($this->file);
        $stat['perms'] = Fs::mode2perms($stat['mode']);
    	
    	if (extension_loaded('posix')) {
	    	$userinfo = posix_getpwuid($stat['uid']);
	    	$groupinfo = posix_getgrgid($stat['gid']);
	    	$stat['owner'] = $userinfo['name'];
	    	$stat['group'] = $groupinfo['name'];
    	} else {
	    	$stat['owner'] = $stat['uid'];
	    	$stat['group'] = $stat['gid'];
    	}
    	
        $this->assertEquals($stat, $this->Fs_Node->stat(Fs::NO_DEREFERENCE));
    }
    
    /**
     * Tests Fs_Node->clearStatCache()
     */
    public function testClearStatCache()
    {
    	$mtime = filemtime($this->file);
    	if (!@touch($this->file, strtotime('2009-01-01 12:00:00'))) $this->markTestSkipped("Could not touch {$this->file}.");
    	if (!$mtime == filemtime($this->file)) $this->markTestSkipped("Stats don't appear to be cached.");
    	
        $this->Fs_Node->clearStatCache();
        $this->assertNotEquals($mtime, filemtime($this->file));
    }

    /**
     * Tests Fs_Node->touch()
     */
    public function testTouch()
    {
    	filemtime($this->file); // Stat cache 
    	
    	$this->Fs_Node->touch(strtotime('2009-01-01 12:00:00'));
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', filemtime($this->file)));
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', fileatime($this->file)));
    }
    
    /**
     * Tests Fs_Node->touch() with atime
     */
    public function testTouch_atime()
    {
    	filemtime($this->file); // Stat cache
    	
    	$this->Fs_Node->touch(strtotime('2009-01-01 12:00:00'), strtotime('2009-01-01 18:00:00'));
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', filemtime($this->file)));
    	$this->assertEquals('2009-01-01 18:00:00', date('Y-m-d H:i:s', fileatime($this->file)));
    }

    /**
     * Tests Fs_Node->touch() with DateTime
     */
    public function testTouch_DateTime()
    {
    	if (!class_exists('DateTime')) $this->markTestSkipped("DateTime is not available.");
    	filemtime($this->file); // Stat cache
    	
    	$this->Fs_Node->touch(new DateTime('2009-01-01 12:00:00'), new DateTime('2009-01-01 18:00:00'));
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', filemtime($this->file)));
    	$this->assertEquals('2009-01-01 18:00:00', date('Y-m-d H:i:s', fileatime($this->file)));
    }

    /**
     * Tests Fs_Node->touch() with strings
     */
    public function testTouch_string()
    {
    	filemtime($this->file); // Stat cache
    	
    	$this->Fs_Node->touch('2009-01-01 12:00:00', '2009-01-01 18:00:00');
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', filemtime($this->file)));
    	$this->assertEquals('2009-01-01 18:00:00', date('Y-m-d H:i:s', fileatime($this->file)));
    }
    
    
    /**
     * Tests Fs_Node->getAttribute()
     */
    public function testGetAttribute()
    {
    	$stat = stat($this->file);
        $this->assertEquals($stat['dev'], $this->Fs_Node->getAttribute('dev'), 'dev');
        $this->assertEquals($stat['ino'], $this->Fs_Node->getAttribute('ino'), 'ino');
        $this->assertEquals($stat['mode'], $this->Fs_Node->getAttribute('mode'), 'mode');
        $this->assertEquals($stat['nlink'], $this->Fs_Node->getAttribute('nlink'), 'nlink');
        $this->assertEquals($stat['uid'], $this->Fs_Node->getAttribute('uid'), 'uid');
        $this->assertEquals($stat['gid'], $this->Fs_Node->getAttribute('gid'), 'gid');
        $this->assertEquals($stat['rdev'], $this->Fs_Node->getAttribute('rdev'), 'rdev');
        $this->assertEquals($stat['size'], $this->Fs_Node->getAttribute('size'), 'size');
        $this->assertEquals($stat['atime'], $this->Fs_Node->getAttribute('atime'), 'atime');
        $this->assertEquals($stat['mtime'], $this->Fs_Node->getAttribute('mtime'), 'mtime');
        $this->assertEquals($stat['ctime'], $this->Fs_Node->getAttribute('ctime'), 'ctime');
        $this->assertEquals($stat['blksize'], $this->Fs_Node->getAttribute('blksize'), 'blksize');
        $this->assertEquals($stat['blocks'], $this->Fs_Node->getAttribute('blocks'), 'blocks');
    }

    /**
     * Tests Fs_Node->getAttribute() for calculated info
     */
    public function testGetAttribute_Calculated()
    {
    	$mode = fileperms(realpath($this->file));
    	
        $this->assertEquals($mode, $this->Fs_Node->getAttribute('mode'), 'mode = fileperms');
        $this->assertEquals(filetype(realpath($this->file)), $this->Fs_Node->getAttribute('type'), 'type');
        $this->assertEquals(Fs::mode2perms($mode), $this->Fs_Node->getAttribute('perms'), 'perms');
    }
    
    /**
     * Tests Fs_Node->getAttribute() getting posix info
     */
    public function testGetAttribute_Posix()
    {
    	if (!extension_loaded('posix')) $this->markTestSkipped("Posix methods not available.");
    	
    	$userinfo = posix_getpwuid(fileowner($this->file));
    	$groupinfo = posix_getgrgid(filegroup($this->file));
    	
    	$this->assertEquals($userinfo['name'], $this->Fs_Node->getAttribute('owner'), 'owner');
        $this->assertEquals($groupinfo['name'], $this->Fs_Node->getAttribute('group'), 'group');
    }

    /**
     * Tests Fs_Node->getAttribute()
     */
    public function testGetAttribute_lstat()
    {
    	$stat = lstat($this->file);
        $this->assertEquals($stat['dev'], $this->Fs_Node->getAttribute('dev', Fs::NO_DEREFERENCE), 'dev');
        $this->assertEquals($stat['ino'], $this->Fs_Node->getAttribute('ino', Fs::NO_DEREFERENCE), 'ino');
        $this->assertEquals($stat['mode'], $this->Fs_Node->getAttribute('mode', Fs::NO_DEREFERENCE), 'mode');
        $this->assertEquals($stat['nlink'], $this->Fs_Node->getAttribute('nlink', Fs::NO_DEREFERENCE), 'nlink');
        $this->assertEquals($stat['uid'], $this->Fs_Node->getAttribute('uid', Fs::NO_DEREFERENCE), 'uid');
        $this->assertEquals($stat['gid'], $this->Fs_Node->getAttribute('gid', Fs::NO_DEREFERENCE), 'gid');
        $this->assertEquals($stat['rdev'], $this->Fs_Node->getAttribute('rdev', Fs::NO_DEREFERENCE), 'rdev');
        $this->assertEquals($stat['size'], $this->Fs_Node->getAttribute('size', Fs::NO_DEREFERENCE, Fs::NO_DEREFERENCE), 'size');
        $this->assertEquals($stat['atime'], $this->Fs_Node->getAttribute('atime', Fs::NO_DEREFERENCE), 'atime');
        $this->assertEquals($stat['mtime'], $this->Fs_Node->getAttribute('mtime', Fs::NO_DEREFERENCE), 'mtime');
        $this->assertEquals($stat['ctime'], $this->Fs_Node->getAttribute('ctime', Fs::NO_DEREFERENCE), 'ctime');
        $this->assertEquals($stat['blksize'], $this->Fs_Node->getAttribute('blksize', Fs::NO_DEREFERENCE), 'blksize');
        $this->assertEquals($stat['blocks'], $this->Fs_Node->getAttribute('blocks', Fs::NO_DEREFERENCE), 'blocks');
    }

    /**
     * Tests Fs_Node->getAttribute() for calculated info
     */
    public function testGetAttribute_lstat_Calculated()
    {
    	$mode = fileperms($this->file);
    	
        $this->assertEquals($mode, $this->Fs_Node->getAttribute('mode', Fs::NO_DEREFERENCE), 'mode = fileperms');
        $this->assertEquals(filetype(realpath($this->file)), $this->Fs_Node->getAttribute('type', Fs::NO_DEREFERENCE), 'type');
        $this->assertEquals(Fs::mode2perms($mode), $this->Fs_Node->getAttribute('perms', Fs::NO_DEREFERENCE), 'perms');
    }
    
    /**
     * Tests Fs_Node->getAttribute() getting posix info
     */
    public function testGetAttribute_lstat_Posix()
    {
    	if (!extension_loaded('posix')) $this->markTestSkipped("Posix methods not available.");
    	
    	$userinfo = posix_getpwuid(fileowner($this->file));
    	$groupinfo = posix_getgrgid(filegroup($this->file));
    	
    	$this->assertEquals($userinfo['name'], $this->Fs_Node->getAttribute('owner'), 'owner');
        $this->assertEquals($groupinfo['name'], $this->Fs_Node->getAttribute('group'), 'group');
    }
    
    /**
     * Tests Fs_Node->setAttribute()
     */
    public function testSetAttribute()
    {
    	fileperms($this->file); // Stat cache
    	$this->Fs_Node->setAttribute('mode', 0777);
    	$this->assertEquals('0777', sprintf('%04o', fileperms($this->file) & 07777));
	}

    /**
     * Tests Fs_Node->setAttribute() with an attribute that can't be set
     */
    public function testSetAttribute_Exception()
    {
    	$this->setExpectedException('Exception', "Unable to set attribute 'type'; Attribute is read-only.");
    	$this->Fs_Node['type'] = 'unknown';
    }
	
    /**
     * Tests Fs_Node->offsetExists()
     */
    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->Fs_Node['ino']), 'ino');
        $this->assertTrue(isset($this->Fs_Node['mtime']), 'mtime');
        $this->assertTrue(isset($this->Fs_Node['type']), 'type');
        $this->assertFalse(isset($this->Fs_Node['nonexistent_' . md5(uniqid())]), 'nonexistent_MD5');
    }

    /**
     * Tests Fs_Node->offsetGet()
     */
    public function testOffsetGet()
    {
    	$stat = stat($this->file);
        $this->assertEquals($stat['ino'], $this->Fs_Node['ino'], 'ino');
        $this->assertEquals($stat['mtime'], $this->Fs_Node['mtime'], 'mtime');
        $this->assertEquals(filetype($this->file), $this->Fs_Node['type'], 'type');
    }
	
    /**
     * Tests Fs_Node->offsetSet()
     */
    public function testOffsetSet()
    {
    	fileperms($this->file); // Stat cache
    	$this->Fs_Node['mode'] = 0777;
    	$this->assertEquals('0777', sprintf('%04o', fileperms($this->file) & 0777));
	}

    /**
     * Tests Fs_Node->offsetSet() with an attribute that can't be set
     */
    public function testOffsetSet_Exception()
    {
    	$this->setExpectedException('Exception', "Unable to set attribute 'type'; Attribute is read-only.");
    	$this->Fs_Node['type'] = 'unknown';
    }
	
    /**
     * Tests Fs_Node->offsetUnset() with an attribute that can't be unset
     */
    public function testOffsetUnset_Exception()
    {
    	$this->setExpectedException('Exception', "Unable to set attribute 'mode' to null.");
    	unset($this->Fs_Node['mode']);
    }

    /**
     * Tests Fs_Node->exists()
     */
    public function testExists()
    {
        $this->assertTrue($this->Fs_Node->exists());
    }

    /**
     * Tests Fs_Node->isExecutable()
     */
    public function testIsExecutable()
    {
        $this->assertEquals(is_executable($this->file), $this->Fs_Node->isExecutable());
    }

    /**
     * Tests Fs_Node->isReadable()
     */
    public function testIsReadable()
    {
        $this->assertEquals(is_readable($this->file), $this->Fs_Node->isReadable());
	}

    /**
     * Tests Fs_Node->isWritable()
     */
    public function testIsWritable()
    {
        $this->assertEquals(is_writable($this->file), $this->Fs_Node->isWritable());
    }

    /**
     * Tests Fs_Node->isDeletable()
     */
    public function testIsDeletable()
    {
        $this->assertEquals(is_writable($this->file) || is_writable(dirname($this->file)), $this->Fs_Node->isDeletable());
    }

    /**
     * Tests Fs_Node->isHidden()
     */
    public function testIsHidden()
    {
        $this->assertFalse($this->Fs_Node->isHidden());
    }

    /**
     * Tests Fs_Node::chmod()
     */
    public function testChmod()
    {
    	fileperms($this->file); // Stat cache
    	
        $this->Fs_Node->chmod(0766);
        $this->assertEquals('0766', sprintf('%04o', fileperms($this->file) & 07777));

        $this->Fs_Node->chmod(0740);
        $this->assertEquals('0740', sprintf('%04o', fileperms($this->file) & 07777), '2nd time');
    }

    /**
     * Tests Fs_Node::chmod() with string
     */
    public function testChmod_string()
    {
    	fileperms($this->file); // Stat cache
    	
        $this->Fs_Node->chmod('u=rwx,go+rw-x');
        $this->assertEquals('0766', sprintf('%04o', fileperms($this->file) & 07777));
        
        $this->Fs_Node->chmod('g-w,o-rwx');
        $this->assertEquals('0740', sprintf('%04o', fileperms($this->file) & 07777), '2nd time');
    }
	
    /**
     * Tests Fs_Node::chmod() with invalid string
     */
    public function testChmod_invalidString()
    {
    	$this->setExpectedException('Q\ExecException');
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
     * Tests Fs_Node->__invoke()
     */
    public function test__invoke()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': This is not a regular file, but a " . filetype(realpath($this->file)));
        $this->Fs_Node();
    }

    /**
     * Tests Fs_Node::__set_state()
     */
    public function test__set_state()
    {
        $ser = var_export($this->Fs_Node, true);
        $this->assertEquals($this->Fs_Node, eval("return $ser;"));
    }
}
