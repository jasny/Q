<?php
use Q\Fs;

require_once 'TestHelper.php';
require_once 'PHPUnit/Framework/TestCase.php';

require_once 'Q/Fs.php';
require_once 'Q/Fs/Unknown.php';
require_once 'Q/Fs/Symlink/Fifo.php';
require_once 'Q/Fs/Symlink/Unknown.php';

/**
 * Fs test case.
 */
class Fs_Test extends PHPUnit_Framework_TestCase
{
	/**
	 * Any temporary files that ware created
	 * @var array
	 */
	protected $tmpfiles = array();
	
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
	}
	
	
    /**
     * Tests Fs::canonicalize() with an absolute path
     */
    public function testCanonicalize_Absolute()
    {
        $this->assertEquals("/usr/bin/php", Fs::canonicalize("/usr/bin/php"));
        $this->assertEquals("/usr/share", Fs::canonicalize("/usr/share/test/./examples/../files/../.."));
    }
    
    /**
     * Tests Fs::canonicalize() with a relative path
     */
    public function testCanonicalize_Relative()
    {
        $this->assertEquals(getcwd() . "/test/abc", Fs::canonicalize("test/abc"));
        $this->assertEquals(dirname(getcwd()) . "/test", Fs::canonicalize(".././test/abc/def/../.."));
        $this->assertEquals("/usr", Fs::canonicalize("/tmp/../../../usr"));
    }

    /**
     * Tests Fs::canonicalize() with home dir
     */
    public function testCanonicalize_Home()
    {
        $this->assertEquals(getenv('HOME'), Fs::canonicalize("~"));
        $this->assertEquals(getenv('HOME') . '/test', Fs::canonicalize("~/test"));
        $this->assertEquals(getcwd() . '/~test', Fs::canonicalize("~test"));
        $this->assertEquals('/~', Fs::canonicalize("/~"));
    }

    /**
     * Tests Fs::mode2type()
     */
    public function testMode2type()
    {
    	$this->assertEquals('socket', Fs::mode2type(0140751));
    	$this->assertEquals('link', Fs::mode2type(0120751));
    	$this->assertEquals('file', Fs::mode2type(0100751));
    	$this->assertEquals('block', Fs::mode2type(0060751));
    	$this->assertEquals('dir', Fs::mode2type(0040751));
    	$this->assertEquals('char', Fs::mode2type(0020751));
    	$this->assertEquals('fifo', Fs::mode2type(0010751));
    	$this->assertEquals('unknown', Fs::mode2type(0777));
    }
    
    /**
     * Tests Fs::mode2perms()
     */
    public function testMode2perms()
    {
    	$this->assertEquals(' rwxrwxrwx', Fs::mode2perms(0777));
    	$this->assertEquals(' ---------', Fs::mode2perms(0000));
    	$this->assertEquals(' rwxr-xr-x', Fs::mode2perms(0755));
    	$this->assertEquals(' r---w---x', Fs::mode2perms(0421));

    	$this->assertEquals(' rwxr-xr-x', Fs::mode2perms(00755));
    	$this->assertEquals(' rwsr-sr-t', Fs::mode2perms(07755));
    	$this->assertEquals(' rwSr-Sr-T', Fs::mode2perms(07644));
    	
    	$this->assertEquals('srwxr-xr-x', Fs::mode2perms(0140755));
    	$this->assertEquals('lrwxr-xr-x', Fs::mode2perms(0120755));
    	$this->assertEquals('-rwxr-xr-x', Fs::mode2perms(0100755));
    	$this->assertEquals('brwxr-xr-x', Fs::mode2perms(0060755));
    	$this->assertEquals('drwxr-xr-x', Fs::mode2perms(0040755));
    	$this->assertEquals('crwxr-xr-x', Fs::mode2perms(0020755));
    	$this->assertEquals('prwxr-xr-x', Fs::mode2perms(0010755));
    }

    /**
     * Tests Fs::mode2umask()
     */
    public function testMode2umask()
    {
    	$this->assertEquals('000', sprintf('%03o', Fs::mode2umask(0777)));
    	$this->assertEquals('777', sprintf('%03o', Fs::mode2umask(0000)));
    	$this->assertEquals('022', sprintf('%03o', Fs::mode2umask(0755)));
    	$this->assertEquals('356', sprintf('%03o', Fs::mode2umask(0421)));
    }
    

    /**
     * Tests Fs::file()
     */
    public function testFile()
    {
        $file = Fs::file(__FILE__);
        $this->assertType('Q\Fs_File', $file);
        $this->assertEquals(__FILE__, (string)$file);
	}
    
    /**
     * Tests Fs::dir()
     */
    public function testDir()
    {
        $file = Fs::dir(__DIR__);
        $this->assertType('Q\Fs_Dir', $file);
        $this->assertEquals(__DIR__, (string)$file);
    }
	
    /**
     * Tests Fs::block()
     */
    public function testBlock()
    {
        $file = Fs::block('/dev/sda');
        $this->assertType('Q\Fs_Block', $file);
        $this->assertEquals('/dev/sda', (string)$file);
	}

    /**
     * Tests Fs::char()
     */
    public function testChar()
    {
        $file = Fs::char('/dev/null');
        $this->assertType('Q\Fs_Char', $file);
        $this->assertEquals('/dev/null', (string)$file);
	}
	
    /**
     * Tests Fs::socket()
     */
    public function testSocket()
    {
        $file = Fs::socket('/does/not/exists');
        $this->assertType('Q\Fs_Socket', $file);
        $this->assertEquals('/does/not/exists', (string)$file);
	}

    /**
     * Tests Fs::fifo()
     */
    public function testFifo()
    {
        $file = Fs::fifo('/does/not/exists');
        $this->assertType('Q\Fs_Fifo', $file);
        $this->assertEquals('/does/not/exists', (string)$file);
	}

	
    /**
     * Tests Fs::has()
     */
    public function testHas()
    {
        $this->assertTrue(Fs::has(__FILE__));
    }
    
    /**
     * Tests Fs::has() with a non-existing file
     */
    public function testHas_NotExists()
    {
        $this->assertFalse(Fs::has('/does/not/exists/' . md5(uniqid())));
    }
    
    /**
     * Tests Fs::has() with a broken symlink
     */
    public function testHas_Symlink_Broken()
    {
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	symlink('/does/not/exist/' . basename($link), $link);
    	
        $this->assertTrue(Fs::has($link));
    }
        
    /**
     * Tests Fs::get() with a file
     */
    public function testGet_File()
    {
        $file = Fs::get(__FILE__);
        $this->assertType('Q\Fs_File', $file);
        $this->assertEquals(__FILE__, (string)$file);
    }

    /**
     * Tests Fs::get() with a file
     */
    public function testGet_Dir()
    {
        $file = Fs::get(__DIR__);
        $this->assertType('Q\Fs_Dir', $file);
        $this->assertEquals(__Dir__, (string)$file);
    }

    /**
     * Tests Fs::get() with a block device 
     */
    public function testGet_Block()
    {
    	if (!file_exists('/dev/sda') || filetype('/dev/sda') != 'block') $this->markTestSkipped("Block device '/dev/sda' does not exist.");
    	
    	$file = Fs::get('/dev/sda');
    	$this->assertType('Q\Fs_Block', $file);
    	$this->assertEquals('/dev/sda', (string)$file);
    }
    
    /**
     * Tests Fs::get() with a char block device 
     */
    public function testGet_Char()
    {
    	if (!file_exists('/dev/null')) $this->markTestSkipped("Char device '/dev/null' does not exist.");
    	
    	$file = Fs::get('/dev/null');
    	$this->assertType('Q\Fs_Char', $file);
    	$this->assertEquals('/dev/null', (string)$file);
    }

    /**
     * Tests Fs::get() with a unix socket 
     */
    public function testGet_Socket()
    {
    	if (!file_exists('/var/run/mysqld/mysqld.sock')) $this->markTestSkipped("Socket '/var/run/mysqld/mysqld.sock' does not exist.");
    	
    	$file = Fs::get('/var/run/mysqld/mysqld.sock');
    	$this->assertType('Q\Fs_Socket', $file);
    	$this->assertEquals('/var/run/mysqld/mysqld.sock', (string)$file);
    }
    
    
    /**
     * Tests Fs::get() with a symlink to a file
     */
    public function testGet_Symlink_File()
    {
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	symlink(__FILE__, $link);
    	
        $file = Fs::get($link);
        $this->assertType('Q\Fs_Symlink_File', $file);
        $this->assertEquals($link, (string)$file);
        $this->assertEquals(__FILE__, (string)$file->target());
    }

    /**
     * Tests Fs::get() with a symlink to a dir
     */
    public function testGet_Symlink_Dir()
    {
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	symlink(__FILE__, $link);
    	
        $file = Fs::get($link);
        $this->assertType('Q\Fs_Symlink_File', $file);
        $this->assertEquals($link, (string)$file);
        $this->assertEquals(__FILE__, (string)$file->target());
    }
    
    /**
     * Tests Fs::get() with a symlink to a char device 
     */
    public function testGet_Symlink_Char()
    {
    	if (!file_exists('/dev/null')) $this->markTestSkipped("Char device '/dev/null' does not exist.");
    	
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	symlink('/dev/null', $link);
    	
    	$file = Fs::get($link);
    	$this->assertType('Q\Fs_Symlink_Char', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals('/dev/null', (string)$file->target());
    }

    /**
     * Tests Fs::get() with a symlink to a Block device 
     */
    public function testGet_Symlink_Block()
    {
    	if (!file_exists('/dev/sda')) $this->markTestSkipped("Block device '/dev/sda' does not exist.");
    	
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	symlink('/dev/sda', $link);
    	
    	$file = Fs::get($link);
    	$this->assertType('Q\Fs_Symlink_Block', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals('/dev/sda', (string)$file->target());
    }

    /**
     * Tests Fs::get() with a symlink to a unix socket 
     */
    public function testGet_Symlink_Socket()
    {
    	if (!file_exists('/var/run/mysqld/mysqld.sock')) $this->markTestSkipped("Socket '/var/run/mysqld/mysqld.sock' does not exist.");
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	symlink('/var/run/mysqld/mysqld.sock', $link);
    	
    	$file = Fs::get($link);
    	$this->assertType('Q\Fs_Symlink_Socket', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals('/var/run/mysqld/mysqld.sock', (string)$file->target());
    }
    
    /**
     * Tests Fs::get() with a broken symlink
     */
    public function testGet_Symlink_Broken()
    {
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	symlink('/does/not/exist/' . basename($link), $link);
    	
        $file = Fs::get($link);
        $this->assertType('Q\Fs_Symlink_Broken', $file);
        $this->assertEquals($link, (string)$file);
        $this->assertEquals('/does/not/exist/' . basename($link), (string)$file->target());
    }
    
    
    /**
     * Tests Fs::symlink(), link to a file 
     */
    public function testSymlink_File()
    {
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	
    	$file = Fs::symlink(__FILE__, $link);
    	$this->assertType('Q\Fs_Symlink_File', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals(__FILE__, (string)$file->target());
    }

    /**
     * Tests Fs::symlink(), link to a dir 
     */
    public function testSymlink_Dir()
    {
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	
    	$file = Fs::symlink(__DIR__, $link);
    	$this->assertType('Q\Fs_Symlink_Dir', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals(__DIR__, (string)$file->target());
    }

    /**
     * Tests Fs::symlink(), link to a char device 
     */
    public function testSymlink_Char()
    {
    	if (!file_exists('/dev/null')) $this->markTestSkipped("Char device '/dev/null' does not exist.");
    	
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	
    	$file = Fs::symlink('/dev/null', $link);
    	$this->assertType('Q\Fs_Symlink_Char', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals('/dev/null', (string)$file->target());
    }

    /**
     * Tests Fs::symlink(), link to a block device 
     */
    public function testSymlink_Block()
    {
    	if (!file_exists('/dev/sda')) $this->markTestSkipped("Block device '/dev/sda' does not exist.");
    	
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	
    	$file = Fs::symlink('/dev/sda', $link);
    	$this->assertType('Q\Fs_Symlink_Block', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals('/dev/sda', (string)$file->target());
    }

    /**
     * Tests Fs::symlink(), link to a unix socket 
     */
    public function testSymlink_Socket()
    {
    	if (!file_exists('/var/run/mysqld/mysqld.sock')) $this->markTestSkipped("Socket '/var/run/mysqld/mysqld.sock' does not exist.");
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	
    	$file = Fs::symlink('/var/run/mysqld/mysqld.sock', $link);
    	$this->assertType('Q\Fs_Symlink_Socket', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals('/var/run/mysqld/mysqld.sock', (string)$file->target());
    }
    
    /**
     * Tests Fs::symlink(), creating a broken link
     */
    public function testSymlink_Broken()
    {
    	$this->tmpfiles[] = $link = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	
    	$file = Fs::symlink('/does/not/exist/' . basename($link), $link);
    	$this->assertType('Q\Fs_Symlink_Broken', $file);
    	$this->assertEquals($link, (string)$file);
    	$this->assertEquals('/does/not/exist/' . basename($link), (string)$file->target());
    }
	
    
    /**
     * Tests Fs->glob()
     */
    public function testGlob()
    {
    	$dir = dirname(__DIR__);
    	
    	$files = array();
    	foreach (glob("$dir/*.php") as $file) $files[] = Fs::get($file); 
        $this->assertEquals($files, Fs::glob("$dir/*.php"));
        
    	$files = array();
    	foreach (glob("$dir/C*", GLOB_ONLYDIR) as $file) $files[] = Fs::get($file); 
        $this->assertEquals($files, Fs::glob("$dir/C*", GLOB_ONLYDIR));
    }

    /**
     * Tests Fs->bin()
     */
    public function testBin()
    {
    	if (!preg_match('~(^|' . PATH_SEPARATOR . ')/bin($|' . PATH_SEPARATOR . ')~', getenv('PATH'))) $this->markTestSkipped("/bin is not in PATH enviroment variable '" . getenv('PATH') . "'.");
    	if (!file_exists('/bin/ls')) $this->markTestSkipped("File /bin/ls does not exist.");
    	 
        $file = Fs::bin('ls');
        $this->assertType('Q\Fs_File', $file);
        $this->assertEquals('/bin/ls', (string)$file);
    }

    /**
     * Tests Fs::clearStatCache()
     */
    public function testClearStatCache()
    {
    	$file = sys_get_temp_dir();
    	
    	$stat = stat($file);
    	if (!@touch($file)) $this->markTestSkipped("Could not touch $file.");
    	if (!$stat == stat($file)) $this->markTestSkipped("Stats don't appear to be cached.");
    	
        Fs::clearStatCache();
        $this->assertNotEquals($stat, stat($file));
    }
    
    
    /**
     * Tests Fs::typeOfNode()
     */
    public function testTypeOfNode()
    {
    	$this->assertEquals('file', Fs::typeOfNode($this->getMock('Q\Fs_File', array(), array(), '', false)));
    	$this->assertEquals('dir', Fs::typeOfNode($this->getMock('Q\Fs_Dir', array(), array(), '', false)));
    	$this->assertEquals('block', Fs::typeOfNode($this->getMock('Q\Fs_Block', array(), array(), '', false)));
    	$this->assertEquals('char', Fs::typeOfNode($this->getMock('Q\Fs_Char', array(), array(), '', false)));
    	$this->assertEquals('fifo', Fs::typeOfNode($this->getMock('Q\Fs_Fifo', array(), array(), '', false)));
    	$this->assertEquals('socket', Fs::typeOfNode($this->getMock('Q\Fs_Socket', array(), array(), '', false)));
    	$this->assertEquals('unknown', Fs::typeOfNode($this->getMock('Q\Fs_Unknown', array(), array(), '', false)));
    	
    	$this->assertEquals('link/', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Broken', array(), array(), '', false)));
    	$this->assertEquals('link/file', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_File', array(), array(), '', false)));
    	$this->assertEquals('link/dir', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Dir', array(), array(), '', false)));
    	$this->assertEquals('link/block', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Block', array(), array(), '', false)));
    	$this->assertEquals('link/char', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Char', array(), array(), '', false)));
    	$this->assertEquals('link/fifo', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Fifo', array(), array(), '', false)));
    	$this->assertEquals('link/socket', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Socket', array(), array(), '', false)));
    	$this->assertEquals('link/unknown', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Unknown', array(), array(), '', false)));
	}
	
    /**
     * Tests Fs::typeOfNode() for an illegal Fs_Node type
     */
    public function testTypeOfNode_Fail()
    {
    	$file = $this->getMock('Q\Fs_Node', array('__toString', 'path'), array(''), 'StrangeFsNode', array(), '', false);
    	$file->expects($this->any())->method('__toString')->will($this->returnValue('/something'));
    	$file->expects($this->any())->method('path')->will($this->returnValue('/something'));
    	
    	$this->setExpectedException('Q\Exception', "Unable to determine type of '/something': Class 'StrangeFsNode' is not any of the known types");
    	Fs::typeOfNode($file);
    }
	
    /**
     * Tests Fs::typeOfNode() with filenames
     */
    public function testTypeOfNode_String()
    {
    	$this->assertEquals('file', Fs::typeOfNode(__FILE__));
    	$this->assertEquals('dir', Fs::typeOfNode(__DIR__));
    	
    	$this->tmpfiles[] = $link_file = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	$this->tmpfiles[] = $link_dir = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	if (symlink(__FILE__, $link_file)) $this->assertEquals('link/file', Fs::typeOfNode($link_file));
    	if (symlink(__DIR__, $link_dir)) $this->assertEquals('link/dir', Fs::typeOfNode($link_dir));
	}
    
    /**
     * Tests Fs::typeOfNode() with ALWAYS_FOLLOW option
     */
    public function testTypeOfNode_AlwayFollow()
    {
    	$this->assertEquals('file', Fs::typeOfNode($this->getMock('Q\Fs_File', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('dir', Fs::typeOfNode($this->getMock('Q\Fs_Dir', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('block', Fs::typeOfNode($this->getMock('Q\Fs_Block', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('char', Fs::typeOfNode($this->getMock('Q\Fs_Char', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('fifo', Fs::typeOfNode($this->getMock('Q\Fs_Fifo', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('socket', Fs::typeOfNode($this->getMock('Q\Fs_Socket', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('unknown', Fs::typeOfNode($this->getMock('Q\Fs_Unknown', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	
    	$this->assertEquals('file', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_File', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('dir', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Dir', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('block', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Block', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('char', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Char', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('fifo', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Fifo', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('socket', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Socket', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('unknown', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Unknown', array(), array(), '', false), Fs::ALWAYS_FOLLOW));
	}
	
    /**
     * Tests Fs::typeOfNode() with ALWAYS_FOLLOW option for broken symlink
     */
    public function testTypeOfNode_AlwayFollow_BrokenSymlink()
    {
    	$file = $this->getMock('Q\Fs_Symlink_Broken', array('__toString', 'path'), array(), '', false);
    	$file->expects($this->any())->method('__toString')->will($this->returnValue('/broken/link'));
    	$file->expects($this->any())->method('path')->will($this->returnValue('/broken/link'));
    	
    	$this->setExpectedException('Q\Fs_Exception', "Unable to determine type of target of '/broken/link': File is a broken link");
    	Fs::typeOfNode($file, Fs::ALWAYS_FOLLOW);
    }

    /**
     * Tests Fs::typeOfNode() with filenames
     */
    public function testTypeOfNode_String_AlwayFollow()
    {
    	$this->assertEquals('file', Fs::typeOfNode(__FILE__, Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('dir', Fs::typeOfNode(__DIR__, Fs::ALWAYS_FOLLOW));
    	
    	$this->tmpfiles[] = $link_file = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	$this->tmpfiles[] = $link_dir = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	if (symlink(__FILE__, $link_file)) $this->assertEquals('file', Fs::typeOfNode($link_file, Fs::ALWAYS_FOLLOW));
    	if (symlink(__DIR__, $link_dir)) $this->assertEquals('dir', Fs::typeOfNode($link_dir, Fs::ALWAYS_FOLLOW));
	}
    
	/**
     * Tests Fs::typeOfNode() with DESCRIPTION option
     */
    public function testTypeOfNode_Description()
    {
    	$this->assertEquals('file', Fs::typeOfNode($this->getMock('Q\Fs_File', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('directory', Fs::typeOfNode($this->getMock('Q\Fs_Dir', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('block device', Fs::typeOfNode($this->getMock('Q\Fs_Block', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('char device', Fs::typeOfNode($this->getMock('Q\Fs_Char', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('named pipe', Fs::typeOfNode($this->getMock('Q\Fs_Fifo', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('socket', Fs::typeOfNode($this->getMock('Q\Fs_Socket', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('unknown filetype', Fs::typeOfNode($this->getMock('Q\Fs_Unknown', array(), array(), '', false), Fs::DESCRIPTION));
    	
    	$this->assertEquals('broken symlink', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Broken', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('symlink to a file', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_File', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('symlink to a directory', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Dir', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('symlink to a block device', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Block', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('symlink to a char device', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Char', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('symlink to a named pipe', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Fifo', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('symlink to a socket', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Socket', array(), array(), '', false), Fs::DESCRIPTION));
    	$this->assertEquals('symlink to an unknown filetype', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Unknown', array(), array(), '', false), Fs::DESCRIPTION));
	}
	
    /**
     * Tests Fs::typeOfNode() with filenames
     */
    public function testTypeOfNode_Description_String()
    {
    	$this->assertEquals('file', Fs::typeOfNode(__FILE__, Fs::DESCRIPTION));
    	$this->assertEquals('directory', Fs::typeOfNode(__DIR__, Fs::DESCRIPTION));
    	
    	$this->tmpfiles[] = $link_file = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	$this->tmpfiles[] = $link_dir = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	if (symlink(__FILE__, $link_file)) $this->assertEquals('symlink to a file', Fs::typeOfNode($link_file, Fs::DESCRIPTION));
    	if (symlink(__DIR__, $link_dir)) $this->assertEquals('symlink to a directory', Fs::typeOfNode($link_dir, Fs::DESCRIPTION));
	}
	
    /**
     * Tests Fs::typeOfNode() with ALWAYS_FOLLOW and DESCRIPTION options
     */
    public function testTypeOfNode_Description_AlwayFollow()
    {
    	$this->assertEquals('file', Fs::typeOfNode($this->getMock('Q\Fs_File', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('directory', Fs::typeOfNode($this->getMock('Q\Fs_Dir', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('block device', Fs::typeOfNode($this->getMock('Q\Fs_Block', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('char device', Fs::typeOfNode($this->getMock('Q\Fs_Char', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('named pipe', Fs::typeOfNode($this->getMock('Q\Fs_Fifo', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('socket', Fs::typeOfNode($this->getMock('Q\Fs_Socket', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('unknown filetype', Fs::typeOfNode($this->getMock('Q\Fs_Unknown', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	
    	$this->assertEquals('file', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_File', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('directory', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Dir', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('block device', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Block', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('char device', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Char', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('named pipe', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Fifo', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('socket', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Socket', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('unknown filetype', Fs::typeOfNode($this->getMock('Q\Fs_Symlink_Unknown', array(), array(), '', false), Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
	}
	
    /**
     * Tests Fs::typeOfNode() with filenames
     */
    public function testTypeOfNode_String_Description_AlwayFollow()
    {
    	$this->assertEquals('file', Fs::typeOfNode(__FILE__, Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	$this->assertEquals('directory', Fs::typeOfNode(__DIR__, Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	
    	$this->tmpfiles[] = $link_file = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	$this->tmpfiles[] = $link_dir = sys_get_temp_dir() . '/q-fs_test.' . md5(uniqid());
    	if (symlink(__FILE__, $link_file)) $this->assertEquals('file', Fs::typeOfNode($link_file, Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
    	if (symlink(__DIR__, $link_dir)) $this->assertEquals('directory', Fs::typeOfNode($link_dir, Fs::DESCRIPTION | Fs::ALWAYS_FOLLOW));
	}
}
