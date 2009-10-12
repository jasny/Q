<?php
use Q\Config, Q\Config_File;

require_once 'TestHelper.php';
require_once 'Q/Config/File.php';

class Config_FileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-config_filetest-' . md5(uniqid()) .".xml";
        if (!file_put_contents($this->file, '<?xml version="1.0" encoding="UTF-8"?>
<settings>
    <grp1>
        <q>abc</q>
        <b>27</b>
    </grp1>
    <grp2>
        <a>original</a>
    </grp2>
</settings>')) $this->markTestSkipped("Could not write to '{$this->file}'.");
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->cleanup($this->file);
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

    
	public function testConfigFile()
    {
    	$config = new Config_File(array('path'=>$this->file));

    	$slashpos = strrchr((string)$this->file, '/');
        $rootNode = $slashpos ? substr((string)$slashpos, 1) : (string)$this->file;
        $extpos = strrchr((string)$rootNode, '.');
        $rootNode = substr($rootNode, 0, $extpos ? -strlen($extpos) : strlen($rootNode));
        
    	$this->assertEquals('abc', $config[$rootNode]['grp1']['q']);
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
