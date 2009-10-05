<?php
use Q\Transform_Serialize_Json, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Serialize/Json.php';

/**
 * Transform_Serialize_Json test case.
 */
class Transform_Serialize_JsonTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Tests Transform_Serailize_Json->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Serialize_Json ();
		$contents = $transform->process(array('a'=>1,'b'=>2,'c'=>3,'d'=>(object)array('e'=>4, 'f'=>5)));

        $this->assertType('Q\Transform_Serialize_Json', $transform);
		$this->assertEquals('{"a":1,"b":2,"c":3,"d":{"e":4,"f":5}}', $contents);
	}
	    
    /**
     * Tests Transform_Serialize_Json->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue(array('a'=>1,'b'=>2,'c'=>3,'d'=>(object)array('e'=>4, 'f'=>5))));
        
        $transform = new Transform_Serialize_Json();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Serialize_Json', $transform);
        $this->assertEquals('{"a":1,"b":2,"c":3,"d":{"e":4,"f":5}}', $contents);
    }
    
	/**
	 * Tests Transform_Serialize_Json->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_Json();
		ob_start();
		try{
    		$transform->output(array('a'=>1,'b'=>2,'c'=>3));
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
    	$contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Serialize_Json', $transform);
        $this->assertEquals('{"a":1,"b":2,"c":3}', $contents);
	}
	
	/**
	 * Tests Transform_Serialize_Json->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Serialize_Json ();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		$transform->save($this->tmpfile, array('a'=>1,'b'=>2,'c'=>3));
		
        $this->assertType('Q\Transform_Serialize_Json', $transform);
		$this->assertEquals('{"a":1,"b":2,"c":3}', file_get_contents($this->tmpfile));
	}

	/**
	 * Tests Transform_Serialize_Json->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_Json();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_Json', $reverse);
        $this->assertObjectHasAttribute('assoc', $reverse);
	}
        
    /**
     * Tests Transform_Serialize_Json->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Unserialize_Json'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Serialize_Json();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
