<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Crypt/CreationTest.php';
require_once 'Crypt/CRC32Test.php';
require_once 'Crypt/DoubleMD5Test.php';
require_once 'Crypt/MD5Test.php';
require_once 'Crypt/OpenSSLTest.php';
require_once 'Crypt/SystemTest.php';

/**
 * Test suite for Crypt.
 */
class Crypt_AllTests extends PHPUnit_Framework_TestSuite
{

    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName('Crypt_AllTests');
        $this->addTestSuite('Crypt_CreationTest');
        $this->addTestSuite('Crypt_CRC32Test');
        $this->addTestSuite('Crypt_DoubleMD5Test');
        $this->addTestSuite('Crypt_MD5Test');
        $this->addTestSuite('Crypt_OpenSSLTest');
        $this->addTestSuite('Crypt_SystemTest');
    }

    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}
