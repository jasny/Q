<?php
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
class Test_Log extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('Test_Log');
        $this->addTestSuite('Test_Log_Creation');
        $this->addTestSuite('Test_Log_EventValues');
        $this->addTestSuite('Test_Log_FirePHP');
        $this->addTestSuite('Test_Log_FirePHPStatic');
        $this->addTestSuite('Test_Log_FirePHPTable');
        $this->addTestSuite('Test_Log_Header');
        $this->addTestSuite('Test_Log_Mail');
        $this->addTestSuite('Test_Log_Text');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

