<?php
namespace Q;

require_once 'Test/Config/MainTest.php';
require_once 'Q/Config/Yaml.php';

class Config_YamlTest extends Config_MainTest
{
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

    public function testConfigFile()
    {
    	$config = new Q\Config_Yaml(array('parser'=>$this->parser, 'path'=>$this->getPath() . '/test.yaml'));
    	$this->setgetTest($config);
    }

    public function testConfigSubgrpFile()
    {
    	$config = new Q\Config_Yaml(array('parser'=>$this->parser, 'path'=>$this->getPath() . '/test-subgrp.yaml'));
    	$this->assertEquals(array('xyz'=>array('xq'=>10, 'abc'=>array('a'=>'something else', 'tu'=>27, 're'=>10, 'grp1'=>array('i1'=>22, 'we'=>10))), 'd'=>array('abc', 'def', 'ghij', 'klm')), $config->get());
    }
        
    public function testConfigPHPFile()
    {
    	$config = new Q\Config_Yaml(array('parser'=>$this->parser, 'path'=>$this->getPath() . '/test-php.yaml', 'php'=>true, 'parameters'=>array('good'=>true)));
    	$this->setgetTest($config);
    }

    public function testConfigPHPFile_Alt()
    {
    	$config = new Q\Config_Yaml(array('parser'=>$this->parser, 'path'=>$this->getPath() . '/test-php.yaml', 'php'=>true));
    	$this->assertEquals(array('q'=>'Not good', 'b'=>27), $config->get('grp1'));
    }
    
    public function testConfigDir()
    {
    	$config = new Q\Config_Yaml(array('parser'=>$this->parser, 'path'=>$this->getPath() . '/test'));
    	
    	$this->assertEquals(array('q'=>'abc', 'b'=>27), $config->get('grp1'));
    	$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $config->get());
    	$this->setgetTest($config);
    }
}
