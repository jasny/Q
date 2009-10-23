<?php
use Q\Config, Q\Config_File, Q\Fs, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Config/File.php';
require_once 'Config/Mock/Unserialize.php';

class Config_FileTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Transform::$drivers['from-mock'] = 'Config_FileTest_Mock_Unserialize';
    }

    public function tearDown()
    {
        Config_FileTest_Mock_Unserialize:$created = array();
        unset(Transform::$drivers['from-mock']);
    }
        
	public function test_withTranformer()
    {
        $filename = '/tmp/' . md5(uniqid());
        $mock = new Config_FileTest_Mock_Unserialize();
        
    	$config = new Config_File(array($filename, 'transformer'=>$mock));
    	
        $this->assertType('Q\Fs_File', $mock->in);
        $this->assertEquals($filename, (string)$mock->in);
        
        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($mock->out, (array)$config);
            	
    	$this->assertEquals(1, count(Config_FileTest_Mock_Unserialize::$created));
    }
    
    public function test_onFilename()
    {
        $filename = '/tmp/' . md5(uniqid()) . '.mock';
        $config = new Config_File($filename);
        
        $this->assertArrayHasKey(0, Config_FileTest_Mock_Unserialize::$created);
        $mock = Config_FileTest_Mock_Unserialize::$created[0];
        
        $this->assertType('Q\Fs_File', $mock->in);
        $this->assertEquals($filename, (string)$mock->in);
        
        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($mock->out, (array)$config);
        
        $this->assertEquals(1, count(Config_FileTest_Mock_Unserialize::$created));
    }
    
}
