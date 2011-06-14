<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__DIR__) . PATH_SEPARATOR . dirname(__DIR__) . '/../library');

use Q\Crypt_CRC32;

require_once 'Q/Crypt/CRC32.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Crypt_CRC32 test case.
 */
class Crypt_CRC32Test extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Crypt_CRC32
	 */
	private $Crypt_CRC32;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Crypt_CRC32 = new Q\Crypt_CRC32();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_CRC32 = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_CRC32->encrypt()
	 */
	public function testEncrypt()
	{
		$this->assertEquals(sprintf('%08x', crc32("a test string")), $this->Crypt_CRC32->encrypt("a test string"));
	}

	/**
	 * Tests Crypt_CRC32->encrypt()
	 */
	public function testEncrypt_Salt()
	{
	    $this->Crypt_CRC32->useSalt = true;
	    
		$hash = $this->Crypt_CRC32->encrypt("a test string");
		$this->assertRegExp('/^\w{6}\$\w{8}$/', $hash);
		
		$this->assertNotEquals(preg_replace('/^\w{6}\$/', '', $hash), $this->Crypt_CRC32->encrypt("a test string"));
        $this->assertEquals($hash, $this->Crypt_CRC32->encrypt("a test string", $hash));		
	}
	
	/**
	 * Tests Crypt_CRC32->encrypt() with secret phrase
	 */
	public function testEncrypt_Secret()
	{
	    $this->Crypt_CRC32->secret = "s3cret";
		$this->assertEquals(sprintf('%08x', crc32("a test string" . "s3cret")), $this->Crypt_CRC32->encrypt("a test string"));
	}
	
	/**
	 * Tests Crypt_CRC32->encrypt() with a file
	 */
	public function testEncrypt_File()
	{
                $string = "a test string";

                /*
		$file = $this->getMock('Fs_File', array('getContents'));
		$file->expects($this->once())->method('getContents')->will($this->returnValue($string));
		
		$this->assertEquals(sprintf('%08x', crc32($string)), $this->Crypt_CRC32->encrypt($file));
                */
	}
}
