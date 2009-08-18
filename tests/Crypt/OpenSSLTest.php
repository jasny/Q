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
	 * Tests Crypt_DoubleOpenSSL->decrypt()
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
	 * Tests Crypt_DoubleOpenSSL->decrypt() with DES method
	 */
	public function testDecrypt_DES()
	{
	    $this->Crypt_OpenSSL->method = 'DES';
	    $encrypted = openssl_encrypt("a test string", 'DES', 's3cret');
		$this->assertEquals("a test string", $this->Crypt_OpenSSL->decrypt($encrypted));
	}	
	
}
