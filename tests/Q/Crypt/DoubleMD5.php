<?php

require_once __DIR__ . '/../init.inc';
require_once 'Q/Crypt/DoubleMD5.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Crypt_DoubleMD5 test case.
 */
class Test_Crypt_DoubleMD5 extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    
	/**
	 * @var Crypt_DoubleMD5
	 */
	private $Crypt_DoubleMD5;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->Crypt_DoubleMD5 = new Q\Crypt_DoubleMD5();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Crypt_DoubleMD5 = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Crypt_DoubleMD5->encrypt()
	 */
	public function testEncrypt()
	{
		$this->assertEquals(md5(md5("a test string")), $this->Crypt_DoubleMD5->encrypt("a test string"));
	}

	/**
	 * Tests Crypt_DoubleMD5->encrypt() with salt
	 */
	public function testEncrypt_Salt()
	{
	    $this->Crypt_DoubleMD5->use_salt = true;
	    
		$hash = $this->Crypt_DoubleMD5->encrypt("a test string");
		$this->assertRegExp('/^\w{6}\$\w{32}$/', $hash);
		
		$this->assertNotEquals(preg_replace('/\w{32}$/', '', $hash) . md5(md5("a test string")), $hash);
        $this->assertEquals($hash, $this->Crypt_DoubleMD5->encrypt("a test string", $hash));		
	}
	
	/**
	 * Tests Crypt_DoubleMD5->encrypt() with secret phrase
	 */
	public function testEncrypt_Secret()
	{
	    $this->Crypt_DoubleMD5->secret = "s3cret";
		$this->assertEquals(md5(md5("a test string" . "s3cret")), $this->Crypt_DoubleMD5->encrypt("a test string"));
	}
}

if (PHPUnit_MAIN_METHOD == 'Test_Crypt_DoubleMD5::main') Test_Crypt_DoubleMD5::main();
?>