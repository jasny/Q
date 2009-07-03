<?php
require_once 'Test/Crypt/Creation.php';
require_once 'Test/Crypt/DoubleMD5.php';
require_once 'Test/Crypt/MD5.php';
require_once 'Test/Crypt/System.php';
/**
 * Static test suite.
 */
class Test_Crypt extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('Test_Crypt');
        $this->addTestSuite('Test_Crypt_Creation');
        $this->addTestSuite('Test_Crypt_DoubleMD5');
        $this->addTestSuite('Test_Crypt_MD5');
        $this->addTestSuite('Test_Crypt_System');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

