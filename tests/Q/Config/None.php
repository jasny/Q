<?php
require_once 'Test/Config/Main.php';

require_once 'Q/Config/None.php';

class Test_Config_None extends Test_Config_Main
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
    	$config = new Q\Config_None();
    	$config->set(null, array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')));
    	$this->setgetTest($config);
    }
}

if (PHPUnit_MAIN_METHOD == 'Test_Config_None::main') Test_Config_None::main();
?>