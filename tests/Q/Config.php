<?php
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Test/Config/Creation.php';
require_once 'Test/Config/None.php';
require_once 'Test/Config/Ini.php';
require_once 'Test/Config/Json.php';
require_once 'Test/Config/XML.php';
require_once 'Test/Config/YamlSpyc.php';
require_once 'Test/Config/YamlSyck.php';
/**
 * Static test suite.
 */
class Test_Config extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('Test_Config');
        $this->addTestSuite('Test_Config_Creation');
        $this->addTestSuite('Test_Config_None');
        $this->addTestSuite('Test_Config_Ini');
        $this->addTestSuite('Test_Config_Json');
        $this->addTestSuite('Test_Config_XML');
        $this->addTestSuite('Test_Config_YamlSpyc');
        $this->addTestSuite('Test_Config_YamlSyck');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

