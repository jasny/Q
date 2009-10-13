<?php
use Q\Config, Q\Config_Dir;

require_once 'TestHelper.php';
require_once 'Q/Config/Dir.php';

class Config_DirTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->dir = sys_get_temp_dir() . '/q-config_dirtest-' . md5(uniqid());
        $this->file[0] = sys_get_temp_dir() . '/q-config_dirtest_f0-' . md5(uniqid());
        $this->file[1] = sys_get_temp_dir() . '/q-config_dirtest_f1-' . md5(uniqid());
        $this->subdir = sys_get_temp_dir() . '/q-config_dirtest_subdir-' . md5(uniqid());
        $this->file['subdir'] = sys_get_temp_dir() . '/q-config_dirtest_fsubdir-' . md5(uniqid());
        
        if (!mkdir($this->dir)) $this->markTestSkipped("Could not create '{$this->dir}'.");        
        if (!file_put_contents($this->file[0], '<?xml version="1.0" encoding="UTF-8"?>
<settings>
    <grp1>
        <q>abc</q>
        <b>27</b>
    </grp1>
    <grp2>
        <a>original</a>
    </grp2>
</settings>')) $this->markTestSkipped("Could not write to '{$this->file[0]}'.");
        if (!file_put_contents($this->file[1], '[TABLE_DEF]
overview = "SELECT * FROM phpunit_child"

[test_id]
datatype = parentkey
foreign_table = phpunit_test')) $this->markTestSkipped("Could not write to '{$this->file[1]}'.");
        if (!mkdir($this->subdir)) $this->markTestSkipped("Could not create '{$this->subdir}'.");        
        if (!file_put_contents($this->file['subdir'], 'xyz:
   xq  : 10
   abc :
      a  : something else
      tu : 27


d:
   - abc
   - klm')) $this->markTestSkipped("Could not write to '{$this->file['subdir']}'.");        
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->cleanup($this->dir);
        $this->cleanup($this->file[0]);
        $this->cleanup($this->file[1]);
        $this->cleanup($this->subdir);
        $this->cleanup($this->file['subdir']);
    }

    /**
     * Remove tmp files (recursively)
     * 
     * @param string $path
     */
    protected static function cleanup($path)
    {
        foreach (array('', '.orig', '.x', '.y') as $suffix) {
            if (is_dir($path . $suffix) && !is_link($path . $suffix)) {
                static::cleanup($path . $suffix . '/' . basename($path));
                if (!rmdir($path . $suffix)) throw new Exception("Cleanup failed");
            } elseif (file_exists($path . $suffix) || is_link($path . $suffix)) {
                unlink($path . $suffix);
            }
        }
    } 

    
	public function testConfigDir()
    {
    	$config = new Config_Dir(array('driver'=>'xml', 'path'=>$this->dir));
    	
        $rootNode = pathinfo($this->file[0], PATHINFO_FILENAME);
$config[$rootNode]['test'] = 10;
var_dump((array)$config);
        $this->assertType('Q\Config_Dir', $config);        
//    	$this->assertEquals('abc', $config[$rootNode]['grp1']['q']);
    }
    
    
/*    public function testConfigSubgrpFile()
    {
    	$config = new Config_File(array('path'=>$this->getPath() . '/test-subgrp.xml'));
    	$this->assertEquals(array('xyz'=>array('xq'=>10, 'abc'=>array('a'=>'something else', 'tu'=>27, 're'=>10, 'grp1'=>array('i1'=>22, 'we'=>10))), 'd'=>array('abc', 'def', 'ghij', 'klm')), $config->get());
    }
        
    public function testConfigDir()
    {
    	$config = new Config_File(array('path'=>$this->getPath() . '/test'));

    	$this->assertEquals(array('q'=>'abc', 'b'=>27), $config->get('grp1'));
    	$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $config->get());
    	$this->setgetTest($config);
    }
    
    public function testMapping()
    {
        $config = new Config_File(array('path'=>$this->getPath() . '/a_test.xml', 'map'=>array('extra'=>'value'), 'mapkey'=>array('table_def'=>"'#table'", 'field'=>'@name', 'alias'=>"'#alias:'.@name")));
        $this->assertEquals(array('#table'=>array('description'=>'Alias', 'filter'=>'status = 1'), 'description'=>array('name'=>'description', 'type'=>'string', 'datatype'=>'alphanumeric', 'description'=>'Name', 'extra'=>'yup'), '#alias:xyz'=>array('name'=>'xyz', 'description'=>'Description XYZ')), $config->get());
    }
*/
}
