<?php
use Q\Transform_Serialize_XML, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
require_once 'Q/Transform/Serialize/XML.php';

/**
 * Transform_Serialize_XML test case.
 */
class Transform_Serialize_XMLTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
	/**
	 * Tests Transform_Serialize_XML->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Serialize_XML( array ('rootNodeName' => 'xml' ) );
		$contents = $transform->process(array('node1' => '', 'node2' => 'test2', 'node3' => array('node31' => 'test31', 'node32' => 'test32', 'node33' => array('node331' => 'test331'))));

		$this->assertType('Q\Transform_Serialize_XML', $transform);
		$this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="ISO-8859-1"?>
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
</xml>', $contents);
	}
	
	/**
	 * Tests Transform_Serialize_XML->output()
	 */
	public function testOutput() 
	{
		$transform = new Transform_Serialize_XML();
		ob_start();
		try{
    		$transform->output(array('node1'=>'node1_value', 'node3'=>array('node31'=>array('node311'=>'node311_value'))));
    	} catch (Expresion $e) {
    	    ob_end_clean();
    	    throw $e;
    	}
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Serialize_XML', $transform);
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="ISO-8859-1"?>
<root>
 <node1>node1_value</node1>
 <node3>
  <node31>
    <node311>node311_value</node311>
  </node31>
  </node3>
</root>', $contents);
	}
	
	/**
	 * Tests Transform_Serialize_XML->save()
	 */
	public function testSave() 
	{
		$transform = new Transform_Serialize_XML();
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		$transform->save($this->tmpfile, array('node1'=>'value1', 'node2'=>'value2'));
		
        $this->assertType('Q\Transform_Serialize_XML', $transform);
		$this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="ISO-8859-1"?>
<root>
 <node1>value1</node1>
 <node2>value2</node2>
</root>', file_get_contents($this->tmpfile));
	}

	/**
	 * Tests Transform_Serialize_XML->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Serialize_XML();
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Unserialize_XML', $reverse);
        $this->assertEquals(array('node1'=>'value1', 'node2'=>'value2'), $reverse->process('<?xml version="1.0" encoding="ISO-8859-1"?>
<root>
 <node1>value1</node1>
 <node2>value2</node2>
</root>'));
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_Serialize_XMLTest::main') Transform_Serialize_XMLTest::main();
