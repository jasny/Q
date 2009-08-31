<?php
use Q\Log_EventValues;

require_once 'TestHelper.php';
require_once 'Q/Log/EventValues.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Log_EventValues test case.
 */
class Log_EventValuesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Log_EventValues
     */
    private $Log_EventValues;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        Log_EventValues::initVars();
        $this->Log_EventValues = new Log_EventValues(array('abc'=>10, 'klm'=>"test", 'xyz'=>true));
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Log_EventValues = null;
        parent::tearDown();
    }
    
    
    /**
     * Tests Log_EventValues->getValue()
     */
    public function testGetValue()
    {
        $this->assertEquals("test", $this->Log_EventValues->getValue('klm'));
        $this->assertEquals(10, $this->Log_EventValues->getValue('abc'));
        $this->assertNull($this->Log_EventValues->getValue('not_me'));
    }

    /**
     * Tests Log_EventValues->getAll()
     */
    public function testGetAll()
    {
        $this->assertEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true), $this->Log_EventValues->getAll());
    }

    
    /**
     * Tests Log_EventValues::setVar()
     */
    public function testSetVar()
    {
        $var = 100;
        Log_EventValues::$vars['test'] =& $var;
        $prop = new ReflectionProperty('Q\Log_EventValues', 'vars');
        $prop->setAccessible(true);
        
        $vars = $prop->getValue(null);
        $this->assertEquals(100, $vars['test']);
        
        $var = 250;
        $vars = $prop->getValue(null);
        $this->assertEquals(250, $vars['test']);
    }

    /**
     * Tests Log_EventValues::setVar() in combination with Log_EventValues::getValue()
     */
    public function testSetVar_getValue()
    {
        $var = 100;
        Log_EventValues::$vars['test'] =& $var;
        $this->assertEquals(100, $this->Log_EventValues->getValue('test'));
        
        $var = 250;
        $this->assertEquals(250, $this->Log_EventValues->getValue('test'));
    }
    
    /**
     * Tests Log_EventValues->useVar()
     */
    public function testUseVar()
    {
        $var = 100;
        Log_EventValues::$vars['test'] =& $var;
        Log_EventValues::$vars['yatest'] = "hello";
        
        $this->Log_EventValues->useVar('test');
        $this->assertEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true, 'test'=>100), (array)$this->Log_EventValues);
        
        $var = 250;
        $this->assertEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true, 'test'=>250), (array)$this->Log_EventValues);

        $this->Log_EventValues->useVar('mytest', 'yatest');
        $this->assertEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true, 'test'=>250, 'mytest'=>"hello"), (array)$this->Log_EventValues);
    }
    
    
    /**
     * Tests using a closure with Test::getValue().
     */
    public function testClosure_getValue()
    {
        Log_EventValues::$vars['test'] = function () { return 100; };
        $this->assertEquals(100, $this->Log_EventValues->getValue('test'));
    }
    
    /**
     * Tests using a closure with Test::getAll().
     */
    public function testClosure_getAll()
    {
        Log_EventValues::$vars['test'] = function () { return 100; };
        $this->assertEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true, 'test'=>100), $this->Log_EventValues->getAll());
    }
    
    /**
     * Tests Log_EventValues initial variables
     */
    public function testInitVars()
    {
        $this->assertEquals(phpversion(), $this->Log_EventValues->getValue('phpversion'));
        $this->assertEquals(getmypid(), $this->Log_EventValues->getValue('system-pid'));
        $this->assertRegExp('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $this->Log_EventValues->getValue('time'));
    }
}
