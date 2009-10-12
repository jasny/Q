<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Transform/Test.php';
require_once 'Transform/ReplaceTest.php';
require_once 'Transform/XSLTest.php';
require_once 'Transform/PHPTest.php';
require_once 'Transform/Text2HTMLTest.php';
require_once 'Transform/HTML2TextTest.php';
require_once 'Transform/Serialize/XMLTest.php';
require_once 'Transform/Unserialize/XMLTest.php';
require_once 'Transform/Serialize/PHPTest.php';
require_once 'Transform/Unserialize/PHPTest.php';
require_once 'Transform/Serialize/JsonTest.php';
require_once 'Transform/Unserialize/JsonTest.php';
require_once 'Transform/Serialize/YamlTest.php';
require_once 'Transform/Unserialize/YamlTest.php';
require_once 'Transform/Serialize/IniTest.php';
require_once 'Transform/Unserialize/IniTest.php';

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
        $this->addTestSuite('Transform_Test');
        $this->addTestSuite('Transform_ReplaceTest');
        $this->addTestSuite('Transform_XSLTest');
        $this->addTestSuite('Transform_PHPTest');
        $this->addTestSuite('Transform_Text2HTMLTest');
        $this->addTestSuite('Transform_HTML2TextTest');
        $this->addTestSuite('Transform_Unserialize_XMLTest');
        $this->addTestSuite('Transform_Serialize_XMLTest');
        $this->addTestSuite('Transform_Unserialize_PHPTest');
        $this->addTestSuite('Transform_Serialize_PHPTest');
        $this->addTestSuite('Transform_Unserialize_JsonTest');
        $this->addTestSuite('Transform_Serialize_JsonTest');
        $this->addTestSuite('Transform_Unserialize_YamlTest');
        $this->addTestSuite('Transform_Serialize_YamlTest');
        $this->addTestSuite('Transform_Unserialize_IniTest');
        $this->addTestSuite('Transform_Serialize_IniTest');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}

