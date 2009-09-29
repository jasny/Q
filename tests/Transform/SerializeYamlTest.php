<?php
use Q\Transform_Serialize_Yaml, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
require_once 'Q/Transform/Serialize/Yaml.php';

/**
 * Transform_Serialize_Yaml test case.
 */
class Transform_Serialize_YamlTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
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
        $this->assertEquals(array('a'=>1,'b'=>2,'c'=>array('d'=>'e', 'f'=>'d', 'e'=>array('a'=>'v'))), $reverse->process('a: 1
b: 2
c:
 d: e
 f: d
 e:
  a: v
'));
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_Serialize_YamlTest::main') Transform_Serialize_YamlTest::main();
