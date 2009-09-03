<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Cache/APCTest.php';
require_once 'Cache/FileTest.php';
require_once 'Cache/VarTest.php';


/**
 * Static test suite for Cache package.
 */
class Cache_AllTests extends PHPUnit_Framework_TestSuite
{

    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName(__CLASS__);
        $this->addTestSuite('Cache_VarTest');
        $this->addTestSuite('Cache_FileTest');
        $this->addTestSuite('Cache_APCTest');        
    }

    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}
