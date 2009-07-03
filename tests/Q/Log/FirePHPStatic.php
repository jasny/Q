<?php
require_once __DIR__ . '/../init.inc';
require_once 'Q/Log/FirePHP.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Log_FirePHP test case.
 */
class Test_Log_FirePHPStatic extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $user_agent;
    
    /**
     * Reflection of Q\Log_FirePHP::$counter
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
        Q\Log_FirePHP::setProcessorUrl('example.com');
        $this->assertEquals('example.com', Q\HTTP::header_getValue('X-FirePHP-ProcessorURL'));
    }
    /**
     * Tests Log_FirePHP::setRendererUrl()
     */
    public function testSetRendererUrl()
    {
        Q\Log_FirePHP::setRendererUrl('render.example.com');
        $this->assertEquals('render.example.com', Q\HTTP::header_getValue('X-FirePHP-RendererURL'));
    }
    
    /**
     * Tests Log_FirePHP::detectClientExtension()
     */
    public function testDetectClientExtension()
    {
        $this->assertTrue(Q\Log_FirePHP::detectClientExtension());
    }
    

    /**
     * Tests Log_FirePHP::fbLog()
     */
    public function testFbLog()
    {
        Q\Log_FirePHP::fbLog("A test");

        $this->assertEquals('{', Q\HTTP::header_getValue('X-FirePHP-Data-100000000001'));
        $this->assertEquals('"FirePHP.Firebug.Console":[', Q\HTTP::header_getValue('X-FirePHP-Data-300000000001'));
        $this->assertEquals('["LOG",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        $this->assertEquals('["__SKIP__"]],', Q\HTTP::header_getValue('X-FirePHP-Data-499999999999'));
        $this->assertEquals('"__SKIP__":"__SKIP__"}', Q\HTTP::header_getValue('X-FirePHP-Data-999999999999'));
        
        Q\Log_FirePHP::fbLog("Another test", "mylabel");
        $this->assertEquals('["LOG",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
    
    /**
     * Tests Log_FirePHP::fbInfo()
     */
    public function testFbInfo()
    {
        Q\Log_FirePHP::fbInfo("A test");
        $this->assertEquals('["INFO",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Q\Log_FirePHP::fbInfo("Another test", "mylabel");
        $this->assertEquals('["INFO",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    /**
     * Tests Log_FirePHP::fbWarn()
     */
    public function testFbWarn()
    {
        Q\Log_FirePHP::fbWarn("A test");
        $this->assertEquals('["WARN",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Q\Log_FirePHP::fbWarn("Another test", "mylabel");
        $this->assertEquals('["WARN",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    /**
     * Tests Log_FirePHP::fbError()
     */
    public function testFbError()
    {
        Q\Log_FirePHP::fbError("A test");
        $this->assertEquals('["ERROR",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Q\Log_FirePHP::fbError("Another test", "mylabel");
        $this->assertEquals('["ERROR",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
    
    /**
     * Tests Log_FirePHP::fbDump()
     */
    public function testFbDump()
    {
        Q\Log_FirePHP::fbDump('test_string', "A test");
        $this->assertEquals('"test_string":' . json_encode('A test') . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Q\Log_FirePHP::fbDump('test_bool', true);
        $this->assertEquals('"test_bool":' . json_encode(true) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Q\Log_FirePHP::fbDump('test_null', null);
        $this->assertEquals('"test_null":' . json_encode(null) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Q\Log_FirePHP::fbDump('test_int', 10);
        $this->assertEquals('"test_int":' . json_encode(10) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Q\Log_FirePHP::fbDump('test_float', 122.53);
        $this->assertEquals('"test_float":' . json_encode(122.53) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Q\Log_FirePHP::fbDump('test_array', array(1, 2, 3));
        $this->assertEquals('"test_array":' . json_encode(array(1, 2, 3)) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Q\Log_FirePHP::fbDump('test_assoc', array('jan'=>'jansen', 'piet'=>'de baas', 'frank'=>'& vrij'));
        $this->assertEquals('"test_assoc":' . json_encode(array('jan'=>'jansen', 'piet'=>'de baas', 'frank'=>'& vrij')) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Q\Log_FirePHP::fbDump('test_assoc', (object)array('host'=>'localhost', 'port'=>3360, 'db'=>'mydb'));
        $this->assertEquals('"test_assoc":' . json_encode((object)array('host'=>'localhost', 'port'=>3360, 'db'=>'mydb')) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    /**
     * Tests Log_FirePHP::fbTrace()
     */
    public function testFbTrace()
    {
        $this->markTestSkipped("Trace doesn't work well in test case");
        
        Q\Log_FirePHP::fbTrace();
        $this->assertRegExp('/' . preg_quote(__CLASS__, '/') . '/i', Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
    
    /**
     * Tests Log_FirePHP::fbException()
     */
    public function testFbException()
    {
        $this->markTestSkipped("Trace doesn't work well in test case");
        
        $exception = new Exception("Nothing is wrong, just a test", 2233);
        Q\Log_FirePHP::fbException($exception);

        $this->assertRegExp('/' .preg_quote($exception->getMessage(), '/') . '/i', Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        $this->assertRegExp('/' .preg_quote($exception->getCode(), '/') . '/i', Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
    
	/**
     * Tests Log_FirePHP::fbTable()
     */
    public function testFbTable()
    {
        Q\Log_FirePHP::fbTable("2 SQL queries took 0.06 seconds", array(array('SQL Statement', 'Time', 'Result'), array('SELECT * FROM Foo','0.02', array('row1', 'row2')), array('SELECT * FROM Bar','0.04',array('row1', 'row2'))));
        $this->assertEquals('["TABLE",' . json_encode(array('2 SQL queries took 0.06 seconds', array(array('SQL Statement','Time','Result'), array('SELECT * FROM Foo','0.02',array('row1','row2')), array('SELECT * FROM Bar','0.04',array('row1','row2'))))) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    
    /**
     * Tests Log_FirePHP::fb() with type LOG, INFO, etc
     */
    public function testFb_type_LOG()
    {
        Q\Log_FirePHP::fb("A test", Q\Log_FirePHP::LOG);
        $this->assertEquals('["LOG",' . json_encode('A test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
        
        Q\Log_FirePHP::fb("Another test", "mylabel", Q\Log_FirePHP::INFO);
        $this->assertEquals('["INFO",' . json_encode(array("mylabel", "Another test")) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

    /**
     * Tests Log_FirePHP::fb() with type DUMP
     */
    public function testFb_type_DUMP()
    {
        Q\Log_FirePHP::fb('test_array', array(1, 2, 3), Q\Log_FirePHP::DUMP);
        $this->assertEquals('"test_array":' . json_encode(array(1, 2, 3)) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));

        Q\Log_FirePHP::fb(array(1, 2, 3), Q\Log_FirePHP::DUMP);
        $this->assertEquals('"_unknown_":' . json_encode(array(1, 2, 3)) . ',', Q\HTTP::header_getValue('X-FirePHP-Data-2' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

	/**
     * Tests Log_FirePHP::fb() with type TABLE
     */
    public function testFb_type_TABLE()
    {
        Q\Log_FirePHP::fb(array(array('SQL Statement', 'Time', 'Result'), array('SELECT * FROM Foo','0.02', array('row1', 'row2')), array('SELECT * FROM Bar','0.04',array('row1', 'row2'))), '2 SQL queries took 0.06 seconds', Q\Log_FirePHP::TABLE);
        $this->assertEquals('["TABLE",' . json_encode(array('2 SQL queries took 0.06 seconds', array(array('SQL Statement','Time','Result'), array('SELECT * FROM Foo','0.02',array('row1','row2')), array('SELECT * FROM Bar','0.04',array('row1','row2'))))) . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
}

?>