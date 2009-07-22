<?php
namespace Q;

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Test/Log/Creation.php';
require_once 'Test/Log/EventValues.php';
require_once 'Test/Log/FirePHP.php';
require_once 'Test/Log/FirePHPStatic.php';
require_once 'Test/Log/FirePHPTable.php';
require_once 'Test/Log/Header.php';
require_once 'Test/Log/Mail.php';
require_once 'Test/Log/Text.php';

/**
 * Static test suite.
 */
class LogTest extends \PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('LogTest');
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
    public static function suite ()
    {
        return new self();
    }
}

