<?php
require_once __DIR__ . '/../init.inc';
require_once 'Q/Log/EventValues.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Log_EventValues test case.
 */
class Test_Log_EventValues extends PHPUnit_Framework_TestCase
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
        $this->Log_EventValues = new Q\Log_EventValues(array('abc'=>10, 'klm'=>"test", 'xyz'=>true));
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
     * Tests Log_EventValues->__construct()
     */
    public function test__construct()
    {
        $this->assertAttributeEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true), 'values', $this->Log_EventValues);
    }
    
    
    /**
     * Tests Log_EventValues->setValue()
     */
    public function testSetValue()
    {
        $this->Log_EventValues->setValue('abc', 100);
        $this->Log_EventValues->setValue('qqq', "hello");
        $this->assertAttributeEquals(array('abc'=>100, 'klm'=>"test", 'xyz'=>true, "qqq"=>"hello"), 'values', $this->Log_EventValues);
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
     * Tests Log_EventValues->count()
     */
    public function testCount()
    {
        $this->assertEquals(3, $this->Log_EventValues->count());
        $this->assertEquals(3, count($this->Log_EventValues), "Using countable");
        
        $this->Log_EventValues->setValue('qqq', 1000);
        $this->assertEquals(4, $this->Log_EventValues->count());
    }
    

    /**
     * Tests Iterator methods of Log_EventValues
     */
    public function testIteratorMethods()
    {
        $this->assertEquals('abc', $this->Log_EventValues->key());
        $this->assertEquals(10, $this->Log_EventValues->current());
        $this->assertTrue($this->Log_EventValues->valid(), 'valid');
        
        $this->Log_EventValues->next();
        $this->assertEquals('klm', $this->Log_EventValues->key());
        $this->assertEquals("test", $this->Log_EventValues->current());
        
        $this->Log_EventValues->next();
        $this->Log_EventValues->next();
        $this->assertFalse($this->Log_EventValues->valid(), 'valid @ end');
        $this->assertNull($this->Log_EventValues->key(), 'key @ end');
        $this->assertFalse($this->Log_EventValues->current(), 'current @ end');
        
        $this->Log_EventValues->rewind();
        $this->assertEquals('abc', $this->Log_EventValues->key());
        $this->assertEquals(10, $this->Log_EventValues->current());
    }

	/**
     * Tests walking through of Log_EventValues
     */
    public function testIteratorWalk()
    {
        $values = array();
        foreach ($this->Log_EventValues as $key=>$value) {
            $values[$key] = $value;
        }
        $this->assertEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true), $values);
    }

    
    /**
     * Tests Log_EventValues->offsetExists()
     */
    public function testOffsetExists()
    {
        $this->assertTrue($this->Log_EventValues->offsetExists('abc'));
        $this->assertFalse($this->Log_EventValues->offsetExists('not_me'));
                
        $this->assertTrue(isset($this->Log_EventValues['abc']), 'isset');
        $this->assertFalse(isset($this->Log_EventValues['not_me']), 'isset');
    }
    
    /**
     * Tests Log_EventValues->offsetGet()
     */
    public function testOffsetGet()
    {
        $this->assertEquals("test", $this->Log_EventValues->offsetGet('klm'));
        $this->assertEquals(10, $this->Log_EventValues->offsetGet('abc'));
        
        $this->assertEquals("test", $this->Log_EventValues['klm'], "ArrayAccess");
        $this->assertEquals(10, $this->Log_EventValues['abc'], "ArrayAccess");
    }
    
    /**
     * Tests Log_EventValues->offsetSet()
     */
    public function testOffsetSet()
    {
        $this->Log_EventValues->offsetSet('abc', 100);
        $this->Log_EventValues['qqq'] = "hello";
        $this->assertAttributeEquals(array('abc'=>100, 'klm'=>"test", 'xyz'=>true, "qqq"=>"hello"), 'values', $this->Log_EventValues);
    }
    
    /**
     * Tests Log_EventValues->offsetUnset()
     */
    public function testOffsetUnset()
    {
        $this->Log_EventValues->offsetUnset('abc');
        unset($this->Log_EventValues['xyz']);
        $this->assertAttributeEquals(array('klm'=>"test"), 'values', $this->Log_EventValues);
    }
    
    
    /**
     * Tests Log_EventValues::setVar()
     */
    public function testSetVar()
    {
        $var = 100;
        Q\Log_EventValues::$vars['test'] =& $var;
        $prop = new ReflectionProperty('Q\Log_EventValues', 'vars');
        $prop->setAccessible(true);
        
        $vars = $prop->getValue(null);
        $this->assertEquals(100, $vars['test']);
        
        $var = 250;
        $vars = $prop->getValue(null);
        $this->assertEquals(250, $vars['test']);
    }

    /**
     * Tests Log_EventValues::setVar() in combination with  Log_EventValues::getValue()
     */
    public function testSetVar_getValue()
    {
        $var = 100;
        Q\Log_EventValues::$vars['test'] =& $var;
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
        Q\Log_EventValues::$vars['test'] =& $var;
        Q\Log_EventValues::$vars['yatest'] = "hello";
        
        $this->Log_EventValues->useVar('test');
        $this->assertAttributeEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true, 'test'=>100), 'values', $this->Log_EventValues);
        
        $var = 250;
        $this->assertAttributeEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true, 'test'=>250), 'values', $this->Log_EventValues);

        $this->Log_EventValues->useVar('mytest', 'yatest');
        $this->assertAttributeEquals(array('abc'=>10, 'klm'=>"test", 'xyz'=>true, 'test'=>250, 'mytest'=>"hello"), 'values', $this->Log_EventValues);
    }
    
    /**
     * Tests Log_EventValues::setCallback()
     */
    public function testSetCallback()
    {
        Q\Log_EventValues::$vars['test'] = function () { return 100; };
        $this->assertEquals(100, $this->Log_EventValues->getValue('test'));
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

?>