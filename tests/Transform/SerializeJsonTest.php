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
     * Data to transform
     * @var array
     */
    protected $dataToTransform = array ('a'=>1,'b'=>2,'c'=>3,'d'=>4,'e'=>5);
        
    /**
     * Expected result after transformation
     * @var string
     */
    protected $expectedResult = '{"a":1,"b":2,"c":3,"d":4,"e":5}';

    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/SerializeJsonTest.txt';
	
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
		$contents = $transform->process (array('a'=>1,'b'=>2,'c'=>3,'d'=>array('e'=>4, 'f'=>5)));

        $this->assertType('Q\Transform_Serialize_Json', $transform);
		$this->assertEquals({"a":1,"b":2,"c":3,"d":{"e":4,"f":5}}, $contents);
	}
	
	/**
	 * Tests Transform_Serialize_Json->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_Json();
		ob_start();
		try{
    		$transform->output($this->dataToTransform);
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Serialize_Json', $transform);
        $this->assertEquals($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_Serialize_Json->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Serialize_Json ();
		$transform->save ($this->filename, $this->dataToTransform);
		
        $this->assertType('Q\Transform_Serialize_Json', $transform);
		$this->assertEquals($this->expectedResult, file_get_contents($this->filename));
	}

	/**
	 * Tests Transform_Serialize_Json->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_Json();
		$transform->process($this->dataToTransform);
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_Json', $reverse);
        $this->assertEquals($this->dataToTransform, $reverse->process($this->expectedResult));
	}

}

if (PHPUnit_MAIN_METHOD == 'Transform_Serialize_JsonTest::main') Transform_Serialize_JsonTest::main();
