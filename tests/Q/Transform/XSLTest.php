<?php
use Q\Transform_XSL, Q\Transform;

require_once dirname(dirname(dirname(__FILE__))) . '/TestHelper.php';
require_once 'Q/Transform/XSL.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Transform_XSL test case.
 */
class Transform_XSLTest extends PHPUnit_Framework_TestCase
{    
    /**
     * Template content
     * @var string
     */
	protected $template = '<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="title"/></title></head>
        <body>
          <xsl:apply-templates/>
        </body>
      </html>
    </xsl:template>
    
    <xsl:template match="article/title">
      <h1><xsl:value-of select="."/></h1>
    </xsl:template>
    
    <xsl:template match="section">
        <xsl:apply-templates/>
    </xsl:template>
        
        <!-- Formatting for JUST section titles -->
        <xsl:template match="section/title">
          <h2><xsl:value-of select="."/></h2>
        </xsl:template>
    
    <xsl:template match="para">
      <P><xsl:apply-templates/></P>
    </xsl:template>
</xsl:stylesheet>
	';
    /**
     * Data to transform
     * @var string
     */
    protected $dataToTransform = '<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE article PUBLIC "-//OASIS//DTD Simplified DocBook XML V4.1.2.5//EN"
"http://www.oasis-open.org/docbook/xml/simple/4.1.2.5/sdocbook.dtd" >
<article>
  <title>A Short Example</title>

  <section>
    <title>Section #1</title>

    <para>A short example of a Simplified DocBook file.</para>
  </section>
</article>
    ';
	
    /**
     * Expected result after transformation
     * @var string
     */
    protected $expectedResult = '<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title></title>
</head>
<body>
  <h1>A Short Example</h1>

  
    <h2>Section #1</h2>

    <P>A short example of a Simplified DocBook file.</P>
  
</body>
</html>
';
    
    /**
     * The file path where to save the data when run test save() method
     * @var string
     */
    protected $filename = '/tmp/xsl.txt';
	
	/**
	 * Run test from php
	 */
    public static function main() 
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
	/**
	 * Tests Transform_XSL->process()
	 */
	public function testProcess()
	{
		$transform = new Transform_XSL(array('template' => $this->template));
		$contents = $transform->process($this->dataToTransform);
		
		$this->assertType('Q\Transform_XSL', $transform);
		$this->assertEquals($this->expectedResult, $contents);
	}

	/**
	 * Tests Transform_XSL->output()
	 */
	public function testOutput()
	{
		$transform = new Transform_XSL();
		$transform->template = $this->template;
		ob_start();
		$transform->output($this->dataToTransform);
        $contents = ob_get_contents();
        ob_end_clean();
		
        $this->assertType('Q\Transform_XSL', $transform);
        $this->assertEquals($this->expectedResult, $contents);    
	}

    /**
     * Tests Transform_XSL->save()
     */
    public function testSave()
    {
        $transform = new Transform_XSL();
        $transform->template = $this->template;
        $transform->save($this->filename, $this->dataToTransform);

        $this->assertEquals($this->expectedResult, file_get_contents($this->filename));
    }	
    
    /**
     * Test chain functionality
     * 
     */
    public function testChain()
    {
    	$transform = Transform::with('xsl:/home/carmen/projects/Q/tests/Q/Transform/test/myxsl.xsl');
		$transform->chain(Transform::with('php:/home/carmen/projects/Q/tests/Q/Transform/test/data2xml.php'));		
        ob_start();
		$transform->output(array('data' => array('title'=>'A Short Example', 'section' => 'Section #1', 'para' => 'A short example of a Simplified DocBook file.')));
	    $contents = ob_get_contents();
	    ob_end_clean();

        $this->assertType('Q\Transform_XSL', $transform);
        $this->assertEquals($this->expectedResult, $contents);
    }
}

if (PHPUnit_MAIN_METHOD == 'Transform_XSLTest::main') Transform_XSLTest::main();
