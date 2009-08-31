<?php

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Log/CreationTest.php';
require_once 'Log/EventValuesTest.php';
require_once 'Log/FirePHPTest.php';
require_once 'Log/FirePHPStaticTest.php';
require_once 'Log/FirePHPTableTest.php';
require_once 'Log/HeaderTest.php';
require_once 'Log/MailTest.php';
require_once 'Log/TextTest.php';

/**
 * Static test suite.
 */
class Log_AllTests extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName(__CLASS__);
        $this->addTestSuite('Log_CreationTest');
        $this->addTestSuite('Log_EventValuesTest');
        $this->addTestSuite('Log_FirePHPTest');
        $this->addTestSuite('Log_FirePHPStaticTest');
        $this->addTestSuite('Log_FirePHPTableTest');
        $this->addTestSuite('Log_HeaderTest');
        $this->addTestSuite('Log_MailTest');
        $this->addTestSuite('Log_TextTest');
    }
    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}
