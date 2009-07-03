<?php
require_once 'Test/Config/Main.php';

require_once 'Q/Config/Ini.php';

class Test_Config_Ini extends Test_Config_Main
{
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    
	public function testConfigFile()
    {
    	$config = new Q\Config_Ini(array('path'=>$this->getPath() . '/test.ini'));
    	$this->setgetTest($config);
    }
    
	public function testConfigDir()
    {
    	$config = new Q\Config_Ini(array('path'=>$this->getPath() . '/test'));

    	$this->assertEquals(array('q'=>'abc', 'b'=>27), $config->get('grp1'));
    	$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $config->get());
    	$this->setgetTest($config);
    }
}
   
if (PHPUnit_MAIN_METHOD == 'Test_Config_Ini::main') Test_Config_Ini::main();
?>