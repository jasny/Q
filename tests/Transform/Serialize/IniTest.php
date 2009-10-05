<?php
use Q\Transform_Serialize_Ini, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Serialize/Ini.php';

/**
 * Transform_Serialize_Ini test case.
 */
class Transform_Serialize_IniTest extends PHPUnit_Framework_TestCase 
{

	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
	/**
	 * Tests Transform_Serialize_Ini->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Serialize_Ini();
		$contents = $transform->process(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original', 'b'=>array('w', 'x', 'y'))));

		$this->assertType('Q\Transform_Serialize_Ini', $transform);
		$this->assertEquals('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
b[] = "w"
b[] = "x"
b[] = "y"
', $contents);
	}
        
    /**
     * Tests Transform_Serialize_Ini->process() with a chain
     */
    public function testProcess_Chain() 
    {
    	$mock = $this->getMock('Q\Transform', array('process'));
    	$mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original', 'b'=>array('w', 'x', 'y')))));
    	
        $transform = new Transform_Serialize_Ini();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Serialize_Ini', $transform);
        $this->assertEquals('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
b[] = "w"
b[] = "x"
b[] = "y"
', $contents);
    }
    
    /**
     * Tests Transform_Serialize_Ini->process() with invalid data
     */
    public function testProcess_Exception_InvalidData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to serialize to a ini string : incorrect data type");
    	$transform = new Transform_Serialize_Ini();
        $contents = $transform->process('process_string');
    }
	
	/**
	 * Tests Transform_Serialize_Ini->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_Ini();
		ob_start();
		try{
    		$transform->output(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')));
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Serialize_Ini', $transform);
        $this->assertEquals('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
', $contents);
	}
	
	/**
	 * Tests Transform_Serialize_Ini->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Serialize_Ini();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		$transform->save($this->tmpfile, array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')));
		
        $this->assertType('Q\Transform_Serialize_Ini', $transform);
		$this->assertEquals('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
', file_get_contents($this->tmpfile));
	}

	/**
	 * Tests Transform_Serialize_Ini->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_Ini();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_Ini', $reverse);
	}
        
    /**
     * Tests Transform_Serialize_Ini->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Unserialize_Ini'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Serialize_Ini();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
