<?php
use Q\Transform_Unserialize_Json, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Unserialize/Json.php';

/**
 * Transform_Unserialize_Json test case.
 */
class Transform_Unserialize_JsonTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Tests Transform_Unserialize_Json->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Unserialize_Json ();
		$contents = $transform->process('{"a":1,"b":{"c":2,"d":{"h":6,"i":"bla"}},"e":{"f":4,"g":5}}');
        $this->assertType('Q\Transform_Unserialize_Json', $transform);
		$this->assertEquals(array("a"=>1, "b"=>array("c"=>2, "d"=>array("h"=>6, "i"=>"bla")), "e"=>array("f"=>4, "g"=>5)), $contents);
	}
	
    /**
     * Tests Transform_Unserialize_Json->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('{"a":1,"b":{"c":2,"d":{"h":6,"i":"bla"}},"e":{"f":4,"g":5}}'));
        
        $transform = new Transform_Unserialize_Json();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Unserialize_Json', $transform);
        $this->assertEquals(array("a"=>1, "b"=>array("c"=>2, "d"=>array("h"=>6, "i"=>"bla")), "e"=>array("f"=>4, "g"=>5)), $contents);
    }
	
	/**
	 * Tests Transform_Unserialize_Json->process()
	 */
	public function testProcess_Fs() 
	{
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		file_put_contents($this->tmpfile, '{"a":"t1","b":"t2"}');
		
        $file = $this->getMock('Q\Fs_Node', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));		
        $file->expects($this->once())->method('getContents')->will($this->returnValue(file_get_contents($this->tmpfile)));       
        
		$transform = new Transform_Unserialize_Json();
		$contents = $transform->process($file);

        $this->assertType('Q\Transform_Unserialize_Json', $transform);
		$this->assertEquals(array('a'=>'t1', 'b'=>'t2'), $contents);
	}

    /**
     * Tests Transform_Unserialize_Json->process()
     */
    public function testProcess_Exception_MalformedJson() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Failed to unserialize json; Invalid json syntax.");
        
        $transform = new Transform_Unserialize_Json();
        $contents = $transform->process("{'Organization': 'PHP Documentation Team'}");
    }
	
	/**
	 * Tests Transform_Unserialize_Json->output()
	 */
	public function testOutput() 
	{
        $this->setExpectedException('Q\Transform_Exception', "Unable to output data: Transformation returned a non-scalar value of type 'array'.");
		$transform = new Transform_Unserialize_Json();
    	$transform->output('{"a":"t1","b":"t2"}');
	}
		
	/**
	 * Tests Transform_Unserialize_Json->save()
	 */
	public function testSave() 
	{
        $this->setExpectedException('Q\Transform_Exception', "Transformation returned a non-scalar value of type 'array'");
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		
        $transform = new Transform_Unserialize_Json ();
		$transform->save ($this->tmpfile, '{"a":"t1","b":"t2"}');
	}

	/**
	 * Tests Transform_Unserialize_Json->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize_Json();
		$reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_Json', $reverse);
	}

    /**
     * Tests Transform_Unserialize_Json->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Serialize_Json'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Unserialize_Json();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
