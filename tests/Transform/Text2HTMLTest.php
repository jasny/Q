<?php
use Q\Transform_Text2HTML, Q\Transform;

require_once dirname(dirname(dirname(__FILE__))) . '/TestHelper.php';
require_once 'Q/Transform/Text2HTML.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Transform_Text2XML test case.
 */
class Transform_Text2HTMLTest extends PHPUnit_Framework_TestCase
{    
    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/home/carmen/projects/text2html.html';

    /**
     * The file path of the text file that will be transformed
     * @var string
     */
    protected $file = '/home/carmen/add_handle_into_db.txt';
    
    /**
     * The text that will be transformed 
     */
    protected $template = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, ana@mail.car.com.  
    sed do eiusmod tempor incididunt ut labore et dolore www.test.com magna aliqua . 
        https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist
        http://test.com/aaaa/b.txt
    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. 
    Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. 
    Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. 
    aha@xxx.com";
    
    /**
     * Expected result after transformation of then template
     */
    protected $expectedResult=<<<HTML
Lorem ipsum dolor sit amet, consectetur adipisicing elit, <a href="mailto:ana@mail.car.com">ana@mail.car.com</a>.  <br />
    sed do eiusmod tempor incididunt ut labore et dolore <a href="www.test.com">www.test.com</a> magna aliqua . <br />
        <a href="https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist">https://admin.helderhosting.nl/index.php?error=De+gebruikersnaam%2Fwachtwoord+combinatie+is+onjuist</a><br />
        <a href="http://test.com/aaaa/b.txt">http://test.com/aaaa/b.txt</a><br />
    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. <br />
    Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. <br />
    Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. <br />
    <a href="mailto:aha@xxx.com">aha@xxx.com</a>
HTML;
    
    /**
	 * Run test from php
	 */
    public static function main() 
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
	/**
	 * Tests Transform_Text2XML->process()
	 */
    public function testProcess ()
    {
        $transform = new Transform_Text2HTML();
        $contents = $transform->process($this->template);

        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals($this->expectedResult, $contents);
    }

	/**
	 * Tests Transform_Replace->output() with a simple string
	 */
	public function testOutput()
	{
		$transform = new Transform_Text2HTML();
		ob_start();
        $transform->output($this->template);
        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals($this->expectedResult, $contents);
	}

    /**
     * Tests Transform_Array2XML->save()
     */
    public function testSave() 
    {
        $transform = new Transform_Text2HTML();
        $transform->save($this->filename, $this->template);
                
        $this->assertType('Q\Transform_Text2HTML', $transform);
        $this->assertEquals($this->expectedResult, file_get_contents($this->filename));
    }
}

if (PHPUnit_MAIN_METHOD == 'Transform_Text2HTMLTest::main') Transform_Text2HTMLTest::main();

