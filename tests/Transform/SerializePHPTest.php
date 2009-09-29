<?php
use Q\Transform_Serialize_PHP, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
require_once 'Q/Transform/Serialize/PHP.php';

/**
 * Transform_Serialize_PHP test case.
 */
class Transform_Serialize_PHPTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Data to transform
     * @var array
     */
    protected $dataToTransform = array (
  'a' => 'TEST',
  'b' => 
  array (
    0 => '2',
    1 => '4',
    2 => '7',
  ),
)
;
        
    /**
     * Expected result after transformation
     * @var string
     */
    protected $expectedResult = "array ( 'a' => 'TEST', 'b' => array ( 0 => '2', 1 => '4', 2 => '7' ) )";
    
    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/SerializePHPTest.txt';
	
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
	/**
	 * Tests Transform_Serailize_PHP->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Serialize_PHP();
		$contents = $transform->process ($this->dataToTransform);

        $this->assertType('Q\Transform_Serialize_PHP', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_Serialize_PHP->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_PHP();
		ob_start();
		try{
    		$transform->output($this->dataToTransform);
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Serialize_PHP', $transform);
        $this->assertEquals($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_Serialize_PHP->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Serialize_PHP();
		$transform->save ($this->filename, $this->dataToTransform);
		
        $this->assertType('Q\Transform_Serialize_PHP', $transform);
		$this->assertEquals($this->expectedResult, file_get_contents($this->filename));
	}

	/**
	 * Tests Transform_Serialize_PHP->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_PHP();
		$transform->process($this->dataToTransform);
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_PHP', $reverse);
        $this->assertEquals($this->dataToTransform, $reverse->process($this->expectedResult));
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_Serialize_PHPTest::main') Transform_Serialize_PHPTest::main();
