<?php
use Q\Transform_Unserialize_PHP, Q\Transform;

require_once dirname(dirname(__FILE__)) . '/TestHelper.php';
require_once 'Q/Transform/Unserialize/PHP.php';

/**
 * Transform_PHP test case.
 */
class Transform_Unserialize_PHPTest extends \PHPUnit_Framework_TestCase
{    
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
		$transform = new Transform_Unserialize_PHP();
		$contents = $transform->process("array (
  'a' => 'TEST',
  'b' => 
  array (
    0 => '2',
    1 => '4',
    2 => '7'
  )
);
");
		
		$this->assertType('Q\Transform_Unserialize_PHP', $transform);
		$this->assertEquals(array ( 'a'=>'TEST', 'b'=> array ( 0 => '2', 1 => '4', 2 => '7' )), $contents);
	}

    /**
     * Tests Transform_Unserialize_PHP->process() using a string for process
     */
    public function testProcess_withFs()
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, "array ( 'a', 'b' );");
        
        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       

        $transform = new Transform_Unserialize_PHP();
        $contents = $transform->process($file);

        $this->assertType('Q\Transform_Unserialize_PHP', $transform);
        $this->assertEquals(array ( 'a', 'b' ), $contents);
    }
    
	/**
	 * Tests Transform_Unserialize_PHP->process() using a string for process
	 */
	public function testProcess_withFs_and_PhpTag()
	{
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, "<?php return array ( 'a', 'b' );");
        
        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       

        $transform = new Transform_Unserialize_PHP();
		$contents = $transform->process($file);
		
		$this->assertType('Q\Transform_Unserialize_PHP', $transform);
		$this->assertEquals(array ( 'a', 'b' ), $contents);
	}

	/**
	 * Tests Transform_Unserialize_PHP->output()
	 */
	public function testOutput()
	{
        $this->setExpectedException('Q\Transform_Exception', "Unable to output data: Transformation returned a non-scalar value of type 'array'.");

        $transform = new Transform_Unserialize_PHP();
		$transform->output("array ( 'a', 'b' );");
	}

    /**
     * Tests Transform_Unserialize_PHP->save()
     */
    public function testSave()
    {
        $this->setExpectedException('Q\Transform_Exception', "Transformation returned a non-scalar value of type 'array'");
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
    	
        $transform = new Transform_Unserialize_PHP();
        $transform->save($this->tmpfile, "array ( 'a', 'b' );");
    }

    /**
     * Tests Transform_Unserialize_Json->getReverse()
     */
    public function testGetReverse() 
    {
        $transform = new Transform_Unserialize_PHP();
        $reverse = $transform->getReverse();
        $this->assertType('Q\Transform_Serialize_PHP', $reverse);
        $this->assertEquals("array ( 0 => 'a', 1 => 'b' )", $reverse->process(array('a', 'b')));
    }
}

if (PHPUnit_MAIN_METHOD == 'Transform_PHPTest::main') Transform_PHPTest::main();
