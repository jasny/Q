<?php
require_once 'Test/Config/Yaml.php';

class Test_Config_YamlSpyc extends Test_Config_Yaml
{
	protected $parser = 'spyc';
	
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
}
   
if (PHPUnit_MAIN_METHOD == 'Test_Config_YamlSpyc::main') Test_Config_Yaml::main();
?>