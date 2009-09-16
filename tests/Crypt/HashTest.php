<?php
use Q\Crypt_Hash;

require_once 'TestHelper.php';
require_once 'Q/Crypt/Hash.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Crypt_Hash test case.
 */
class Crypt_HashTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Crypt_Hash
	 */
	private $Crypt_Hash;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Crypt_Hash = new Q\Crypt_Hash('md5');
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_Hash = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_Hash->encrypt() with whirlpool algoritm
	 */
	public function testEncrypt_Whirlpool()
	{
		$this->Crypt_Hash->method = 'whirlpool';
		$this->assertEquals(hash('whirlpool', "a test string"), $this->Crypt_Hash->encrypt("a test string"));
	}

	/**
	 * Tests Crypt_Hash->encrypt()
	 */
	public function testEncrypt_Salt()
	{
	    $this->Crypt_Hash->useSalt = true;
	    
		$hash = $this->Crypt_Hash->encrypt("a test string");
		$this->assertRegExp('/^\w{6}\$\w{32}$/', $hash);
		
		$this->assertNotEquals(preg_replace('/\w{32}$/', '', $hash) . hash('md5', "a test string"), $hash);
        $this->assertEquals($hash, $this->Crypt_Hash->encrypt("a test string", $hash));		
	}
	
	/**
	 * Tests Crypt_DoubleHash->encrypt() with secret phrase
	 */
	public function testEncrypt_Secret()
	{
	    $this->Crypt_Hash->secret = "s3cret";
		$this->assertEquals(hash('md5', "a test string" . "s3cret"), $this->Crypt_Hash->encrypt("a test string"));
	}
	
	/**
	 * Tests Crypt_Hash->encrypt() with a file
	 */
	public function testEncrypt_File()
	{
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		file_put_contents($this->tmpfile, "a test string");
		
		$file = $this->getMock('Q\Fs_File', array('__toString'));
		$file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
		
		$this->assertEquals(hash('md5', "a test string"), $this->Crypt_Hash->encrypt($file));
	}
	
	/**
	 * Tests Crypt_Hash->encrypt() with a file using a secret phrase
	 */
	public function testEncrypt_File_Secret()
	{
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'));
		$file->expects($this->never())->method('__toString');
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->Crypt_Hash->secret = "s3cret";
		$this->assertEquals(hash('md5', "a test string" . "s3cret"), $this->Crypt_Hash->encrypt($file));
	}
}
