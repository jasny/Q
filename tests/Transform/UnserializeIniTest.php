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
		$contents = $transform->process ($this->dataToTransform);

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_Unserialize_Ini->process()
	 */
	public function testProcess2() 
	{
		$transform = new Transform_Unserialize_Ini ();
		$contents = $transform->process (Q\Fs::get($this->dataToTransform_url));

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}

	/**
	 * Tests Transform_Unserialize_Ini->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Unserialize_Ini();
		ob_start();
		$transform->output($this->dataToTransform);
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
        $this->assertEquals($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_Unserialize_Ini->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Unserialize_Ini ();
		$transform->save ($this->filename, $this->dataToTransform);
		
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
		$this->assertEquals($this->expectedResult, file_get_contents($this->filename));
	}

	/**
	 * Tests Transform_Unserialize_Ini->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize_Ini();
		$transform->process($this->dataToTransform);
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_Ini', $reverse);
        $this->assertEquals($this->dataToTransform, $reverse->process($this->expectedResult));
	}

}

if (PHPUnit_MAIN_METHOD == 'Transform_Unserialize_IniTest::main') Transform_Unserialize_IniTest::main();
