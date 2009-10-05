<?php
use Q\Transform_Serialize_PHP, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Serialize/PHP.php';

/**
 * Transform_Serialize_PHP test case.
 */
class Transform_Serialize_PHPTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Tests Transform_Serailize_PHP->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Serialize_PHP();
		$contents = $transform->process(array('a' => 'TEST','b' => array(0 => 2, 1 => 3.5, 2 => '7'), 'c'=>(object)array('e'=>'test')));
		$this->assertType('Q\Transform_Serialize_PHP', $transform);
		$this->assertEquals("array ( 'a' => 'TEST', 'b' => array ( 0 => 2, 1 => 3.5, 2 => '7' ), 'c' => (object) array ( 'e' => 'test' ) )", $contents);
	}

	/**
     * Tests Transform_Serialize_PHP->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue(array('a' => 'TEST','b' => array(0 => 2, 1 => 3.5, 2 => '7'), 'c'=>(object)array('e'=>'test'))));
        
        $transform = new Transform_Serialize_PHP();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Serialize_PHP', $transform);
        $this->assertEquals("array ( 'a' => 'TEST', 'b' => array ( 0 => 2, 1 => 3.5, 2 => '7' ), 'c' => (object) array ( 'e' => 'test' ) )", $contents);
    }
	
	/**
	 * Tests Transform_Serialize_PHP->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_PHP();
		ob_start();
		try{
    		$transform->output(array('a', 'b'));
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();
        
        $this->assertType('Q\Transform_Serialize_PHP', $transform);
        $this->assertEquals( "array ( 0 => 'a', 1 => 'b' )", $contents);
	}
	
	/**
	 * Tests Transform_Serialize_PHP->save()
	 */
	public function testSave() 
	{
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		
		$transform = new Transform_Serialize_PHP();
		$transform->save($this->tmpfile, array('a', 'b'));
		
        $this->assertType('Q\Transform_Serialize_PHP', $transform);
		$this->assertEquals( "array ( 0 => 'a', 1 => 'b' )", file_get_contents($this->tmpfile));
	}

	/**
	 * Tests Transform_Serialize_PHP->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_PHP();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_PHP', $reverse);
        $this->assertObjectHasAttribute('errorDescription', $reverse);
        $this->assertObjectHasAttribute('warnings', $reverse);
	}
        
    /**
     * Tests Transform_Serialize_PHP->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Unserialize_PHP'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Serialize_PHP();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
