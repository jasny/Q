<?php
use Q\Transform_XSL, Q\Transform;

require_once 'TestHelper.php';
require_once 'Q/Transform/XSL.php';
require_once 'Q/Fs/Node.php';

/**
 * Transform_XSL test case.
 */
class Transform_XSLTest extends PHPUnit_Framework_TestCase
{    
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
		$transform = new Transform_XSL(array('template' => '<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="article/title"/></title></head>
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
'));
		$contents = $transform->process('<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE article PUBLIC "-//OASIS//DTD Simplified DocBook XML V4.1.2.5//EN"
"http://www.oasis-open.org/docbook/xml/simple/4.1.2.5/sdocbook.dtd" >
<article>
  <title>A Short Example</title>

  <section>
    <title>Section #1</title>

    <para>A short example of a Simplified DocBook file.</para>
  </section>
</article>
');
		
		$this->assertType('Q\Transform_XSL', $transform);
		$this->assertEquals('<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>A Short Example</title>
</head>
<body>
  <h1>A Short Example</h1>

  
    <h2>Section #1</h2>

    <P>A short example of a Simplified DocBook file.</P>
  
</body>
</html>
', $contents);
	}
    
    /**
     * Tests Transform_XSL->process() with a chain
     */
    public function testProcess_Chain() 
    {
        $mock = $this->getMock('Q\Transform', array('process'));
        $mock->expects($this->once())->method('process')->with($this->equalTo('test'))->will($this->returnValue('<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE article PUBLIC "-//OASIS//DTD Simplified DocBook XML V4.1.2.5//EN"
"http://www.oasis-open.org/docbook/xml/simple/4.1.2.5/sdocbook.dtd" >
<article>
  <title>A Short Example</title>
</article>
'));
        
        $transform = new Transform_XSL(array('template' => '<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="article/title"/></title></head>
      </html>
    </xsl:template>
</xsl:stylesheet>
'));
        $transform->chainInput($mock);
        $contents = $transform->process('test');

        $this->assertType('Q\Transform_XSL', $transform);
        $this->assertEquals('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>A Short Example</title>
</head></html>
', $contents);
    }

    /**
     * Tests Transform_XSL->process() using data as an array
     */
    public function testProcess_ArrayData() 
    {
        $transform = new Transform_XSL(array('rootNodeName'=>'article', 'template' => '<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="article/title"/></title></head>
      </html>
    </xsl:template>
</xsl:stylesheet>
'));
        $contents = $transform->process(array('title'=>'A Short Example'));

        $this->assertType('Q\Transform_XSL', $transform);
        $this->assertEquals('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>A Short Example</title>
</head></html>
', $contents);
    }
    
    /**
     * Tests Transform_XSL->process() with no template available
     */
    public function testProcess_Exception_NoTemplate() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to start XSL transformation : No template available");
        $transform = new Transform_XSL();
        $contents = $transform->process('<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE article PUBLIC "-//OASIS//DTD Simplified DocBook XML V4.1.2.5//EN"
"http://www.oasis-open.org/docbook/xml/simple/4.1.2.5/sdocbook.dtd" >
<article>
  <title>A Short Example</title>
</article>
');
    }
    
    /**
     * Tests Transform_XSL->process() with no data supplied
     */
    public function testProcess_Exception_NoData() 
    {
        $this->setExpectedException('Q\Transform_Exception', "Unable to start XSL transformation : No data supplied");
        $transform = new Transform_XSL(array('rootNodeName'=>'article', 'template' => '<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="article/title"/></title></head>
      </html>
    </xsl:template>
</xsl:stylesheet>
'));
        $contents = $transform->process();
    }
    
    /**
     * Tests Transform_XSL->process() - use Fs_Node object for template
     */
    public function testProcess_FsTemplate() 
    {
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($this->tmpfile, '<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="article/title"/></title></head>
      </html>
    </xsl:template>
</xsl:stylesheet>
');

        $file = $this->getMock('Q\Fs_Node', array('__toString'), array(), '', false);
        $file->expects($this->any())->method('__toString')->will($this->returnValue($this->tmpfile));

        $transform = new Transform_XSL(array('template'=> $file));
        $contents = $transform->process('<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE article PUBLIC "-//OASIS//DTD Simplified DocBook XML V4.1.2.5//EN"
"http://www.oasis-open.org/docbook/xml/simple/4.1.2.5/sdocbook.dtd" >
<article>
  <title>A Short Example</title>
</article>
');

        $this->assertType('Q\Transform_XSL', $transform);
        $this->assertEquals('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>A Short Example</title>
</head></html>
', $contents);
    }
    
	/**
	 * Tests Transform_XSL->output()
	 */
	public function testOutput()
	{
		$transform = new Transform_XSL();
		$transform->template = '<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="article/title"/></title></head>
      </html>
    </xsl:template>
</xsl:stylesheet>
';
		ob_start();
        try{
            $transform->output('<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE article PUBLIC "-//OASIS//DTD Simplified DocBook XML V4.1.2.5//EN"
"http://www.oasis-open.org/docbook/xml/simple/4.1.2.5/sdocbook.dtd" >
<article>
  <title>A Short Example</title>
</article>
');
        } catch (Expresion $e) {
            ob_end_clean();
            throw $e;
        }
        $contents = ob_get_contents();
        ob_end_clean();
        
        $this->assertType('Q\Transform_XSL', $transform);
        $this->assertEquals('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>A Short Example</title>
</head></html>
', $contents);    
	}

    /**
     * Tests Transform_XSL->save()
     */
    public function testSave()
    {
        $transform = new Transform_XSL();
        $transform->template = '<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="article/title"/></title></head>
      </html>
    </xsl:template>
</xsl:stylesheet>
';
        $this->tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        $transform->save($this->tmpfile, '<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE article PUBLIC "-//OASIS//DTD Simplified DocBook XML V4.1.2.5//EN"
"http://www.oasis-open.org/docbook/xml/simple/4.1.2.5/sdocbook.dtd" >
<article>
  <title>A Short Example</title>
</article>
');

        $this->assertEquals('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>A Short Example</title>
</head></html>
', file_get_contents($this->tmpfile));
    }  

    /**
     * Tests Transform_XSL->getReverse()
     */
    public function testGetReverse() 
    {
        $this->setExpectedException('Q\Transform_Exception', "There is no reverse transformation defined.");
        
        $transform = new Transform_XSL(array('template'=>'<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    
    <xsl:output method="html"/>
    <xsl:template match="/">
      <html>
        <head><title><xsl:value-of select="article/title"/></title></head>
      </html>
    </xsl:template>
</xsl:stylesheet>
'));
        $transform->getReverse();
    }
}
