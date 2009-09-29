<?php
use Q\Transform_Serialize_Json, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
require_once 'Q/Transform/Serialize/Json.php';

/**
 * Transform_Serialize_Json test case.
 */
class Transform_Serialize_JsonTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
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
        $this->assertEquals(array('a'=>1,'b'=>2,'c'=>3), $reverse->process('{"a":1,"b":2,"c":3}'));
	}

}

if (PHPUnit_MAIN_METHOD == 'Transform_Serialize_JsonTest::main') Transform_Serialize_JsonTest::main();
