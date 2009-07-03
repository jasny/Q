<?php
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
class Test_All extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('Test_All');
        $this->addTestSuite('Test_Authenticate');
        $this->addTestSuite('Test_Config');
        $this->addTestSuite('Test_Crypt');
        $this->addTestSuite('Test_DB');
        $this->addTestSuite('Test_Log');
        $this->addTestSuite('Test_Misc');
        $this->addTestSuite('Test_VariableStream');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

