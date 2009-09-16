<?php
use Q\Crypt_System;

require_once 'TestHelper.php';
require_once 'Q/Crypt/System.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Crypt_System test case.
 */
class Crypt_SystemTest extends PHPUnit_Framework_TestCase
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
	    $crypt = new Crypt_System();
	    
	    $hash = $crypt->encrypt("a test string");
		$this->assertEquals(crypt("a test string", $hash), $hash);
	}

	/**
	 * Tests Crypt_System->encrypt() using standard DES-based encryption
	 */
	public function testEncrypt_std_des()
	{
	    if (!CRYPT_STD_DES) $this->markTestSkipped("Standard DES-based encryption with crypt() not available.");
	    
	    $crypt = new Crypt_System('std_des');
	    
	    $this->assertRegExp('/^.{13}$/', $crypt->encrypt("a test string"));
	    $this->assertEquals(crypt("a test string", '12'), $crypt->encrypt("a test string", '12'));
	    
	    $hash = $crypt->encrypt("a test string");
        $this->assertEquals($hash, $crypt->encrypt("a test string", $hash));		
    }

	/**
	 * Tests Crypt_System->encrypt() using extended DES-based encryption
	 */
	public function testEncrypt_ext_des()
	{
	    if (!CRYPT_EXT_DES) $this->markTestSkipped("Extended DES-based encryption with crypt() not available.");
	    
	    $crypt = new Crypt_System('ext_des');
	    
	    $this->assertRegExp('/^.{20}$/', $crypt->encrypt("a test string"));
	    $this->assertEquals(crypt("a test string", '_23456789'), $crypt->encrypt("a test string", '_23456789'));
	    
	    $hash = $crypt->encrypt("a test string");
        $this->assertEquals($hash, $crypt->encrypt("a test string", $hash));		
    }

	/**
	 * Tests Crypt_System->encrypt() using MD5 encryption
	 */
	public function testEncrypt_md5()
	{
	    if (!CRYPT_MD5) $this->markTestSkipped("MD5-based encryption with crypt() not available.");
	    
	    $crypt = new Crypt_System('md5');
	    
	    $this->assertRegExp('/^\$1\$.{31}$/', $crypt->encrypt("a test string"));
	    $this->assertEquals(crypt("a test string", '$1$12345678'), $crypt->encrypt("a test string", '$1$12345678'));
	    
	    $hash = $crypt->encrypt("a test string");
	    $this->assertEquals($hash, $crypt->encrypt("a test string", $hash));		
    }

	/**
	 * Tests Crypt_System->encrypt() using Blowfish encryption
	 */
	public function testEncrypt_blowfish()
	{
	    if (!CRYPT_BLOWFISH) $this->markTestSkipped("Blowfish-based encryption with crypt() not available.");
	    
	    $crypt = new Crypt_System('blowfish');
	    
	    $this->assertRegExp('/^\$2a\$07\$.{53}$/', $crypt->encrypt("a test string"));
	    $this->assertEquals(crypt("a test string", '$2a$07$1234567890123456789012'), $crypt->encrypt("a test string", '$2a$07$1234567890123456789012'));
	    
	    $hash = $crypt->encrypt("a test string");
	    $this->assertEquals($hash, $crypt->encrypt("a test string", $hash), "From Hash");		
    }

	/**
	 * Tests Crypt_System->encrypt() with a file
	 */
	public function testEncrypt_File()
	{
		$crypt = new Crypt_System();
		
		$file = $this->getMock('Q\Fs_File', array('__toString', 'getContents'));
		$file->expects($this->never())->method('__toString');
		$file->expects($this->once())->method('getContents')->will($this->returnValue("a test string"));
		
	    $hash = $crypt->encrypt($file);
		$this->assertEquals(crypt("a test string", $hash), $hash);
	}
}
