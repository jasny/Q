<?php
namespace Q;

require_once 'Test/Config/Main.php';
require_once 'Q/Config/Ini.php';

class Config_IniTest extends Config_MainTest
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
   
if (PHPUnit_MAIN_METHOD == 'Config_IniTest::main') Config_IniTest::main();
