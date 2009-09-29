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
     * Data to transform
     * @var array
     */
    protected $dataToTransform = array ('a'=>1,'b'=>2,'c'=>array('d'=>'e', 'f'=>'d', 'e'=>array('a'=>'v')));
        
    /**
     * Expected result after transformation
     * @var string
     */
    protected $expectedResult = 'a: 1
b: 2
c:
 d: e
 f: d
 e:
  a: v
';

    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/SerializeYamlTest.txt';
	
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
		$contents = $transform->process ($this->dataToTransform);

        $this->assertType('Q\Transform_Serialize_Yaml', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_Serialize_Yaml->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_Yaml();
		ob_start();
		try{
    		$transform->output($this->dataToTransform);
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Serialize_Yaml', $transform);
        $this->assertEquals($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_Serialize_Yaml->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Serialize_Yaml ();
		$transform->save ($this->filename, $this->dataToTransform);
		
        $this->assertType('Q\Transform_Serialize_Yaml', $transform);
		$this->assertEquals($this->expectedResult, file_get_contents($this->filename));
	}

	/**
	 * Tests Transform_Serialize_Yaml->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_Yaml();
		$transform->process($this->dataToTransform);
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_Yaml', $reverse);
        $this->assertEquals($this->dataToTransform, $reverse->process($this->expectedResult));
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_Serialize_YamlTest::main') Transform_Serialize_YamlTest::main();
