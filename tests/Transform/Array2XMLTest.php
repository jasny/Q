<?php
use Q\Transform_Array2XML, Q\Transform;

require_once dirname ( dirname ( dirname ( __FILE__ ) ) ) . '/TestHelper.php';
require_once 'Q/Transform/Array2XML.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Transform_Array2XMl test case.
 */
class Transform_Array2XMLTest extends PHPUnit_Framework_TestCase 
{
    /**
     * Data to transform
     * @var array
     */
    protected $dataToTransform = array ('node1' => '', 'node2' => 'test2', 'node3' => array ('node31' => 'test31', 'node32' => 'test32', 'node33' => array ('node331' => 'test331' ) ) ) ;
    
    /**
     * Expected result after transformation
     * @var string
     */
    protected $expectedResult = '<?xml version="1.0" encoding="ISO-8859-1"?>
<xml>
 <node1></node1>
 <node2>test2</node2>
 <node3>
  <node31>test31</node31>
  <node32>test32</node32>
  <node33>
   <node331>test331</node331>
  </node33>
 </node3>
</xml>';

    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/array2xml.txt';
	
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
	/**
	 * Tests Transform_Array2XML->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Array2XML ( array ('rootNodeName' => 'xml' ) );
		$contents = $transform->process ($this->dataToTransform);
        
        $this->assertType('Q\Transform_Array2XML', $transform);
		$this->assertXmlStringEqualsXmlString($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_XSL->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Array2XML(array ('rootNodeName' => 'xml' ));
		ob_start();
		$transform->output ($this->dataToTransform);
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Array2XML', $transform);
        $this->assertXmlStringEqualsXmlString($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_Array2XML->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Array2XML ( array ('rootNodeName' => 'xml' ) );
		$transform->save ($this->filename, $this->dataToTransform);
		
        $this->assertType('Q\Transform_Array2XML', $transform);
		$this->assertXmlStringEqualsXmlString($this->expectedResult, file_get_contents($this->filename));
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_Array2XMLTest::main') Transform_Array2XMLTest::main ();
