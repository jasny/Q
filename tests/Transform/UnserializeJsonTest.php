<?php
use Q\Transform_Unserialize_Json, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
require_once 'Q/Transform/Unserialize/Json.php';

/**
 * Transform_Unserialize_Json test case.
 */
class Transform_Unserialize_JsonTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
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
	 * Tests Transform_Unserialize_Json->process()
	 */
	public function testProcess_withFs() 
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
        $this->assertEquals('{"a":"t1","b":"t2"}', $reverse->process(array('a'=>'t1', 'b'=>'t2')));
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_Unserialize_JsonTest::main') Transform_Unserialize_JsonTest::main();
