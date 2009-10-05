<?php
use Q\Transform_Unserialize_XML, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Unserialize/XML.php';

/**
 * Transform_Unserialize_XML test case.
 */
class Transform_Unserialize_XMLTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
	/**
	 * Tests Transform_Unserialize_XML->process()
	 */
	public function testProcess() 
	{
		$transform = new Transform_Unserialize_XML ();
		$contents = $transform->process('<?xml version="1.0" encoding="ISO-8859-1"?>
<settings>
 <grp1>
  <q>abc</q>
  <b>27</b>
 </grp1>
 <grp2>
  <a>original</a>
 </grp2>
</settings>
');
        $this->assertType('Q\Transform_Unserialize_XML', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
	}
	
    /**
     * Tests Transform_Unserialize_XML->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('<?xml version="1.0" encoding="ISO-8859-1"?>
<settings>
 <grp1>
  <q>abc</q>
  <b>27</b>
 </grp1>
 <grp2>
  <a>original</a>
 </grp2>
</settings>
'));
        
        $transform = new Transform_Unserialize_XML;
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Unserialize_XML', $transform);
        $this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
    }
	
	/**
	 * Tests Transform_Unserialize_XML->process()
	 */
	public function testProcess_withFs() 
	{
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		file_put_contents($this->tmpfile, '<?xml version="1.0" encoding="ISO-8859-1"?>
<settings>
 <grp1>
  <q>abc</q>
  <b>27</b>
 </grp1>
 <grp2>
  <a>original</a>
 </grp2>
</settings>
');
		
        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));		
		
		$transform = new Transform_Unserialize_XML();
		$contents = $transform->process($file);
//		var_dump($contents); exit;
        $this->assertType('Q\Transform_Unserialize_XML', $transform);
		$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $contents);
	}

    /**
     * Tests Transform_Unserialize_XML->process() with invalid data
     */
    public function testProcess_Exception_InvalidData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to transform XML into Array: Incorect data type");
        $transform = new Transform_Unserialize_XML();
        $contents = $transform->process(array());
    }
    
	/**
	 * Tests Transform_Unserialize_XML->output()
	 */
	public function testOutput() 
	{
        $this->setExpectedException('Q\Transform_Exception', "Transformation returned a non-scalar value of type 'array'");
		$transform = new Transform_Unserialize_XML();
    	$transform->output('<?xml version="1.0" encoding="ISO-8859-1"?>
<settings>
 <grp1>
  <q>abc</q>
  <b>27</b>
 </grp1>
 <grp2>
  <a>original</a>
 </grp2>
</settings>
');
	}
		
	/**
	 * Tests Transform_Unserialize_XML->save()
	 */
	public function testSave() 
	{
        $this->setExpectedException('Q\Transform_Exception', "Transformation returned a non-scalar value of type 'array'");
		$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
		
        $transform = new Transform_Unserialize_XML ();
		$transform->save ($this->tmpfile, '<?xml version="1.0" encoding="ISO-8859-1"?>
<settings>
 <grp1>
  <q>abc</q>
  <b>27</b>
 </grp1>
 <grp2>
  <a>original</a>
 </grp2>
</settings>
');
	}

    public function testMapping()
    {
        $transform = new Transform_Unserialize_XML(array(
            'map' => array('extra'=>'value'), 
            'mapkey' => array('table_def'=>"'#table'", 'field'=>'@name', 'alias'=>"'#alias:'.@name", 'def'=>'@id')));

        $this->assertEquals(array('#table'=>array('description'=>'Alias', 'filter'=>'status = 1'), 'description'=>array('name'=>'description', 'type'=>'string', 'datatype'=>'alphanumeric', 'description'=>'Name', 'extra'=>'yup'), '#alias:xyz'=>array('name'=>'xyz', 'description'=>'Description XYZ')), $transform->process('<?xml version="1.0" encoding="UTF-8"?>
<config>
    <table_def>
        <description>Alias</description>
        <filter>status = 1</filter>
    </table_def>
    
    <field name="description">
        <type>string</type>
        <datatype>alphanumeric</datatype>
        <description>Name</description>
        <extra><value>yup</value></extra>
    </field>
    
    <alias name="xyz">
        <description>Description XYZ</description>
    </alias>
</config>
'));
    }
	
	/**
	 * Tests Transform_Unserialize_XML->getReverse()
	 */
	public function testGetReverse() 
	{
		$transform = new Transform_Unserialize_XML(array('rootNodeName'=>'settings'));
        $reverse = $transform->getReverse();

        $this->assertType('Q\Transform_Serialize_XML', $reverse);
        $this->assertObjectHasAttribute('writer', $reverse);
        $this->assertObjectHasAttribute('rootNodeName', $reverse);
	}

    /**
     * Tests Transform_Unserialize_XML->getReverse() using the map and root node setted by process in the reverse action
     */
    public function testGetReverse_use_map() {
        $transform = new Transform_Unserialize_XML(array('map' => array('a'=>'value')));
        $transform->process('<?xml version="1.0" encoding="ISO-8859-1"?>
<settings>
 <grp1>
  <q>abc</q>
  <b>27</b>
 </grp1>
 <grp2>
  <a>
    <value>original</value>
  </a>
 </grp2>
</settings>
');
        $reverse = $transform->getReverse();
        
        $this->assertType('Q\Transform_Serialize_XML', $reverse);
        $this->assertObjectHasAttribute('map', $reverse);
    }
	
    /**
     * Tests Transform_Unserialize_XML->getReverse()
     */
    public function testGetReverse_Exception_mapkey() {
        $this->setExpectedException('Q\Transform_Exception', "Unable to get the reverse transformer: mapkey is not supported by Transform_Serialize_XML");
    	
        $transform = new Transform_Unserialize_XML(array('mapkey' => array('table_def'=>"'#table'", 'field'=>'@name', 'alias'=>"'#alias:'.@name", 'def'=>'@id')));
        $reverse = $transform->getReverse();
    }

    /**
     * Tests Transform_Unserialize_XML->getReverse() with a chain
     */
    public function testGetReverse_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Serialize_XML'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Unserialize_XML();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
