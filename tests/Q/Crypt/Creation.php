<?php
require_once __DIR__ . '/../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Crypt.php';

/**
 * Test factory method
 */
class Test_Crypt_Creation extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    public function testDriverOnly()
    {
        $crypt = Q\Crypt::with('none');
        $this->assertType('Q\Crypt_None', $crypt);
    }

    public function testMD5Options()
    {
        $crypt = Q\Crypt::with('md5:secret=mysecret', array('use_salt'=>true));
        $this->assertType('Q\Crypt_MD5', $crypt);
        
        $this->assertTrue($crypt->use_salt);
        $this->assertEquals('mysecret', $crypt->secret);
    }
}
?>