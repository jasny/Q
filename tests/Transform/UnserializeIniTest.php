<?php
use Q\Transform_Unserialize_Ini, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
require_once 'Q/Transform/Serialize/Ini.php';
<<<<<<< HEAD:tests/Transform/UnserializeIniTest.php
//require_once 'Q/Fs/File.php';
=======
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:tests/Transform/UnserializeIniTest.php

/**
 * Transform_Unserialize_Ini test case.
 */
class Transform_Unserialize_IniTest extends PHPUnit_Framework_TestCase 
{
<<<<<<< HEAD:tests/Transform/UnserializeIniTest.php
=======
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
	
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:tests/Transform/UnserializeIniTest.php
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
<<<<<<< HEAD:tests/Transform/UnserializeIniTest.php
		$contents = $transform->process('[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
=======
		$contents = $transform->process ($this->dataToTransform);

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals($this->expectedResult, $contents);
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:tests/Transform/UnserializeIniTest.php
	}
	
	/**
	 * Tests Transform_Unserialize_Ini->process()
	 */
<<<<<<< HEAD:tests/Transform/UnserializeIniTest.php
	public function testProcess_usingUrl() 
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
		var_dump($contents); exit;
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
=======
	public function testProcess2() 
	{
		$transform = new Transform_Unserialize_Ini ();
		$contents = $transform->process (Q\Fs::get($this->dataToTransform_url));

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals($this->expectedResult, $contents);
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:tests/Transform/UnserializeIniTest.php
	}

	/**
	 * Tests Transform_Unserialize_Ini->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Unserialize_Ini();
		ob_start();
<<<<<<< HEAD:tests/Transform/UnserializeIniTest.php
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
=======
		$transform->output($this->dataToTransform);
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:tests/Transform/UnserializeIniTest.php
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
<<<<<<< HEAD:tests/Transform/UnserializeIniTest.php
        $this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
=======
        $this->assertEquals($this->expectedResult, $contents);
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:tests/Transform/UnserializeIniTest.php
	}
	
	/**
	 * Tests Transform_Unserialize_Ini->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Unserialize_Ini ();
<<<<<<< HEAD:tests/Transform/UnserializeIniTest.php
		$transform->save ('/tmp/SerializeIniTest.txt', '
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');
		
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), file_get_contents($this->filename));
=======
		$transform->save ($this->filename, $this->dataToTransform);
		
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals($this->expectedResult, file_get_contents($this->filename));
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:tests/Transform/UnserializeIniTest.php
	}

	/**
	 * Tests Transform_Unserialize_Ini->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize_Ini();
<<<<<<< HEAD:tests/Transform/UnserializeIniTest.php
		$transform->process('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
');
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_Ini', $reverse);
        $this->assertEquals('
[grp1]
q = "abc"
b = "27"

[grp2]
a = "original"
', $reverse->process(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original'))));
=======
		$transform->process($this->dataToTransform);
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_Ini', $reverse);
        $this->assertEquals($this->dataToTransform, $reverse->process($this->expectedResult));
>>>>>>> ac34dbb77c3a3611c0b0224528b88eabc3c35be8:tests/Transform/UnserializeIniTest.php
	}

}

if (PHPUnit_MAIN_METHOD == 'Transform_Unserialize_IniTest::main') Transform_Unserialize_IniTest::main();
