<?php
namespace Q;

require_once 'Test/Authenticate.php';
require_once 'Test/Config.php';
require_once 'Test/Crypt.php';
require_once 'Test/DB.php';
require_once 'Test/Log.php';
require_once 'Test/Misc.php';
require_once 'Test/VariableStream.php';

/**
 * Static test suite.
 */
class AllTest extends \PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('AllTest');
        $this->addTestSuite('AuthenticateTest');
        $this->addTestSuite('ConfigTest');
        $this->addTestSuite('CryptTest');
        $this->addTestSuite('DBTest');
        $this->addTestSuite('LogTest');
        $this->addTestSuite('MiscTest');
        $this->addTestSuite('VariableStreamTest');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

