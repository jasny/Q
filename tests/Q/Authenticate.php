<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Test/Authenticate/DB.php';
require_once 'Test/Authenticate/Manual.php';
/**
 * Static test suite.
 */
class Test_Authenticate extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('Test_Authenticate');
        $this->addTestSuite('Test_Authenticate_Manual');
        $this->addTestSuite('Test_Authenticate_DB');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

