<?php
use Q\Transform_Serialize_Yaml, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Serialize/Yaml.php';

/**
 * Transform_Serialize_Yaml test case.
 */
class Transform_Serialize_YamlTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Tests Transform_Serialize_Yaml->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Serialize_Yaml ();
		$contents = $transform->process(array('a'=>1,'b'=>2,'c'=>array('d'=>'e', 'f'=>'d', 'e'=>array('a'=>'v'))));

        $this->assertType('Q\Transform_Serialize_Yaml', $transform);
		$this->assertEquals('a: 1
b: 2
c:
 d: e
 f: d
 e:
  a: v
', $contents);
	}

	/**
     * Tests Transform_Serialize_Yaml->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue(array('a'=>1,'b'=>2,'c'=>array('d'=>'e', 'f'=>'d', 'e'=>array('a'=>'v')))));
        
        $transform = new Transform_Serialize_Yaml();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Serialize_Yaml', $transform);
        $this->assertEquals('a: 1
b: 2
c:
 d: e
 f: d
 e:
  a: v
', $contents);
    }
	
	/**
	 * Tests Transform_Serialize_Yaml->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_Yaml();
		ob_start();
		try{
    		$transform->output(array('a'=>1,'b'=>2));
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Serialize_Yaml', $transform);
        $this->assertEquals('a: 1
b: 2
', $contents);
	}
	
	/**
	 * Tests Transform_Serialize_Yaml->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Serialize_Yaml();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		$transform->save ($this->tmpfile, array('a'=>1,'b'=>2));
		
        $this->assertType('Q\Transform_Serialize_Yaml', $transform);
		$this->assertEquals('a: 1
b: 2
', file_get_contents($this->tmpfile));
	}

	/**
	 * Tests Transform_Serialize_Yaml->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_Yaml();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_Yaml', $reverse);
	}
        
    /**
     * Tests Transform_Serialize_Yaml->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Unserialize_Yaml'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Serialize_Yaml();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
