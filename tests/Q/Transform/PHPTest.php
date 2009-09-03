<?php
use Q\Transform_PHP, Q\Transform;

require_once dirname(dirname(dirname(__FILE__))) . '/TestHelper.php';
require_once 'Q/Transform/PHP.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Transform_PHP test case.
 */
class Transform_PHPTest extends \PHPUnit_Framework_TestCase
{    
    /**
     * File that contains the template
     * @var string
     */
    protected $file = '/home/carmen/projects/Q/tests/Q/Transform/test/template.php';
	
    /**
     * Data to transform
     * @var string
     */
    protected $dataToTransform = array('a'=>'TEST', 'b'=>array('2', '4', '7'));
    
    /**
     * Expected result after transformation
     * @var string
     */
    protected $expectedResult = 'a : <br />string(4) "TEST"
<br /><br /> b: <br />array(3) {
  [0]=>
  string(1) "2"
  [1]=>
  string(1) "4"
  [2]=>
  string(1) "7"
}
<br /><br /> b elements sum: <br />int(13)
';

    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/php.txt';
    
	/**
	 * Run test from php
	 */
    public static function main() 
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
	/**
	 * Tests Transform_PHP->process()
	 */
	public function testProcess()
	{
		$transform = new Transform_PHP(array('file'=> $this->file));
		$contents = $transform->process($this->dataToTransform);
		
		$this->assertType('Q\Transform_PHP', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}

	/**
	 * Tests Transform_PHP->output()
	 */
	public function testOutput()
	{
		$transform = new Transform_PHP();
		$transform->file = $this->file;
		ob_start();
		$transform->output($this->dataToTransform);
		$contents = ob_get_contents();
		ob_end_clean();
		            
		$this->assertType('Q\Transform_PHP', $transform);
		$this->assertEquals($this->expectedResult, $contents);            
	}

    /**
     * Tests Transform_PHP->save()
     */
    public function testSave()
    {
        $transform = new Transform_PHP(array('file'=> $this->file));
        $transform->save($this->filename, $this->dataToTransform);

        $this->assertType('Q\Transform_PHP', $transform);
        $this->assertEquals($this->expectedResult, file_get_contents($this->filename));
    }
}

if (PHPUnit_MAIN_METHOD == 'Transform_PHPTest::main') Transform_PHPTest::main();
