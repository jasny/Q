<?php
require_once 'Test/Config/Main.php';

require_once 'Q/Config/Json.php';

class Test_Config_Json extends Test_Config_Main
{
	/**
	 * Overwrite by child class
	 * @var string
	 */
	protected $parser;
	
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    public function testConfigFile()
    {
    	$config = new Q\Config_Json(array('path'=>$this->getPath() . '/test.json'));
    	$this->setgetTest($config);
    }

    public function testConfigSubgrpFile()
    {
    	$config = new Q\Config_Json(array('path'=>$this->getPath() . '/test-subgrp.json'));
    	$this->assertEquals(array('xyz'=>array('xq'=>10, 'abc'=>array('a'=>'something else', 'tu'=>27, 're'=>10, 'grp1'=>array('i1'=>22, 'we'=>10))), 'd'=>array('abc', 'def', 'ghij', 'klm')), $config->get());
    }
        
    public function testConfigPHPFile()
    {
    	$config = new Q\Config_Json(array('path'=>$this->getPath() . '/test-php.json', 'php'=>true, 'parameters'=>array('good'=>true)));
    	$this->setgetTest($config);
    }

    public function testConfigPHPFile_Alt()
    {
    	$config = new Q\Config_Json(array('path'=>$this->getPath() . '/test-php.json', 'php'=>true));
    	$this->assertEquals(array('q'=>'Not good', 'b'=>27), $config->get('grp1'));
    }
    
    public function testConfigDir()
    {
    	$config = new Q\Config_Json(array('path'=>$this->getPath() . '/test'));
    	
    	$this->assertEquals(array('q'=>'abc', 'b'=>27), $config->get('grp1'));
    	$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $config->get());
    	$this->setgetTest($config);
    }
}
?>