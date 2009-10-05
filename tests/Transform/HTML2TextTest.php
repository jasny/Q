<?php
use Q\Transform_HTML2Text, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/HTML2Text.php';
require_once 'Q/Fs/Node.php';

/**
 * Transform_HTML2Text test case.
 */
class Transform_HTML2TextTest extends PHPUnit_Framework_TestCase
{    
	/**
	 * Tests Transform_HTML2Text->process()
	 */
    public function testProcess()
    {
        $transform = new Transform_HTML2Text();
        $contents = $transform->process('<html>
        <header><title>Test title</title></header>
        <body>
        <p>Lorem ipsum dolor sit amet</p> <a href="mailto:ana@mail.car.com">ana@mail.car.com</a>.  <br /><br /><br />sed do ... <a href="www.test.com">www.test.com</a> magna aliqua . <br />
        <a href="https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist">https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist</a><br />
        <a href="http://test.com/aaaa/b.txt" >http://test.com/aaaa/b.txt</a><br />
    Ut enim ad minim veniam, <p>quis nostrud exercitation</p> <p>ullamco laboris nisi ut aliquip</p> ex ea commodo consequat. <br />
        <a href="mailto:aha@xxx.com">aha@xxx.com</a>        
&#126;
 I\'ll &quot;walk&quot; the &lt;b&gt;dog&lt;/b&gt; now
<ul>
  <li>Coffee</li>
  <li>Tea</li>
  <li>Milk</li>
</ul>
</body></html>');
        
        
        $this->assertType('Q\Transform_HTML2Text', $transform);
        $this->assertEquals("
        Test title
        
        Lorem ipsum dolor sit amet
 ana@mail.car.com.  


sed do ... www.test.com magna aliqua . 

        https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist

        http://test.com/aaaa/b.txt

    Ut enim ad minim veniam, quis nostrud exercitation
 ullamco laboris nisi ut aliquip
 ex ea commodo consequat. 

        aha@xxx.com        
~
 I'll \"walk\" the dog now

  Coffee
  Tea
  Milk

", $contents);
    }
    
    /**
     * Tests Transform_HTML2Text->process()
     */
    public function testProcess_Fs() 
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, 'Lorem ipsum dolor<br /> sit amet');

        $file = $this->getMock('Q\Fs_Node', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
        $file->expects($this->any())->method('getContents')->will($this->returnValue(file_get_contents($this->tmpfile)));
        
        $transform = new Transform_HTML2Text();

        $contents = $transform->process($file);

        $this->assertType('Q\Transform_HTML2Text', $transform);
        $this->assertEquals("Lorem ipsum dolor\n sit amet", $contents);
    }

    /**
     * Tests Transform_HTML2Text->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('Lorem ipsum dolor<br /> sit amet'));
        
        $transform = new Transform_HTML2Text();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_HTML2Text', $transform);
        $this->assertEquals("Lorem ipsum dolor\n sit amet", $contents);
    }
    
    /**
     * Tests Transform_HTML2Text->process() with wrong data
     */
    public function testProcess_Exception_WrongData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to start text transformation: Incorrect data provided");
        $transform = new Transform_HTML2Text();
        $contents = $transform->process(array());
    }

    /**
     * Tests Transform_HTML2Text->process() with an empty file data
     */
    public function testProcess_Exception_EmptyFileData() 
    {
    	
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, '');

        $file = $this->getMock('Q\Fs_Node', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
        $file->expects($this->once())->method('getContents')->will($this->returnValue(file_get_contents($this->tmpfile)));
        
    	$this->setExpectedException('Q\Transform_Exception', "Unable to start text file transformation: empty data");
        $transform = new Transform_HTML2Text();
        $contents = $transform->process($file);
    }
    
	/**
	 * Tests Transform_HTML2Text->output()
	 */
	public function testOutput()
	{
		$transform = new Transform_HTML2Text();
		ob_start();
		try {
            $transform->output('<p>Lorem ipsum dolor sit amet</p> <a href="mailto:ana@mail.car.com">ana@mail.car.com</a>.');
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_HTML2Text', $transform);
        $this->assertEquals("Lorem ipsum dolor sit amet\n ana@mail.car.com.", $contents);
	}

    /**
     * Tests Transform_HTML2Text->save()
     */
    public function testSave() 
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
    	$transform = new Transform_HTML2Text();
        $transform->save($this->tmpfile, '<p>Lorem ipsum dolor sit amet</p> <a href="mailto:ana@mail.car.com">ana@mail.car.com</a>.');
                
        $this->assertType('Q\Transform_HTML2Text', $transform);
        $this->assertEquals("Lorem ipsum dolor sit amet\n ana@mail.car.com.", file_get_contents($this->tmpfile));
    }

    /**
     * Tests Transform_HTML2Text->getReverse()
     */
    public function testGetReverse() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_Text2HTML'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_HTML2Text();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
