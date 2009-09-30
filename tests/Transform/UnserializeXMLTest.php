<?php
use Q\Transform_Unserialize_XML, Q\Transform;

require_once dirname ( dirname ( __FILE__ ) ) . '/TestHelper.php';
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
        
var_dump($transform->process('<?xml version="1.0" encoding="UTF-8"?>
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
')); exit;        
        
        
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
        $this->assertEquals('<?xml version="1.0" encoding="ISO-8859-1"?>
<settings>
 <grp1>
  <q>abc</q>
  <b>27</b>
 </grp1>
 <grp2>
  <a>original</a>
 </grp2>
</settings>
', $reverse->process(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original'))));
	}
}

if (PHPUnit_MAIN_METHOD == 'Transform_Unserialize_XMLTest::main') Transform_Unserialize_XMLTest::main();
