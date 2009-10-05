<?php
use Q\Transform_Serialize_XML, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Serialize/XML.php';

/**
 * Transform_Serialize_XML test case.
 */
class Transform_Serialize_XMLTest extends PHPUnit_Framework_TestCase 
{
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
     * Tests Transform_Serialize_XML->process()
     */
    public function testProcess_Maping() 
    {
        $transform = new Transform_Serialize_XML( array ('rootNodeName' => 'settings', 'map'=>array('test' =>'val', 'a'=>'x') ) );
        $contents = $transform->process(array('test' => 'aha', 'a' => 'bla', 'b' => 'c'));

        $this->assertType('Q\Transform_Serialize_XML', $transform);
        $this->assertXmlStringEqualsXmlString('<?xml version="1.0" encoding="ISO-8859-1"?>
<settings>
 <test>
  <val>aha</val>
 </test>
 <a>
  <x>bla</x>
 </a>
 <b>c</b>
</settings>
', $contents);
    }

    /**
     * Tests Transform_Serialize_XML->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue(array('node1'=>'test1', 'node2'=>'test2')));
        
        $transform = new Transform_Serialize_XML();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Serialize_XML', $transform);
        $this->assertEquals('<?xml version="1.0" encoding="ISO-8859-1"?>
<root>
 <node1>test1</node1>
 <node2>test2</node2>
</root>
', $contents);
    }
    
    /**
     * Tests Transform_Serialize_XML->process() with invalid data
     */
    public function testProcess_Exception_InvalidData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to transform Array to XML: data is not array");
        $transform = new Transform_Serialize_XML();
        $contents = $transform->process('test');
    }
    
    /**
     * Tests Transform_Serialize_XML->process() with invalid map variable
     */
    public function testProcess_Exception_InvalidMap() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to transform Array to XML. map type string is incorect. Array is expected.");
        $transform = new Transform_Serialize_XML(array('map'=>'test'));
        $contents = $transform->process(array());
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
	}
    /**
     * Tests Transform_Serialize_XML->getReverse()
     */
    public function testGetReverse_Maping() 
    {
        $transform = new Transform_Serialize_XML( array ('rootNodeName' => 'settings', 'map'=>array('test' =>'val', 'a'=>'x') ) );
        $contents = $transform->process(array('test' => 'aha', 'a' => 'bla', 'b' => 'c'));
        $reverse = $transform->getReverse();
        
        $this->assertType('Q\Transform_Serialize_XML', $transform);
        $this->assertEquals(array('test' => 'aha', 'a' => 'bla', 'b' => 'c'), $reverse->process($transform->process(array('test' => 'aha', 'a' => 'bla', 'b' => 'c'))));
    }
        
    /**
     * Tests Transform_Serialize_XML->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Unserialize_XML'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Serialize_XML();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
