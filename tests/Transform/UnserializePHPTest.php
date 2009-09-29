<?php
use Q\Transform_Unserialize_PHP, Q\Transform;

require_once dirname(dirname(__FILE__)) . '/TestHelper.php';
require_once 'Q/Transform/Unserialize/PHP.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Transform_PHP test case.
 */
class Transform_Unserialize_PHPTest extends \PHPUnit_Framework_TestCase
{    
    /**
     * Data to transform
     * @var string
     */
    protected $dataToTransform_url = '/home/carmen/projects/Q/tests/Transform/test/unserializePHP.php';

    /**
     * Data to transform
     * @var string 
     */
    protected $dataToTransform = "array (
  'a' => 'TEST',
  'b' => 
  array (
    0 => '2',
    1 => '4',
    2 => '7',
  ),
)
";
    
    /**
     * Expected result after transformation
     * @var array
     */
    protected $expectedResult = array (
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
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/unserializePHP.txt';
    
	/**
	 * Run test from php
	 */
    public static function main() 
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
	/**
	 * Tests Transform_Unserialize_PHP->process() using a file for process
	 */
	public function testProcess()
	{
	    $this->markTestSkipped("PHP unserialize not well implemented when url is used");
	
		$transform = new Transform_Unserialize_PHP();
		$contents = $transform->process($this->dataToTransform_url);

		
		$this->assertType('Q\Transform_Unserialize_PHP', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}

	/**
	 * Tests Transform_Unserialize_PHP->process() using a string for process
	 */
	public function testProcess2()
	{
		$transform = new Transform_Unserialize_PHP();
		$contents = $transform->process($this->dataToTransform);

		$this->assertType('Q\Transform_Unserialize_PHP', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}

	/**
	 * Tests Transform_Unserialize_PHP->output()
	 */
	public function testOutput()
	{
		$transform = new Transform_Unserialize_PHP();
		ob_start();
		$transform->output($this->dataToTransform);
		$contents = ob_get_contents();
		ob_end_clean();
		$this->assertType('Q\Transform_Unserialize_PHP', $transform);
		$this->assertEquals($this->expectedResult, $contents);            
	}

    /**
     * Tests Transform_Unserialize_PHP->save()
     */
    public function testSave()
    {
        $transform = new Transform_Unserialize_PHP();
        $transform->save($this->filename, $this->dataToTransform);

        $this->assertType('Q\Transform_Unserialize_PHP', $transform);
        $this->assertEquals($this->expectedResult, file_get_contents($this->filename));
    }
}

if (PHPUnit_MAIN_METHOD == 'Transform_PHPTest::main') Transform_PHPTest::main();
