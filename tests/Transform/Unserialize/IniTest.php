<?php
use Q\Transform_Unserialize_Ini, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Serialize/Ini.php';
require_once 'Q/Fs/Node.php';

/**
 * Transform_Unserialize_Ini test case.
 */
class Transform_Unserialize_IniTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Tests Transform_Unserialize_Ini->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Unserialize_Ini ();
		$contents = $transform->process('[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
	}

	        
    /**
     * Tests Transform_Unserialize_Ini->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
'));
        
        $transform = new Transform_UnSerialize_Ini();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
        $this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
    }
	
	/**
	 * Tests Transform_Unserialize_Ini->process()
	 */
	public function testProcess_Fs() 
	{
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		file_put_contents($this->tmpfile, '
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');

        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
		$file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));

		$transform = new Transform_Unserialize_Ini();
		$contents = $transform->process($file);

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
	}

	/**
	 * Tests Transform_Unserialize_Ini->output()
	 */
	public function testOutput() 
	{
        $this->setExpectedException('Q\Transform_Exception', "Transformation returned a non-scalar value of type 'array'");
		$transform = new Transform_Unserialize_Ini();
    		$transform->output('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');
	}
	
	/**
	 * Tests Transform_Unserialize_Ini->save()
	 */
	public function testSave() 
	{
        $this->setExpectedException('Q\Transform_Exception', "Transformation returned a non-scalar value of type 'array'");
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		
        $transform = new Transform_Unserialize_Ini();
		$transform->save($this->tmpfile, '
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');
	}

	/**
	 * Tests Transform_Unserialize_Ini->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize_Ini();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_Ini', $reverse);
	}

    /**
     * Tests Transform_Unserialize_Ini->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Serialize_Ini'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Unserialize_Ini();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
