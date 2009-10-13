<?php
use Q\Config, Q\Config_File, Q\Fs, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Config/File.php';

class Config_FileTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Transform::$drivers['from-mock'] = 'Config_FileTest_Mock_Unserialize';
    }

    public function tearDown()
    {
        Config_FileTest_Mock_Unserialize:$created = array();
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

class Config_FileTest_Mock_Unserialize extends Transform
{
    /**
     * Created transform mock objects
     * @var array
     */
    static public $created = array();
    
    /**
     * Input data process
     * @var mixed
     */
    public $in;
    
    /**
     * Return data
     * @var array
     */
    public $out = array(
      'db' => array(
        'host'   => 'localhost',
        'dbname' => 'test',
        'user'   => 'myuser',
        'pwd'    => 'mypwd'
      ),
      'output' => 'xml',
      'input'  => 'json'
    );
    
    /**
     * Class constructor
     */
    public function __construct($options=array())
    {
        self::$created[] = $this;
    }
    
    /**
     * Transform
     * 
     * @param mixed $data
     * @return array
     */
    public function process($data)
    {
        $this->in = $data;
        return $this->out;
    }
}