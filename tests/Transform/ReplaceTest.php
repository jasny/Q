<?php
use Q\Transform_Replace, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/Replace.php';
require_once 'Q/Fs/Node.php';

/**
 * Transform_Replace test case.
 */
class Transform_ReplaceTest extends PHPUnit_Framework_TestCase
{    
    public function shutDown()
    {
    	ob_end_clean();
    }
    
	/**
	 * Tests Transform_Replace->process()
	 */
    public function testProcess()
    {
        $transform = new Transform_Replace();
        $transform->template = <<<HTML
<body>
  Hello i'm %{name}. I was very cool @ %{a}.
</body>
HTML;
        $contents = $transform->process(array('a'=>19, 'name'=>"arnold"));

        $this->assertType('Q\Transform_Replace', $transform);
        $this->assertEquals("<body>
  Hello i'm arnold. I was very cool @ 19.
</body>", $contents);
    }

    /**
     * Tests Transform_Replace->process() - use Fs_Node object for template
     */
    public function testProcess_FsTemplate() 
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, "<body>
  Hello i'm %{name}. I was very cool @ %{a}.
</body>");

        $file = $this->getMock('Q\Fs_Node', array('__toString', 'getContents'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));       
        $file->expects($this->any())->method('getContents')->will($this->returnValue(file_get_contents($this->tmpfile)));       
                
        $transform = new Transform_Replace();
        $transform->template = $file;
        $contents = $transform->process(array('a'=>19, 'name'=>"arnold"));
                
        $this->assertType('Q\Transform_Replace', $transform);
        $this->assertEquals("<body>
  Hello i'm arnold. I was very cool @ 19.
</body>", $contents);
    }

    /**
     * Tests Transform_Replace->process() with no template available
     */
    public function testProcess_Exception_NoTemplate() 
    {
        $this->setExpectedException('Q\Transform_Exception', 'Unable to start the replace process: No template available or wrong variable type');
        $transform = new Transform_Replace();
        $contents = $transform->process(array('a'=>19, 'name'=>"arnold"));
    }
    
    /**
     * Tests Transform_XSL->process() with wrong data type
     */
    public function testProcess_Exception_WrongData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to start the replace process : Incorect data type");
        $transform = new Transform_Replace();
        $transform->template = <<<HTML
<body>
  Hello i'm %{name}. I was very cool @ %{a}.
</body>
HTML;
        $contents = $transform->process();
    }
    
	/**
	 * Tests Transform_Replace->output() with a simple string
	 */
	public function testOutput()
	{
		$transform = new Transform_Replace(array('template'=>'<body>
<div>###title###</div>
<div>###content###</div>
<div>###note###</div>
</body>', 'marker' => '###%s###'));
		ob_start();
		try {
            $transform->output(array('content'=>'>This is the content', 'title'=>'This is the title', 'note'=>'This is the footer'));
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('<body>
<div>This is the title</div>
<div>>This is the content</div>
<div>This is the footer</div>
</body>', $contents);
	}

    /**
     * Tests Transform_Array2XML->save()
     */
    public function testSave() 
    {
    	$this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        $transform = new Transform_Replace(array('template'=>"<body>
  Hello i'm %{name}. I was very cool @ %{a}.
</body>"));
        $transform->save($this->tmpfile, array('a'=>19, 'name'=>"arnold"));
                
        $this->assertEquals("<body>
  Hello i'm arnold. I was very cool @ 19.
</body>", file_get_contents($this->tmpfile));
    }

    /**
     * Tests Transform_XSL->getReverse()
     */
    public function testGetReverse() 
    {
        $this->setExpectedException('Q\Transform_Exception', "There is no reverse transformation defined.");
        
        $transform = new Transform_Replace();
        $transform->getReverse();
    }
}
