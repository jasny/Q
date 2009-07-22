<?php
namespace Q;

require_once 'Test/Crypt/Creation.php';
require_once 'Test/Crypt/DoubleMD5.php';
require_once 'Test/Crypt/MD5.php';
require_once 'Test/Crypt/System.php';

/**
 * Static test suite.
 */
class CryptTest extends \PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('CryptTest');
        $this->addTestSuite('Crypt_CreationTest');
        $this->addTestSuite('Crypt_DoubleMD5Test');
        $this->addTestSuite('Crypt_MD5Test');
        $this->addTestSuite('Crypt_SystemTest');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

