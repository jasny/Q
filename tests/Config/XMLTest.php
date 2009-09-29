<?php
namespace Q;

require_once dirname(__FILE__) . '/MainTest.php';
require_once 'Q/Config/XML.php';

class Config_XMLTest extends Config_MainTest
{
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    
	public function testConfigFile()
    {
    	$config = new Config_XML(array('path'=>$this->getPath() . '/test.xml'));
    	$this->setgetTest($config);
    }
    
    public function testConfigSubgrpFile()
    {
    	$config = new Config_XML(array('path'=>$this->getPath() . '/test-subgrp.xml'));
    	$this->assertEquals(array('xyz'=>array('xq'=>10, 'abc'=>array('a'=>'something else', 'tu'=>27, 're'=>10, 'grp1'=>array('i1'=>22, 'we'=>10))), 'd'=>array('abc', 'def', 'ghij', 'klm')), $config->get());
    }
        
    public function testConfigDir()
    {
    	$config = new Config_XML(array('path'=>$this->getPath() . '/test'));

    	$this->assertEquals(array('q'=>'abc', 'b'=>27), $config->get('grp1'));
    	$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $config->get());
    	$this->setgetTest($config);
    }
    
    public function testMapping()
    {
        $config = new Config_XML(array('path'=>$this->getPath() . '/a_test.xml', 'map'=>array('extra'=>'value'), 'mapkey'=>array('table_def'=>"'#table'", 'field'=>'@name', 'alias'=>"'#alias:'.@name")));
        $this->assertEquals(array('#table'=>array('description'=>'Alias', 'filter'=>'status = 1'), 'description'=>array('name'=>'description', 'type'=>'string', 'datatype'=>'alphanumeric', 'description'=>'Name', 'extra'=>'yup'), '#alias:xyz'=>array('name'=>'xyz', 'description'=>'Description XYZ')), $config->get());
    }
}
   
if (PHPUnit_MAIN_METHOD == 'Config_XMLTest::main') Config_XMLTest::main();
