<?php
use Q\Transform_PHP, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/PHP.php';
require_once 'Q/Fs/Node.php';

/**
 * Transform_PHP test case.
 */
class Transform_PHPTest extends \PHPUnit_Framework_TestCase
{    
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
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, '<?php

echo "a : <br />";
var_dump($a);
echo "<br /><br /> b: <br />";

var_dump($b);

echo "<br /><br /> b elements sum: <br />";
var_dump(array_sum($b));

?>');

        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
		
		$transform = new Transform_PHP(array('file'=> $file));
		$contents = $transform->process(array('a'=>'TEST', 'b'=>array('2', '4', '7')));
		
		$this->assertType('Q\Transform_PHP', $transform);
		$this->assertEquals('a : <br />string(4) "TEST"
<br /><br /> b: <br />array(3) {
  [0]=>
  string(1) "2"
  [1]=>
  string(1) "4"
  [2]=>
  string(1) "7"
}
<br /><br /> b elements sum: <br />int(13)
', $contents);
	}

    /**
     * Tests Transform_PHP->process() with no php file available
     */
    public function testProcess_Exception_NoFile() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to start the PHP file transformation : File does not exist, is not accessable (check permissions) or is not a regular file.");
        $transform = new Transform_PHP();
        $contents = $transform->process(array());
    }

    /**
     * Tests Transform_PHP->process() with wrong data
     */
    public function testProcess_Exception_WrongData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to start the PHP file transformation : The param specified with process is not an array.");
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, '<?php

echo "a : <br />";
var_dump($a);
echo "<br /><br /> b: <br />";

var_dump($b);

echo "<br /><br /> b elements sum: <br />";
var_dump(array_sum($b));

?>');

        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
        
        $transform = new Transform_PHP();
        $transform->file = $file;
        $contents = $transform->process();
    }
    
	/**
	 * Tests Transform_PHP->output()
	 */
	public function testOutput()
	{
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, '<?php

echo "a : <br />";
var_dump($a);
echo "<br /><br /> b: <br />";

var_dump($b);

echo "<br /><br /> b elements sum: <br />";
var_dump(array_sum($b));

?>');

        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
		
        $transform = new Transform_PHP();
		$transform->file = $file;
		ob_start();
		try{
    		$transform->output(array('a'=>'TEST', 'b'=>array('2', '4', '7')));
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
		ob_end_clean();
		            
		$this->assertType('Q\Transform_PHP', $transform);
		$this->assertEquals('a : <br />string(4) "TEST"
<br /><br /> b: <br />array(3) {
  [0]=>
  string(1) "2"
  [1]=>
  string(1) "4"
  [2]=>
  string(1) "7"
}
<br /><br /> b elements sum: <br />int(13)
', $contents);            
	}

    /**
     * Tests Transform_PHP->save()
     */
    public function testSave()
    {
        $this->filename = tempnam(sys_get_temp_dir(), 'Q-');
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, '<?php

echo "a : <br />";
var_dump($a);
echo "<br /><br /> b: <br />";

var_dump($b);

echo "<br /><br /> b elements sum: <br />";
var_dump(array_sum($b));

?>');

        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
    	
        $transform = new Transform_PHP(array('file'=> $file));
        $transform->save($this->filename, array('a'=>'TEST', 'b'=>array('2', '4', '7')));

        $this->assertType('Q\Transform_PHP', $transform);
        $this->assertEquals('a : <br />string(4) "TEST"
<br /><br /> b: <br />array(3) {
  [0]=>
  string(1) "2"
  [1]=>
  string(1) "4"
  [2]=>
  string(1) "7"
}
<br /><br /> b elements sum: <br />int(13)
', file_get_contents($this->filename));
    }

    /**
     * Tests Transform_PHP->getReverse()
     */
    public function testGetReverse() 
    {
        $this->setExpectedException('Q\Transform_Exception', "There is no reverse transformation defined.");

        $this->filename = tempnam(sys_get_temp_dir(), 'Q-');
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, '<?php

echo "a : <br />";
var_dump($a);
echo "<br /><br /> b: <br />";

var_dump($b);

echo "<br /><br /> b elements sum: <br />";
var_dump(array_sum($b));

?>');

        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
        $transform = new Transform_PHP(array('file'=>$file));
        $transform->getReverse();
    }
}
