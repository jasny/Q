<?php
use Q\Transform_Text2HTML, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Text2HTML.php';
require_once 'Q/Fs/Node.php';

/**
 * Transform_Text2HTML test case.
 */
class Transform_Text2HTMLTest extends PHPUnit_Framework_TestCase
{    
	/**
	 * Tests Transform_Text2HTML->process()
	 */
    public function testProcess()
    {
        $transform = new Transform_Text2HTML();
        $contents = $transform->process("Lorem ipsum dolor sit amet, ana@mail.car.com.  
    sed do ... www.test.com magna aliqua . 
        https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist
        http://test.com/aaaa/b.txt
    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. 
        aha@xxx.com");
        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals('Lorem ipsum dolor sit amet, <a href="mailto:ana@mail.car.com">ana@mail.car.com</a>.  <br />
    sed do ... <a href="www.test.com">www.test.com</a> magna aliqua . <br />
        <a href="https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist">https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist</a><br />
        <a href="http://test.com/aaaa/b.txt">http://test.com/aaaa/b.txt</a><br />
    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. <br />
        <a href="mailto:aha@xxx.com">aha@xxx.com</a>'
        , $contents);
    }
    
    /**
     * Tests Transform_Text2HTML->process()
     */
    public function testProcess_DontConvert_Emails_And_Links()
    {
        $transform = new Transform_Text2HTML(array('convertEmail'=>false, 'convertLink'=>false));
        $contents = $transform->process("Lorem ipsum dolor sit amet, ana@mail.car.com.  
    sed do ... www.test.com magna aliqua . 
        https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist
        http://test.com/aaaa/b.txt
    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. 
        aha@xxx.com");
        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals('Lorem ipsum dolor sit amet, ana@mail.car.com.  <br />
    sed do ... www.test.com magna aliqua . <br />
        https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist<br />
        http://test.com/aaaa/b.txt<br />
    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. <br />
        aha@xxx.com'
        , $contents);
    }
    
    /**
     * Tests Transform_Text2HTML->process()
     */
    public function testProcess_Fs() 
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, 'Lorem ipsum dolor sit amet');

        $file = $this->getMock('Q\Fs_Node', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
        $file->expects($this->any())->method('getContents')->will($this->returnValue(file_get_contents($this->tmpfile)));
        
        $transform = new Transform_Text2HTML();

        $contents = $transform->process($file);

        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals('Lorem ipsum dolor sit amet', $contents);
    }

    /**
     * Tests Transform_text2HTML->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue("Lorem ipsum dolor sit amet, ana@mail.car.com.  
    sed do ... www.test.com magna aliqua . 
        https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist
        http://test.com/aaaa/b.txt
    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. 
        aha@xxx.com"));
        
        $transform = new Transform_Text2HTML();
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals('Lorem ipsum dolor sit amet, <a href="mailto:ana@mail.car.com">ana@mail.car.com</a>.  <br />
    sed do ... <a href="www.test.com">www.test.com</a> magna aliqua . <br />
        <a href="https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist">https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist</a><br />
        <a href="http://test.com/aaaa/b.txt">http://test.com/aaaa/b.txt</a><br />
    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. <br />
        <a href="mailto:aha@xxx.com">aha@xxx.com</a>', $contents);
    }
    
    /**
     * Tests Transform_Text2HTML->process() with wrong data
     */
    public function testProcess_Exception_WrongData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to start text transformation: Incorrect data provided");
        $transform = new Transform_Text2HTML();
        $contents = $transform->process(array());
    }

    /**
     * Tests Transform_Text2HTML->process() with an empty file data
     */
    public function testProcess_Exception_EmptyFileData() 
    {
    	
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, '');

        $file = $this->getMock('Q\Fs_Node', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));
        $file->expects($this->once())->method('getContents')->will($this->returnValue(file_get_contents($this->tmpfile)));
        
    	$this->setExpectedException('Q\Transform_Exception', "Unable to start text file transformation: empty data");
        $transform = new Transform_Text2HTML();
        $contents = $transform->process($file);
    }
    
	/**
	 * Tests Transform_Text2HTML->output()
	 */
	public function testOutput()
	{
		$transform = new Transform_Text2HTML();
		ob_start();
		try {
            $transform->output('Lorem ipsum dolor sit amet, ana@mail.car.com. \'sed do ... "www.test.com magna aliqua".');
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals('Lorem ipsum dolor sit amet, <a href="mailto:ana@mail.car.com">ana@mail.car.com</a>. \'sed do ... &quot;<a href="www.test.com">www.test.com</a> magna aliqua&quot;.', $contents);
	}

    /**
     * Tests Transform_Text2HTML->save()
     */
    public function testSave() 
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
    	$transform = new Transform_Text2HTML();
        $transform->save($this->tmpfile, 'Lorem ipsum dolor sit amet, ana@mail.car.com. \'sed do ... "www.test.com magna aliqua".');
                
        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals('Lorem ipsum dolor sit amet, <a href="mailto:ana@mail.car.com">ana@mail.car.com</a>. \'sed do ... &quot;<a href="www.test.com">www.test.com</a> magna aliqua&quot;.', file_get_contents($this->tmpfile));
    }

    /**
     * Tests Transform_Text2HTML->getReverse()
     */
    public function testGetReverse() 
    {
        $mock = $this->getMock('Q\Transform', array('getReverse', 'process'));
        $mock->expects($this->once())->method('getReverse')->with($this->isInstanceOf('Q\Transform_HTML2Text'))->will($this->returnValue('reverse of mock transformer'));
        
        $transform = new Transform_Text2HTML();
        $transform->chainInput($mock);
        
        $this->assertEquals('reverse of mock transformer', $transform->getReverse());
    }
}
