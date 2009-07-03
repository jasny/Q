<?php
require_once 'Test/DB/MySQL.php';
/**
 * Static test suite.
 */
class Test_DB extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('Test_DB');
        $this->addTestSuite('Test_DB_MySQL');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

