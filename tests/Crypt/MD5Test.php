<?php
use Q\Crypt_MD5;

require_once 'TestHelper.php';
require_once 'Q/Crypt/MD5.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Crypt_MD5 test case.
 */
class Crypt_MD5Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Crypt_MD5
	 */
	private $Crypt_MD5;

	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Crypt_MD5 = new Q\Crypt_MD5();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		if (isset($this->tmpfile) && file_exists($this->tmpfile)) unlink($this->tmpfile);
		
		$this->Crypt_MD5 = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_MD5->encrypt()
	 */
	public function testEncrypt()
	{
		$this->assertEquals(md5("a test string"), $this->Crypt_MD5->encrypt("a test string"));
	}

	/**
	 * Tests Crypt_MD5->encrypt()
	 */
	public function testEncrypt_Salt()
	{
	    $this->Crypt_MD5->useSalt = true;
	    
		$hash = $this->Crypt_MD5->encrypt("a test string");
		$this->assertRegExp('/^\w{6}\$\w{32}$/', $hash);
		
		$this->assertNotEquals(preg_replace('/\w{32}$/', '', $hash) . md5("a test string"), $hash);
        $this->assertEquals($hash, $this->Crypt_MD5->encrypt("a test string", $hash));		
	}
	
	/**
	 * Tests Crypt_MD5->encrypt() with secret phrase
	 */
	public function testEncrypt_Secret()
	{
	    $this->Crypt_MD5->secret = "s3cret";
		$this->assertEquals(md5("a test string" . "s3cret"), $this->Crypt_MD5->encrypt("a test string"));
	}
	
	/**
	 * Tests Crypt_MD5->encrypt() with a file
	 */
	public function testEncrypt_File()
	{
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		file_put_contents($this->tmpfile, "a test string");
		
		$file = $this->getMock('Q\Fs_File', array('__toString'));
		$file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
		
		$this->assertEquals(md5("a test string"), $this->Crypt_MD5->encrypt($file));
	}
	
	/**
	 * Tests Crypt_MD5->encrypt() with a file using a secret phrase
	 */
	public function testEncrypt_File_Secret()
	{
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'));
		$file->expects($this->never())->method('__toString');
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->Crypt_MD5->secret = "s3cret";
		$this->assertEquals(md5("a test string" . "s3cret"), $this->Crypt_MD5->encrypt($file));
	}
}
