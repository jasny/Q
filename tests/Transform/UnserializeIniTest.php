<?php
use Q\Transform_Unserialize_Ini, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
require_once 'Q/Transform/Serialize/Ini.php';

/**
 * Transform_Unserialize_Ini test case.
 */
class Transform_Unserialize_IniTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Data to transform
     * @var array
     */
    protected $dataToTransform = '
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
';

    /**
     * Data to transform
     * @var string
     */
    protected $dataToTransform_url = 'test/unserialize.ini';
        
    /**
     * Expected result after transformation
     * @var string
     */
    protected $expectedResult = array ('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original'));

    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/SerializeIniTest.txt';
	
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
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
	 * Tests Transform_Unserialize_Ini->process()
	 */
	public function testProcess_withFs() 
	{
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		file_put_contents($this->tmpfile, '
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');
		$file = $this->getMock('Q\Fs_File', array('__toString'), array($this->tmpfile));
		$file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));

		$transform = new Transform_Unserialize_Ini();
		$contents = $transform->process($file);
//		var_dump($contents); exit;
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
	}

	/**
	 * Tests Transform_Unserialize_Ini->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Unserialize_Ini();
		ob_start();
		try{
    		$transform->output('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
        $this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
	}
	
	/**
	 * Tests Transform_Unserialize_Ini->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Unserialize_Ini ();
		$transform->save ('/tmp/SerializeIniTest.txt', '
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), file_get_contents($this->filename));
	}

	/**
	 * Tests Transform_Unserialize_Ini->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize_Ini();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_Ini', $reverse);
        $this->assertEquals('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
', $reverse->process(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original'))));
	}

}

if (PHPUnit_MAIN_METHOD == 'Transform_Unserialize_IniTest::main') Transform_Unserialize_IniTest::main();
