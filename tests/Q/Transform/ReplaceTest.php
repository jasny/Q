<?php
use Q\Transform_Replace, Q\Transform;

require_once dirname(dirname(dirname(__FILE__))) . '/TestHelper.php';
require_once 'Q/Transform/Replace.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Transform_Replace test case.
 */
class Transform_ReplaceTest extends PHPUnit_Framework_TestCase
{    
    /**
     * Template content for process and save method test
     * @var string
     */
    protected $templateForProcess = <<<HTML
<body>
  Hello i'm %{name}. I was very cool @ %{a}.
</body>
HTML;
	
    /**
     * Data to transform for process and save method test
     * @var string
     */
    protected $dataToTransformForProcess = array('a'=>19, 'name'=>"arnold");
    
    /**
     * Expected result after transformation for process and save method test
     * @var string
     */
    protected $expectedResultForProcess = "<body>
  Hello i'm arnold. I was very cool @ 19.
</body>";
        
    /**
     * Template content for process and save method test
     * @var string
     */
    protected $templateForOutput = <<<HTML
<body>
<div>###title###</div>
<div>###content###</div>
<div>###note###</div>
</body>
HTML;
    
    /**
     * Data to transform 
     * @var string
     */
    protected $dataToTransformForOutput = array('content'=>'>This is the content', 'title'=>'This is the title', 'note'=>'This is the footer');
    
    /**
     * Expected result after transformation
     * @var string
     */
    protected $expectedResultForOutput = '<body>
<div>This is the title</div>
<div>>This is the content</div>
<div>This is the footer</div>
</body>';
    
    /**
     * Marker for output transformation test
     * @var string
     */
    protected $markerForOutput = '###%s###';
    
    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/replace.txt';
	
	/**
	 * Run test from php
	 */
    public static function main() 
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    public function shutDown()
    {
    	ob_end_clean();
    }
    
	/**
	 * Tests Transform_Replace->process()
	 */
    public function testProcess ()
    {
        $transform = new Transform_Replace();
        $transform->template = $this->templateForProcess;
        $contents = $transform->process($this->dataToTransformForProcess);

        $this->assertType('Q\Transform_Replace', $transform);
        $this->assertEquals($this->expectedResultForProcess, $contents);
    }


	/**
	 * Tests Transform_Replace->output() with a simple string
	 */
	public function testOutput()
	{
		$transform = new Transform_Replace(array('template'=>$this->templateForOutput, 'marker' => $this->markerForOutput));
		ob_start();
        $transform->output($this->dataToTransformForOutput);
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertEquals($this->expectedResultForOutput, $contents);
	}

    /**
     * Tests Transform_Array2XML->save()
     */
    public function testSave() 
    {
        $transform = new Transform_Replace(array('template'=>$this->templateForProcess));
        $transform->save($this->filename, $this->dataToTransformForProcess);
                
        $this->assertEquals($this->expectedResultForProcess, file_get_contents($this->filename));
    }
	

    /**
     * Tests Chain 
     */
    public function testChain()
    {
/*
    	$template = $templateForProcess = <<<HTML
<body>
  Hello i'm %{a}. I was very cool @ %{b}.
</body>
HTML;
        $templatePHP = '<?php $data = array("a" => $title, "b" => $content ) echo $data; ?>';
    	
        $transform = Transform::with('replace');
        $transform->template = $template;
        $transform->chain(Transform::with('php', array('template' => $templatePHP)));      
        ob_start();
        $transform->output(array('title'=>'arnold', 'content' => '19'));
        $contents = ob_get_contents();
        ob_end_clean();
        
        $this->assertType('Q\Transform_XSL', $transform);
        $this->assertEquals($this->expectedResult, $contents);
*/
    }    
}

if (PHPUnit_MAIN_METHOD == 'Transform_ReplaceTest::main') Transform_ReplaceTest::main();

