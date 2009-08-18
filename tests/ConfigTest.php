<?php
namespace Q;

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
class ConfigTest extends \PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('ConfigTest');
        $this->addTestSuite('Config_CreationTest');
        $this->addTestSuite('Config_NoneTest');
        $this->addTestSuite('Config_IniTest');
        $this->addTestSuite('Config_JsonTest');
        $this->addTestSuite('Config_XMLTest');
        $this->addTestSuite('Config_YamlSpycTest');
        $this->addTestSuite('Config_YamlSyckTest');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

