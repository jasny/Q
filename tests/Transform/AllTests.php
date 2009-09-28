<?php

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Transform/CreationTest.php';
require_once 'Transform/ReplaceTest.php';
require_once 'Transform/XSLTest.php';
require_once 'Transform/PHPTest.php';
require_once 'Transform/Array2XMLTest.php';
require_once 'Transform/XML2ArrayTest.php';
require_once 'Transform/Text2HTMLTest.php';

/**
 * Static test suite.
 */
class TransformTest extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName('TransformTest');
        $this->addTestSuite('Transform_CreationTest');
        $this->addTestSuite('Transform_ReplaceTest');
        $this->addTestSuite('Transform_XSLTest');
        $this->addTestSuite('Transform_PHPTest');
		$this->addTestSuite('Transform_Array2XMLTest');
        $this->addTestSuite('Transform_XML2ArrayTest');
        $this->addTestSuite('Transform_Text2HTMLTest');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}

