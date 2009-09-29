<?php
namespace Q;

require_once dirname(__FILE__) . '/MainTest.php';
require_once 'Q/Config/None.php';

class Config_NoneTest extends Config_MainTest
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    	
	public function testConfigEmpty()
    {
    	$config = new Config_None();
    	$config->set(null, array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')));
    	$this->setgetTest($config);
    }
}

if (PHPUnit_MAIN_METHOD == 'Config_NoneTest::main') Config_NoneTest::main();
