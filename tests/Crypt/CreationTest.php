<?php
use Q\Crypt;

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Q/Crypt.php';

/**
 * Test factory method
 */
class Crypt_CreationTest extends PHPUnit_Framework_TestCase
{
    public function testDriverOnly()
    {
        $crypt = Crypt::with('none');
        $this->assertType('Q\Crypt_None', $crypt);
    }

    public function testMD5Options()
    {
        $crypt = Crypt::with('md5:secret=mysecret', array('use_salt'=>true));
        $this->assertType('Q\Crypt_MD5', $crypt);
        
        $this->assertTrue($crypt->use_salt);
        $this->assertEquals('mysecret', $crypt->secret);
    }
}
