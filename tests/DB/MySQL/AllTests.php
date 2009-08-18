<?php
namespace Q;

require_once 'Q/DB/MySQL/SQLSplitterTest.php';
require_once 'Q/DB/MySQL/BasicTest.php';
require_once 'Q/DB/MySQL/AdvancedTest.php';

/**
 * Static test suite.
 */
class DB_MySQL_AllTests extends \PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName(__CLASS__);
        $this->addTestSuite('Q\DB_MySQL_SQLSplitterTest');
        $this->addTestSuite('Q\DB_MySQL_BasicTest');
        $this->addTestSuite('Q\DB_MySQL_AdvancedTest');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

