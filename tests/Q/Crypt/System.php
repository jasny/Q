<?php

require_once __DIR__ . '/../init.inc';
require_once 'Q/Crypt/System.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Crypt_System test case.
 */
class Test_Crypt_System extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    
	/**
	 * Tests Crypt_System->encrypt()
	 */
	public function testEncrypt()
	{
	    $crypt = new Q\Crypt_System();
	    
	    $hash = $crypt->encrypt("a test string");
		$this->assertEquals(crypt("a test string", $hash), $hash);
	}

	/**
	 * Tests Crypt_System->encrypt() using standard DES-based encryption
	 */
	public function testEncrypt_std_des()
	{
	    if (!CRYPT_STD_DES) $this->markTestSkipped("Standard DES-based encryption with crypt() not available.");
	    
	    $crypt = new Q\Crypt_System('std_des');
	    
	    $this->assertRegExp('/^.{13}$/', $crypt->encrypt("rasmuslerdorf"));
	    $this->assertEquals(crypt("rasmuslerdorf", 'r1'), $crypt->encrypt("rasmuslerdorf", 'r1'));
	    
	    $hash = $crypt->encrypt("rasmuslerdorf");
        $this->assertEquals($hash, $crypt->encrypt("rasmuslerdorf", $hash));		
    }

	/**
	 * Tests Crypt_System->encrypt() using extended DES-based encryption
	 */
	public function testEncrypt_ext_des()
	{
	    if (!CRYPT_EXT_DES) $this->markTestSkipped("Extended DES-based encryption with crypt() not available.");
	    
	    $crypt = new Q\Crypt_System('std_des');
	    
	    $this->assertRegExp('/^.{20}$/', $crypt->encrypt("rasmuslerdorf"));
	    $this->assertEquals(crypt("rasmuslerdorf", '_J9..rasm'), $crypt->encrypt("rasmuslerdorf", '_J9..rasm'));
	    
	    $hash = $crypt->encrypt("rasmuslerdorf");
        $this->assertEquals($hash, $crypt->encrypt("rasmuslerdorf", $hash));		
    }

	/**
	 * Tests Crypt_System->encrypt() using MD5 encryption
	 */
	public function testEncrypt_md5()
	{
	    if (!CRYPT_MD5) $this->markTestSkipped("MD5-based encryption with crypt() not available.");
	    
	    $crypt = new Q\Crypt_System('md5');
	    
	    $this->assertRegExp('/^\$1\$.{31}$/', $crypt->encrypt("rasmuslerdorf"));
	    $this->assertEquals(crypt("rasmuslerdorf", '$1$rasmusle$'), $crypt->encrypt("rasmuslerdorf", '$1$rasmusle$'));
	    
	    $hash = $crypt->encrypt("rasmuslerdorf");
	    $this->assertEquals($hash, $crypt->encrypt("rasmuslerdorf", $hash));		
    }

	/**
	 * Tests Crypt_System->encrypt() using Blowfish encryption
	 */
	public function testEncrypt_blowfish()
	{
	    if (!CRYPT_BLOWFISH) $this->markTestSkipped("Blowfish-based encryption with crypt() not available.");
	    
	    $crypt = new Q\Crypt_System('blowfish');
	    
	    $this->assertRegExp('/^\$2a\$\w{2}\$.{53}$/', $crypt->encrypt("rasmuslerdorf"));
	    $this->assertEquals(crypt("rasmuslerdorf", '$2a$07$rasmuslerd...........$'), $crypt->encrypt("rasmuslerdorf", '$2a$07$rasmuslerd...........$'));
	    
	    $hash = $crypt->encrypt("rasmuslerdorf");
	    $this->assertEquals($hash, $crypt->encrypt("rasmuslerdorf", $hash), "From Hash");		
    }    
}

if (PHPUnit_MAIN_METHOD == 'Test_Crypt_System::main') Test_Crypt_System::main();
?>