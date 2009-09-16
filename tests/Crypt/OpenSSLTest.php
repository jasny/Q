<?php
use Q\Crypt_OpenSSL;

require_once 'TestHelper.php';
require_once 'Q/Crypt/OpenSSL.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Crypt_OpenSSL test case.
 */
class Crypt_OpenSSLTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Crypt_OpenSSL
	 */
	private $Crypt_OpenSSL;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Crypt_OpenSSL = new Q\Crypt_OpenSSL(array('secret'=>'s3cret'));
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_OpenSSL = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_OpenSSL->encrypt()
	 */
	public function testEncrypt()
	{
		$this->assertEquals(openssl_encrypt("a test string", 'AES256', 's3cret'), $this->Crypt_OpenSSL->encrypt("a test string"));
	}
	
	/**
	 * Tests Crypt_OpenSSL->decrypt()
	 */
	public function testDecrypt()
	{
	    $encrypted = openssl_encrypt("a test string", 'AES256', 's3cret');
		$this->assertEquals("a test string", $this->Crypt_OpenSSL->decrypt($encrypted));
	}

	/**
	 * Tests Crypt_OpenSSL->encrypt() with DES method
	 */
	public function testEncrypt_DES()
	{
	    $this->Crypt_OpenSSL->method = 'DES';
		$this->assertEquals(openssl_encrypt("a test string", 'DES', 's3cret'), $this->Crypt_OpenSSL->encrypt("a test string"));
	}
	
	/**
	 * Tests Crypt_OpenSSL->decrypt() with DES method
	 */
	public function testDecrypt_DES()
	{
	    $this->Crypt_OpenSSL->method = 'DES';
	    $encrypted = openssl_encrypt("a test string", 'DES', 's3cret');
		$this->assertEquals("a test string", $this->Crypt_OpenSSL->decrypt($encrypted));
	}	

	/**
	 * Tests Crypt_OpenSSL->encrypt() with a file
	 */
	public function testEncrypt_File()
	{
		$file = $this->getMock('Q\Fs_File', array('getContents'));
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(openssl_encrypt("a test string", 'AES256', 's3cret'), $this->Crypt_OpenSSL->encrypt($file));
	}
	
	/**
	 * Tests Crypt_OpenSSL->decrypt() with a file
	 */
	public function testDecrypt_File()
	{
		$encrypted = openssl_encrypt("a test string", 'AES256', 's3cret');
		
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'));
		$file->expects($this->never())->method('__toString');
		$file->expects($this->once())->method('getContents')->will($this->returnValue($encrypted));
		
		$this->assertEquals("a test string", $this->Crypt_OpenSSL->decrypt($file));
	}
	
	/**
	 * Tests Crypt_OpenSSL->decrypt() where decrypt fails
	 */
	public function testDecrypt_NotEncrypted()
	{
		$this->setExpectedException('Q\Decrypt_Exception', "Failed to decrypt value with AES256 using openssl.");
		$this->Crypt_OpenSSL->decrypt("not encrypted");
	}

	/**
	 * Tests Crypt_OpenSSL->decrypt() where decrypt fails because of incorrect secret phrase
	 */
	public function testDecrypt_WrongSecret()
	{
		$this->setExpectedException('Q\Decrypt_Exception', "Failed to decrypt value with AES256 using openssl.");
		$encrypted = openssl_encrypt("a test string", 'AES256', 'another secret');
		$this->Crypt_OpenSSL->decrypt($encrypted);
	}
}
