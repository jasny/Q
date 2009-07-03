<?php
require_once 'Test/Config/Main.php';

require_once 'Q/Config/XML.php';

class Test_Config_XML extends Test_Config_Main
{
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    
	public function testConfigFile()
    {
    	$config = new Q\Config_XML(array('path'=>$this->getPath() . '/test.xml'));
    	$this->setgetTest($config);
    }
    
    public function testConfigSubgrpFile()
    {
    	$config = new Q\Config_XML(array('path'=>$this->getPath() . '/test-subgrp.xml'));
    	$this->assertEquals(array('xyz'=>array('xq'=>10, 'abc'=>array('a'=>'something else', 'tu'=>27, 're'=>10, 'grp1'=>array('i1'=>22, 'we'=>10))), 'd'=>array('abc', 'def', 'ghij', 'klm')), $config->get());
    }
        
    public function testConfigDir()
    {
    	$config = new Q\Config_XML(array('path'=>$this->getPath() . '/test'));

    	$this->assertEquals(array('q'=>'abc', 'b'=>27), $config->get('grp1'));
    	$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $config->get());
    	$this->setgetTest($config);
    }
    
    public function testMapping()
    {
        $config = new Q\Config_XML(array('path'=>$this->getPath() . '/a_test.xml', 'map'=>array('extra'=>'value'), 'mapkey'=>array('table_def'=>"'#table'", 'field'=>'@name', 'alias'=>"'#alias:'.@name")));
        $this->assertEquals(array('#table'=>array('description'=>'Alias', 'filter'=>'status = 1'), 'description'=>array('name'=>'description', 'type'=>'string', 'datatype'=>'alphanumeric', 'description'=>'Name', 'extra'=>'yup'), '#alias:xyz'=>array('name'=>'xyz', 'description'=>'Description XYZ')), $config->get());
    }
}
   
if (PHPUnit_MAIN_METHOD == 'Test_Config_XML::main') Test_Config_XML::main();
?>