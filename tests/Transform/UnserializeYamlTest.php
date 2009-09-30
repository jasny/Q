<?php
use Q\Transform_Unserialize_Yaml, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
require_once 'Q/Transform/Unserialize/Yaml.php';

/**
 * Transform_Unserialize_Yaml test case.
 */
class Transform_Unserialize_YamlTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
	/**
	 * Tests Transform_Unserialize_Yaml->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Unserialize_Yaml ();
		$contents = $transform->process('a: 1
b: 2
c:
 d: e
 f: d
 e:
  a: v
');
        $this->assertType('Q\Transform_Unserialize_Yaml', $transform);
		$this->assertEquals(array('a'=>1,'b'=>2,'c'=>array('d'=>'e', 'f'=>'d', 'e'=>array('a'=>'v'))), $contents);
	}
	
	/**
	 * Tests Transform_Unserialize_Yaml->process()
	 */
	public function testProcess_withFs() 
	{
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		file_put_contents($this->tmpfile, 'a: 1
b: 2
');
		
        $file = $this->getMock('Q\Fs_Node', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));		
        $file->expects($this->once())->method('getContents')->will($this->returnValue(file_get_contents($this->tmpfile)));       
        
		$transform = new Transform_Unserialize_Yaml();
		$contents = $transform->process($file);

        $this->assertType('Q\Transform_Unserialize_Yaml', $transform);
		$this->assertEquals(array('a'=>1,'b'=>2), $contents);
	}

	/**
	 * Tests Transform_Unserialize_Yaml->output()
	 */
	public function testOutput() 
	{
        $this->setExpectedException('Q\Transform_Exception', "Unable to output data: Transformation returned a non-scalar value of type 'array'.");
		$transform = new Transform_Unserialize_Yaml();
    	$transform->output('a: 1
b: 2
');
	}
		
	/**
	 * Tests Transform_Unserialize_Yaml->save()
	 */
	public function testSave() 
	{
        $this->setExpectedException('Q\Transform_Exception', "Transformation returned a non-scalar value of type 'array'");
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		
        $transform = new Transform_Unserialize_Yaml ();
		$transform->save ($this->tmpfile, 'a: 1
b: 2
');
	}

	/**
	 * Tests Transform_Unserialize_Yaml->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize_Yaml();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_Yaml', $reverse);
        $this->assertEquals('a: 1
b: 2
', $reverse->process(array('a'=>1, 'b'=>2)));
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_Unserialize_YamlTest::main') Transform_Unserialize_YamlTest::main();
