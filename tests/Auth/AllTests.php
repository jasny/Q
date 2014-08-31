<?php

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Auth/DBTest.php';
require_once 'Auth/ManualTest.php';

/**
 * Static test suite.
 */
class Auth_AllTests extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName(__CLASS__);
        $this->addTestSuite('Auth_ManualTest');
        $this->addTestSuite('Auth_DBTest');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}
