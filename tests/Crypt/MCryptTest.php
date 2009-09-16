<?php
use Q\Crypt_MCrypt;

require_once 'TestHelper.php';
require_once 'Q/Crypt/MCrypt.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Crypt_MCrypt test case.
 */
class Crypt_MCryptTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Crypt_MCrypt
	 */
	private $Crypt_MCrypt;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		if (!extension_loaded('mcrypt')) $this->markTestSkipped("mcrypt extension is not available");
		
		parent::setUp();
		$this->Crypt_MCrypt = new Q\Crypt_MCrypt(array('method'=>'blowfish', 'secret'=>'s3cret'));
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_MCrypt = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_MCrypt->encrypt() with blowfish method
	 */
	public function testEncrypt()
	{
		$this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->encrypt("a test string"));
	}
	
	/**
	 * Tests Crypt_MCrypt->decrypt() with blowfish method
	 */
	public function testDecrypt()
	{
	    $encrypted = mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB);
		$this->assertEquals("a test string", $this->Crypt_MCrypt->decrypt($encrypted));
	}

	/**
	 * Tests Crypt_MCrypt->encrypt() with DES method
	 */
	public function testEncrypt_DES()
	{
	    $this->Crypt_MCrypt->method = 'des';
		$this->assertEquals(mcrypt_encrypt('DES', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->encrypt("a test string"));
	}
	
	/**
	 * Tests Crypt_MCrypt->decrypt() with DES method
	 */
	public function testDecrypt_DES()
	{
	    $this->Crypt_MCrypt->method = 'des';
	    $encrypted = mcrypt_encrypt('DES', 's3cret', "a test string", MCRYPT_MODE_ECB);
		$this->assertEquals("a test string", $this->Crypt_MCrypt->decrypt($encrypted));
	}	

	/**
	 * Tests Crypt_MCrypt->encrypt() with a file
	 */
	public function testEncrypt_File()
	{
		$file = $this->getMock('Q\Fs_File', array('getContents'));
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
		$this->assertEquals(mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB), $this->Crypt_MCrypt->encrypt($file));
	}
	
	/**
	 * Tests Crypt_MCrypt->decrypt() with a file
	 */
	public function testDecrypt_File()
	{
		$encrypted = mcrypt_encrypt('blowfish', 's3cret', "a test string", MCRYPT_MODE_ECB);
		
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'));
		$file->expects($this->never())->method('__toString');
		$file->expects($this->once())->method('getContents')->will($this->returnValue($encrypted));
		
		$this->assertEquals("a test string", $this->Crypt_MCrypt->decrypt($file));
	}
	
	/**
	 * Tests Crypt_MCrypt->decrypt() where decrypt fails
	 */
	public function testDecrypt_NotEncrypted()
	{
		$this->setExpectedException('Q\Decrypt_Exception', "Failed to decrypt value with blowfish using mycrypt.");
		$this->Crypt_MCrypt->decrypt("not encrypted");
	}

	/**
	 * Tests Crypt_MCrypt->decrypt() where decrypt fails because of incorrect secret phrase
	 */
	public function testDecrypt_WrongSecret()
	{
		$this->setExpectedException('Q\Decrypt_Exception', "Failed to decrypt value with blowfish using mycrypt.");
		$encrypted = mcrypt_encrypt('blowfish', 'another_secret', "a test string", MCRYPT_MODE_ECB);
		$this->Crypt_MCrypt->decrypt($encrypted);
	}
}
