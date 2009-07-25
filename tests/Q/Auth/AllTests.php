<?php
namespace Q;

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Test/Authenticate/DB.php';
require_once 'Test/Authenticate/Manual.php';

/**
 * Static test suite.
 */
class AuthenticateTest extends \PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('AuthenticateTest');
        $this->addTestSuite('Authenticate_ManualTest');
        $this->addTestSuite('Authenticate_DBTest');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

