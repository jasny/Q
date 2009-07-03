<?php
require_once 'Test/Config/Yaml.php';

class Test_Config_YamlSyck extends Test_Config_Yaml
{
	protected $parser = 'syck';
	
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    public function setUp()
    {
    	if (!extension_loaded('syck')) $this->markTestSkipped("Extension 'syck' is not available.");
    }
}
   
if (PHPUnit_MAIN_METHOD == 'Test_Config_YamlSyck::main') Test_Config_Yaml::main();
?>