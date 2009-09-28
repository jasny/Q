<?php
use Q\Fs;

require_once 'Q/Fs.php';
require_once 'TestHelper.php';
require_once 'PHPUnit/Framework/TestCase.php';

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
}
