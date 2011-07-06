<?php
namespace Q;

require_once 'Q/DB/MySQL/AllTests.php';

/**
 * Static test suite.
 */
class DB_AllTests extends \PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName(__CLASS__);
        $this->addTestSuite('Q\DB_MySQL_AllTests');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

