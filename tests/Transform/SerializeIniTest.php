<?php
use Q\Transform_Serialize_Ini, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
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
		$contents = $transform->process(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')));

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
        $this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $reverse->process('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
'));
	}

}

if (PHPUnit_MAIN_METHOD == 'Transform_Serialize_IniTest::main') Transform_Serialize_IniTest::main();
