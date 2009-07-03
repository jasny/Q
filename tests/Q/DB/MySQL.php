<?php
require_once 'Test/DB/MySQL/Advanced.php';
require_once 'Test/DB/MySQL/Basic.php';
require_once 'Test/DB/MySQL/QuerySplitter.php';
/**
 * Static test suite.
 */
class Test_DB_MySQL extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('Test_DB_MySQL');
        $this->addTestSuite('Test_DB_MySQL_QuerySplitter');
        $this->addTestSuite('Test_DB_MySQL_Basic');
        $this->addTestSuite('Test_DB_MySQL_Advanced');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

