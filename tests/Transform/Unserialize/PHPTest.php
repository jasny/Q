<?php
use Q\Transform_Unserialize_PHP, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Unserialize/PHP.php';

/**
 * Transform_PHP test case.
 */
class Transform_Unserialize_PHPTest extends \PHPUnit_Framework_TestCase
{    
	/**
	 * Tests Transform_Unserialize_PHP->process() using a file for process
	 */

    public function testProcess()
	{	
		$transform = new Transform_Unserialize_PHP();
		$contents = $transform->process("array (
  'a' => 'TEST',
  'b' => 
  array (
    0 => '2',
    1 => '4',
    2 => '7'
  )
);
");
		
		$this->assertType('Q\Transform_Unserialize_PHP', $transform);
		$this->assertEquals(array ( 'a'=>'TEST', 'b'=> array ( 0 => '2', 1 => '4', 2 => '7' )), $contents);
	}

    /**
     * Tests Transform_Unserialize_PHP->process() with invalid data
     */
    public function testProcess_Exception_InvalidData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Wrong parameter type : array given when string should be pass");
        $transform = new Transform_Unserialize_PHP();
        $contents = $transform->process(array());
    }
    
    /**
     * Tests Transform_Unserialize_PHP->process() with invalid data
     */
    public function testProcess_Exception_Fs_InvalidData() 
    {
    	$this->markTestSkipped('Fix the eval problem and remove skip');
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, "array ( 'a', b' );");
        
        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       
    	
        $this->setExpectedException('Q\Transform_Exception', "Wrong parameter type : array given when string should be pass");
        $transform = new Transform_Unserialize_PHP();
        $contents = $transform->process($this->tmpfile);
    }
    
    /**
     * Tests Transform_Unserialize_PHP->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue("array (
  'a' => 'TEST',
  'b' => 
  array (
    0 => '2',
    1 => '4',
    2 => '7'
  )
);
"));
        
        $transform = new Transform_Unserialize_PHP();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Unserialize_PHP', $transform);
        $this->assertEquals(array ( 'a'=>'TEST', 'b'=> array ( 0 => '2', 1 => '4', 2 => '7' )), $contents);
    }
	
	/**
     * Tests Transform_Unserialize_PHP->process() using a string for process
     */
    public function testProcess_Fs()
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, "array ( 'a', 'b' );");
        
        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       

        $transform = new Transform_Unserialize_PHP();
        $contents = $transform->process($file);

        $this->assertType('Q\Transform_Unserialize_PHP', $transform);
        $this->assertEquals(array ( 'a', 'b' ), $contents);
    }
    
	/**
	 * Tests Transform_Unserialize_PHP->process() using a string for process
	 */
	public function testProcess_Fs_and_PhpTag()
	{
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, "<?php return array ( 'a', 'b' );");
        
        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       

        $transform = new Transform_Unserialize_PHP();
		$contents = $transform->process($file);
		
		$this->assertType('Q\Transform_Unserialize_PHP', $transform);
		$this->assertEquals(array ( 'a', 'b' ), $contents);
	}

	/**
	 * Tests Transform_Unserialize_PHP->output()
	 */
	public function testOutput()
	{
        $this->setExpectedException('Q\Transform_Exception', "Unable to output data: Transformation returned a non-scalar value of type 'array'.");

        $transform = new Transform_Unserialize_PHP();
		$transform->output("array ( 'a', 'b' );");
	}

    /**
     * Tests Transform_Unserialize_PHP->save()
     */
    public function testSave()
    {
        $this->setExpectedException('Q\Transform_Exception', "Transformation returned a non-scalar value of type 'array'");
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
    	
        $transform = new Transform_Unserialize_PHP();
        $transform->save($this->tmpfile, "array ( 'a', 'b' );");
    }

    /**
     * Tests Transform_Unserialize_Json->getReverse()
     */
    public function testGetReverse() 
    {
        $transform = new Transform_Unserialize_PHP();
        $reverse = $transform->getReverse();
        $this->assertType('Q\Transform_Serialize_PHP', $reverse);
        $this->assertObjectHasAttribute('castObjectToString', $reverse);
    }

    /**
     * Tests Transform_Unserialize_PHP->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Serialize_PHP'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Unserialize_PHP();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
