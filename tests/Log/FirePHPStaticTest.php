<?php
use Q\Log, Q\Log_FirePHP;

require_once 'TestHelper.php';
require_once 'Q/Log/FirePHP.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Log_FirePHP test case.
 */
class Log_FirePHPStaticTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $user_agent;
    
    /**
     * Reflection of Log_FirePHP::$counter
     *
     * @var ReflectionProperty
     */
    protected $prop_counter;
    
    
    /**
     * Constructs a test case with the given name.
     *
     * @param  string $name
     * @param  array  $data
     * @param  string $dataName
     * @access public
     */
    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        $this->prop_counter = new ReflectionProperty('Q\Log_FirePHP', 'counter');
        $this->prop_counter->setAccessible(true);
        
        parent::__construct($name, $data, $dataName);
    }
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.2) Gecko/2008092313 Ubuntu/8.04 (hardy) Firefox/3.0.2 FirePHP/0.1.2";
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $_SERVER['HTTP_USER_AGENT'] = $this->user_agent;
        parent::tearDown();
    }

    /**
     * Get the current counter of the 
     *
     */
    protected function getCounter()
    {
        return $this->prop_counter->getValue(null);
    }
    
    
    /**
     * Tests Log_FirePHP::setProcessorUrl()
     */
    public function testSetProcessorUrl()
    {
        Log_FirePHP::setProcessorUrl('example.com');
        $this->assertEquals('example.com', Q\HTTP::header_getValue('X-FirePHP-ProcessorURL'));
    }
    /**
     * Tests Log_FirePHP::setRendererUrl()
     */
    public function testSetRendererUrl()
    {
        Log_FirePHP::setRendererUrl('render.example.com');
        $this->assertEquals('render.example.com', Q\HTTP::header_getValue('X-FirePHP-RendererURL'));
    }
    
    /**
     * Tests Log_FirePHP::detectClientExtension()
     */
    public function testDetectClientExtension()
    {
        $this->assertTrue(Log_FirePHP::detectClientExtension());
    }
    

    /**
     * Tests Log_FirePHP::fbLog()
     */
    public function testFbLog()
    {
        Log_FirePHP::fbLog("A test");

        $this->assertEquals('{', Q\HTTP::header_getValue('X-FirePHP-Data-100000000001'));
        $this->assertEquals('"FirePHP.Firebug.Console":[', Q\HTTP::header_getValue('X-FirePHP-Data-300000000001'));
        $this->assertEquals('["LOG",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        $this->assertEquals('["__SKIP__"]],', Q\HTTP::header_getValue('X-FirePHP-Data-499999999999'));
        $this->assertEquals('"__SKIP__":"__SKIP__"}', Q\HTTP::header_getValue('X-FirePHP-Data-999999999999'));
        
        Log_FirePHP::fbLog("Another test", "mylabel");
        $this->assertEquals('["LOG",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
    
    /**
     * Tests Log_FirePHP::fbInfo()
     */
    public function testFbInfo()
    {
        Log_FirePHP::fbInfo("A test");
        $this->assertEquals('["INFO",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Log_FirePHP::fbInfo("Another test", "mylabel");
        $this->assertEquals('["INFO",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    /**
     * Tests Log_FirePHP::fbWarn()
     */
    public function testFbWarn()
    {
        Log_FirePHP::fbWarn("A test");
        $this->assertEquals('["WARN",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Log_FirePHP::fbWarn("Another test", "mylabel");
        $this->assertEquals('["WARN",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    /**
     * Tests Log_FirePHP::fbError()
     */
    public function testFbError()
    {
        Log_FirePHP::fbError("A test");
        $this->assertEquals('["ERROR",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Log_FirePHP::fbError("Another test", "mylabel");
        $this->assertEquals('["ERROR",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
    
    /**
     * Tests Log_FirePHP::fbDump()
     */
    public function testFbDump()
    {
        Log_FirePHP::fbDump('stringTest', "A test");
        $this->assertEquals('"stringTest":' . json_encode('A test') . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Log_FirePHP::fbDump('boolTest', true);
        $this->assertEquals('"boolTest":' . json_encode(true) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Log_FirePHP::fbDump('nullTest', null);
        $this->assertEquals('"nullTest":' . json_encode(null) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Log_FirePHP::fbDump('intTest', 10);
        $this->assertEquals('"intTest":' . json_encode(10) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Log_FirePHP::fbDump('floatTest', 122.53);
        $this->assertEquals('"floatTest":' . json_encode(122.53) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Log_FirePHP::fbDump('arrayTest', array(1, 2, 3));
        $this->assertEquals('"arrayTest":' . json_encode(array(1, 2, 3)) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Log_FirePHP::fbDump('assocTest', array('jan'=>'jansen', 'piet'=>'de baas', 'frank'=>'& vrij'));
        $this->assertEquals('"assocTest":' . json_encode(array('jan'=>'jansen', 'piet'=>'de baas', 'frank'=>'& vrij')) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Log_FirePHP::fbDump('assocTest', (object)array('host'=>'localhost', 'port'=>3360, 'db'=>'mydb'));
        $this->assertEquals('"assocTest":' . json_encode((object)array('host'=>'localhost', 'port'=>3360, 'db'=>'mydb')) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    /**
     * Tests Log_FirePHP::fbTrace()
     */
    public function testFbTrace()
    {
        $this->markTestSkipped("Trace doesn't work well in test case");
        
        Log_FirePHP::fbTrace();
        $this->assertRegExp('/' . preg_quote(__CLASS__, '/') . '/i', Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
    
    /**
     * Tests Log_FirePHP::fbException()
     */
    public function testFbException()
    {
        $this->markTestSkipped("Trace doesn't work well in test case");
        
        $exception = new Exception("Nothing is wrong, just a test", 2233);
        Log_FirePHP::fbException($exception);

        $this->assertRegExp('/' .preg_quote($exception->getMessage(), '/') . '/i', Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        $this->assertRegExp('/' .preg_quote($exception->getCode(), '/') . '/i', Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
    
	/**
     * Tests Log_FirePHP::fbTable()
     */
    public function testFbTable()
    {
        Log_FirePHP::fbTable("2 SQL queries took 0.06 seconds", array(array('SQL Statement', 'Time', 'Result'), array('SELECT * FROM Foo','0.02', array('row1', 'row2')), array('SELECT * FROM Bar','0.04',array('row1', 'row2'))));
        $this->assertEquals('["TABLE",' . json_encode(array('2 SQL queries took 0.06 seconds', array(array('SQL Statement','Time','Result'), array('SELECT * FROM Foo','0.02',array('row1','row2')), array('SELECT * FROM Bar','0.04',array('row1','row2'))))) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    
    /**
     * Tests Log_FirePHP::fb() with type LOG, INFO, etc
     */
    public function testFb_type_LOG()
    {
        Log_FirePHP::fb("A test", Log_FirePHP::LOG);
        $this->assertEquals('["LOG",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Log_FirePHP::fb("Another test", "mylabel", Log_FirePHP::INFO);
        $this->assertEquals('["INFO",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    /**
     * Tests Log_FirePHP::fb() with type DUMP
     */
    public function testFb_type_DUMP()
    {
        Log_FirePHP::fb('arrayTest', array(1, 2, 3), Log_FirePHP::DUMP);
        $this->assertEquals('"arrayTest":' . json_encode(array(1, 2, 3)) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Log_FirePHP::fb(array(1, 2, 3), Log_FirePHP::DUMP);
        $this->assertEquals('"_unknown_":' . json_encode(array(1, 2, 3)) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

	/**
     * Tests Log_FirePHP::fb() with type TABLE
     */
    public function testFb_type_TABLE()
    {
        Log_FirePHP::fb(array(array('SQL Statement', 'Time', 'Result'), array('SELECT * FROM Foo','0.02', array('row1', 'row2')), array('SELECT * FROM Bar','0.04',array('row1', 'row2'))), '2 SQL queries took 0.06 seconds', Log_FirePHP::TABLE);
        $this->assertEquals('["TABLE",' . json_encode(array('2 SQL queries took 0.06 seconds', array(array('SQL Statement','Time','Result'), array('SELECT * FROM Foo','0.02',array('row1','row2')), array('SELECT * FROM Bar','0.04',array('row1','row2'))))) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
}

