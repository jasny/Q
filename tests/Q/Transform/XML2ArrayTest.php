<?php
use Q\Transform_XML2Array, Q\Transform;

require_once dirname(dirname(dirname(__FILE__))) . '/TestHelper.php';
require_once 'Q/Transform/XML2Array.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Transform_Array2XMl test case.
 */
class Transform_XML2ArrayTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data to transform
	 * @var string
	 */
	protected $dataToTransform = '<?xml version="1.0" encoding="ISO-8859-1"?>
<root>
 <a></a>
 <b>test2</b>
 <c>
  <d>test31</d>
  <e>test32</e>
  <f>
   <g>test331</g>
  </f>
 </c>
</root>';

	/**
	 * Expected result after transformation
	 * @var array
	 */
	protected $expectedResult = Array(
			'a' => '', 
			'b' => 'test2', 
			'c' => Array(
					'd' => 'test31', 
					'e' => 'test32', 
					'f' => Array(
							'g' => 'test331')));
	
	/**
	 * The file path where to save the data when run test save() method
	 * @var string
	 */
	protected $filename = '/tmp/xml2array.txt';
	
	/**
	 * Run test from php
	 */
	public static function main()
	{
		PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
	}
	
	/**
	 * Tests Transform_Array2XML->process()
	 */
	public function testProcess()
	{
		$transform = new Transform_XML2Array();
		$contents = $transform->process($this->dataToTransform);

		$this->assertType('Q\Transform_XML2Array', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}
	
	/**
	 * Tests Transform_XSL->output()
	 */
	public function testOutput()
	{
/*
		$transform = new Transform_XML2Array();
		ob_start();
		$transform->output($this->dataToTransform);
		$contents = ob_get_contents();
		ob_end_clean();

		$this->assertType('Q\Transform_XML2Array', $transform);
		$this->assertEquals($this->expectedResult, (array)$contents);
*/
	}
	
	/**
	 * Tests Transform_Array2XML->save()
	 */
	public function testSave()
	{
/*
		$transform = new Transform_XML2Array();
		$transform->save($this->filename, $this->dataToTransform);
		
		$this->assertType('Q\Transform_XML2Array', $transform);
		$this->assertEquals($this->expectedResult, file_get_contents($this->filename));
*/
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_XML2ArrayTest::main') Transform_XML2ArrayTest::main();
