<?php
use Q\Fs, Q\Fs_Item;

require_once 'Q/Fs.php';
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
    public function testExtension()
    {
        $this->assertEquals(pathinfo($this->file, PATHINFO_EXTENSION), $this->Fs_Item->extension());
    }

    /**
     * Tests Fs_Item->filename()
     */
    public function testFilename()
    {
        $this->assertEquals(pathinfo($this->file, PATHINFO_FILENAME), $this->Fs_Item->filename());
	}

    /**
     * Tests Fs_Item->realpath()
     */
    public function testRealpath()
    {
    	$file = $this->Fs_Item->realpath();
        $this->assertType(preg_replace('/Symlink_/', '', get_class($this->Fs_Item)), $file);
        $this->assertEquals(realpath($this->file), (string)$file);
    }

    /**
     * Tests Fs_Item->up()
     */
    public function testUp()
    {
        $dir = $this->Fs_Item->up();
        $this->assertType('Q\Fs_Dir', $dir);
        $this->assertEquals(dirname($this->file), (string)$dir);
        
        $this->assertEquals($dir, $this->Fs_Item->dirname());
    }
	
    
    /**
     * Tests Fs_Item->stat()
     */
    public function testStat()
    {
    	$stat = stat($this->file);

        $stat['type'] = filetype(realpath($this->file));
        $stat['perms'] = Fs::mode2perms($stat['mode']);
        $stat['umask'] = ~$stat['mode'] & 0777;
    	
    	if (extension_loaded('posix')) {
	    	$userinfo = posix_getpwuid($stat['uid']);
	    	$groupinfo = posix_getgrgid($stat['gid']);
	    	$stat['owner'] = $userinfo['name'];
	    	$stat['group'] = $groupinfo['name'];
    	} else {
	    	$stat['owner'] = $stat['uid'];
	    	$stat['group'] = $stat['gid'];
    	}
    	
        $this->assertEquals($stat, $this->Fs_Item->stat());
    }

    /**
     * Tests Fs_Item->stat() as lstat
     */
    public function testStat_lstat()
    {
    	$stat = lstat($this->file);
		
        $stat['type'] = filetype($this->file);
        $stat['perms'] = Fs::mode2perms($stat['mode']);
        $stat['umask'] = ~$stat['mode'] & 0777;
    	
    	if (extension_loaded('posix')) {
	    	$userinfo = posix_getpwuid($stat['uid']);
	    	$groupinfo = posix_getgrgid($stat['gid']);
	    	$stat['owner'] = $userinfo['name'];
	    	$stat['group'] = $groupinfo['name'];
    	} else {
	    	$stat['owner'] = $stat['uid'];
	    	$stat['group'] = $stat['gid'];
    	}
    	
        $this->assertEquals($stat, $this->Fs_Item->stat(Fs::NO_DEREFERENCE));
    }
    
    /**
     * Tests Fs_Item->clearStatCache()
     */
    public function testClearStatCache()
    {
    	$mtime = filemtime($this->file);
    	if (!@touch($this->file, strtotime('2009-01-01 12:00:00'))) $this->markTestSkipped("Could not touch {$this->file}.");
    	if (!$mtime == filemtime($this->file)) $this->markTestSkipped("Stats don't appear to be cached.");
    	
        $this->Fs_Item->clearStatCache();
        $this->assertNotEquals($mtime, filemtime($this->file));
    }

    /**
     * Tests Fs_Item->touch()
     */
    public function testTouch()
    {
    	filemtime($this->file); // Stat cache 
    	
    	$this->Fs_Item->touch(strtotime('2009-01-01 12:00:00'));
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', filemtime($this->file)));
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', fileatime($this->file)));
    }
    
    /**
     * Tests Fs_Item->touch() with atime
     */
    public function testTouch_atime()
    {
    	filemtime($this->file); // Stat cache
    	
    	$this->Fs_Item->touch(strtotime('2009-01-01 12:00:00'), strtotime('2009-01-01 18:00:00'));
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', filemtime($this->file)));
    	$this->assertEquals('2009-01-01 18:00:00', date('Y-m-d H:i:s', fileatime($this->file)));
    }

    /**
     * Tests Fs_Item->touch() with DateTime
     */
    public function testTouch_DateTime()
    {
    	if (!class_exists('DateTime')) $this->markTestSkipped("DateTime is not available.");
    	filemtime($this->file); // Stat cache
    	
    	$this->Fs_Item->touch(new DateTime('2009-01-01 12:00:00'), new DateTime('2009-01-01 18:00:00'));
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', filemtime($this->file)));
    	$this->assertEquals('2009-01-01 18:00:00', date('Y-m-d H:i:s', fileatime($this->file)));
    }

    /**
     * Tests Fs_Item->touch() with strings
     */
    public function testTouch_string()
    {
    	filemtime($this->file); // Stat cache
    	
    	$this->Fs_Item->touch('2009-01-01 12:00:00', '2009-01-01 18:00:00');
    	$this->assertEquals('2009-01-01 12:00:00', date('Y-m-d H:i:s', filemtime($this->file)));
    	$this->assertEquals('2009-01-01 18:00:00', date('Y-m-d H:i:s', fileatime($this->file)));
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
     * Tests Fs_Item->getAttribute() for calculated info
     */
    public function testGetAttribute_Calculated()
    {
    	$mode = fileperms(realpath($this->file));
    	
        $this->assertEquals($mode, $this->Fs_Item->getAttribute('mode'), 'mode = fileperms');
        $this->assertEquals(filetype(realpath($this->file)), $this->Fs_Item->getAttribute('type'), 'type');
        $this->assertEquals(Fs::mode2perms($mode), $this->Fs_Item->getAttribute('perms'), 'perms');
        $this->assertEquals(~$mode & 0777, $this->Fs_Item->getAttribute('umask'), 'umask');
    }
    
    /**
     * Tests Fs_Item->getAttribute() getting posix info
     */
    public function testGetAttribute_Posix()
    {
    	if (!extension_loaded('posix')) $this->markTestSkipped("Posix methods not available.");
    	
    	$userinfo = posix_getpwuid(fileowner($this->file));
    	$groupinfo = posix_getgrgid(filegroup($this->file));
    	
    	$this->assertEquals($userinfo['name'], $this->Fs_Item->getAttribute('owner'), 'owner');
        $this->assertEquals($groupinfo['name'], $this->Fs_Item->getAttribute('group'), 'group');
    }

    /**
     * Tests Fs_Item->getAttribute()
     */
    public function testGetAttribute_lstat()
    {
    	$stat = lstat($this->file);
        $this->assertEquals($stat['dev'], $this->Fs_Item->getAttribute('dev', Fs::NO_DEREFERENCE), 'dev');
        $this->assertEquals($stat['ino'], $this->Fs_Item->getAttribute('ino', Fs::NO_DEREFERENCE), 'ino');
        $this->assertEquals($stat['mode'], $this->Fs_Item->getAttribute('mode', Fs::NO_DEREFERENCE), 'mode');
        $this->assertEquals($stat['nlink'], $this->Fs_Item->getAttribute('nlink', Fs::NO_DEREFERENCE), 'nlink');
        $this->assertEquals($stat['uid'], $this->Fs_Item->getAttribute('uid', Fs::NO_DEREFERENCE), 'uid');
        $this->assertEquals($stat['gid'], $this->Fs_Item->getAttribute('gid', Fs::NO_DEREFERENCE), 'gid');
        $this->assertEquals($stat['rdev'], $this->Fs_Item->getAttribute('rdev', Fs::NO_DEREFERENCE), 'rdev');
        $this->assertEquals($stat['size'], $this->Fs_Item->getAttribute('size', Fs::NO_DEREFERENCE, Fs::NO_DEREFERENCE), 'size');
        $this->assertEquals($stat['atime'], $this->Fs_Item->getAttribute('atime', Fs::NO_DEREFERENCE), 'atime');
        $this->assertEquals($stat['mtime'], $this->Fs_Item->getAttribute('mtime', Fs::NO_DEREFERENCE), 'mtime');
        $this->assertEquals($stat['ctime'], $this->Fs_Item->getAttribute('ctime', Fs::NO_DEREFERENCE), 'ctime');
        $this->assertEquals($stat['blksize'], $this->Fs_Item->getAttribute('blksize', Fs::NO_DEREFERENCE), 'blksize');
        $this->assertEquals($stat['blocks'], $this->Fs_Item->getAttribute('blocks', Fs::NO_DEREFERENCE), 'blocks');
    }

    /**
     * Tests Fs_Item->getAttribute() for calculated info
     */
    public function testGetAttribute_lstat_Calculated()
    {
    	$mode = fileperms($this->file);
    	
        $this->assertEquals($mode, $this->Fs_Item->getAttribute('mode', Fs::NO_DEREFERENCE), 'mode = fileperms');
        $this->assertEquals(filetype(realpath($this->file)), $this->Fs_Item->getAttribute('type', Fs::NO_DEREFERENCE), 'type');
        $this->assertEquals(Fs::mode2perms($mode), $this->Fs_Item->getAttribute('perms', Fs::NO_DEREFERENCE), 'perms');
        $this->assertEquals(~$mode & 0777, $this->Fs_Item->getAttribute('umask', Fs::NO_DEREFERENCE), 'umask');
    }
    
    /**
     * Tests Fs_Item->getAttribute() getting posix info
     */
    public function testGetAttribute_lstat_Posix()
    {
    	if (!extension_loaded('posix')) $this->markTestSkipped("Posix methods not available.");
    	
    	$userinfo = posix_getpwuid(fileowner($this->file));
    	$groupinfo = posix_getgrgid(filegroup($this->file));
    	
    	$this->assertEquals($userinfo['name'], $this->Fs_Item->getAttribute('owner'), 'owner');
        $this->assertEquals($groupinfo['name'], $this->Fs_Item->getAttribute('group'), 'group');
    }
    
    /**
     * Tests Fs_Item->setAttribute()
     */
    public function testSetAttribute()
    {
    	fileperms($this->file); // Stat cache
    	$this->Fs_Item->setAttribute('mode', 0777);
    	$this->assertEquals('0777', sprintf('%04o', fileperms($this->file) & 07777));
	}

    /**
     * Tests Fs_Item->setAttribute() with an attribute that can't be set
     */
    public function testSetAttribute_Exception()
    {
    	$this->setExpectedException('Exception', "Unable to set attribute 'type'; Attribute is read-only.");
    	$this->Fs_Item['type'] = 'unknown';
    }
	
    /**
     * Tests Fs_Item->offsetExists()
     */
    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->Fs_Item['ino']), 'ino');
        $this->assertTrue(isset($this->Fs_Item['mtime']), 'mtime');
        $this->assertTrue(isset($this->Fs_Item['type']), 'type');
        $this->assertFalse(isset($this->Fs_Item['nonexistent_' . md5(uniqid())]), 'nonexistent_MD5');
    }

    /**
     * Tests Fs_Item->offsetGet()
     */
    public function testOffsetGet()
    {
    	$stat = stat($this->file);
        $this->assertEquals($stat['ino'], $this->Fs_Item['ino'], 'ino');
        $this->assertEquals($stat['mtime'], $this->Fs_Item['mtime'], 'mtime');
        $this->assertEquals(filetype($this->file), $this->Fs_Item['type'], 'type');
    }
	
    /**
     * Tests Fs_Item->offsetSet()
     */
    public function testOffsetSet()
    {
    	fileperms($this->file); // Stat cache
    	$this->Fs_Item['mode'] = 0777;
    	$this->assertEquals('0777', sprintf('%04o', fileperms($this->file) & 0777));
	}

    /**
     * Tests Fs_Item->offsetSet() with an attribute that can't be set
     */
    public function testOffsetSet_Exception()
    {
    	$this->setExpectedException('Exception', "Unable to set attribute 'type'; Attribute is read-only.");
    	$this->Fs_Item['type'] = 'unknown';
    }
	
    /**
     * Tests Fs_Item->offsetUnset() with an attribute that can't be unset
     */
    public function testOffsetUnset_Exception()
    {
    	$this->setExpectedException('Exception', "Unable to set attribute 'mode' to null.");
    	unset($this->Fs_Item['mode']);
    }

    /**
     * Tests Fs_Item->exists()
     */
    public function testExists()
    {
        $this->assertTrue($this->Fs_Item->exists());
    }

    /**
     * Tests Fs_Item->isExecutable()
     */
    public function testIsExecutable()
    {
        $this->assertEquals(is_executable($this->file), $this->Fs_Item->isExecutable());
    }

    /**
     * Tests Fs_Item->isReadable()
     */
    public function testIsReadable()
    {
        $this->assertEquals(is_readable($this->file), $this->Fs_Item->isReadable());
	}

    /**
     * Tests Fs_Item->isWritable()
     */
    public function testIsWritable()
    {
        $this->assertEquals(is_writable($this->file), $this->Fs_Item->isWritable());
    }

    /**
     * Tests Fs_Item->isDeletable()
     */
    public function testIsDeletable()
    {
        $this->assertEquals(is_writable($this->file) || is_writable(dirname($this->file)), $this->Fs_Item->isDeletable());
    }

    /**
     * Tests Fs_Item->isHidden()
     */
    public function testIsHidden()
    {
        $this->assertFalse($this->Fs_Item->isHidden());
    }

    /**
     * Tests Fs_Item::chmod()
     */
    public function testChmod()
    {
    	fileperms($this->file); // Stat cache
    	
        $this->Fs_Item->chmod(0766);
        $this->assertEquals('0766', sprintf('%04o', fileperms($this->file) & 07777));

        $this->Fs_Item->chmod(0740);
        $this->assertEquals('0740', sprintf('%04o', fileperms($this->file) & 07777), '2nd time');
    }

    /**
     * Tests Fs_Item::chmod() with string
     */
    public function testChmod_string()
    {
    	fileperms($this->file); // Stat cache
    	
        $this->Fs_Item->chmod('u=rwx,go+rw-x');
        $this->assertEquals('0766', sprintf('%04o', fileperms($this->file) & 0777));
        
        $this->Fs_Item->chmod('g-w,o-rwx');
        $this->assertEquals('0740', sprintf('%04o', fileperms($this->file) & 0777), '2nd time');
    }
	
    /**
     * Tests Fs_Item::chmod() with invalid string
     */
    public function testChmod_invalidString()
    {
    	$this->setExpectedException('Q\ExecException');
    	$this->Fs_Item->chmod('incorrect mode');
    }
    
    /**
     * Tests Fs_Item::chown() with user:group
     */
    public function testChown_Security()
    {
    	$this->setExpectedException('Q\SecurityException', "Won't change owner of '{$this->file}' to user 'myusr:mygrp': To change both owner and group, user array(owner, group) instead");
    	$this->Fs_Item->chown('myusr:mygrp');
    }
    
    
    /**
     * Tests Fs_Item->__invoke()
     */
    public function test__invoke()
    {
    	$this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': This is not a regular file, but a " . filetype(realpath($this->file)));
        $this->Fs_Item();
    }

    /**
     * Tests Fs_Item::__set_state()
     */
    public function test__set_state()
    {
        $ser = var_export($this->Fs_Item, true);
        $this->assertEquals($this->Fs_Item, eval("return $ser;"));
    }
}
