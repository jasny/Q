<?php
use Q\Output;

require_once 'Q/Output.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Output test case.
 * 
 * The Output interface is more quite to test, because the only way to assert output is with output buffering.
 * This is the very thing that is managed by the Output class.
 */
class OutputTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		for ($i=0, $n=ob_get_level(); $i<$n; $i++) ob_end_flush();
	}
	    
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
	    Output::clear(); 
		parent::tearDown();
	}
    
    /**
     * Tests Output::clear()
     */
    public function testClear()
    {
        $handler = $this->getMock('stdClass', array('callback'));
        $handler->expects($this->once())->method('callback')->with($this->equalTo("", PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_END));
        
        ob_start(array($handler, 'callback'));
        ob_start();
        echo "Some text";
        Output::clear();
        
        $this->assertEquals(0, ob_get_level(), "Output handler nesting level");
    }

    /**
     * Tests marking output sections; Getting current marker with no sections.
     */
    public function testMark_NotUsed()
    {
        $this->assertNull(Output::curMarker());
    }

    /**
     * Tests marking output sections
     */
    public function testMark()
    {
        Output::mark("test");
        $this->assertEquals("test", Output::curMarker());
        Output::endMark();
        
        $this->assertEquals(Output::$defaultMarker, Output::curMarker());
    }

    /**
     * Tests marking output sections; using the stack to support nesting
     */
    public function testMark_Nested()
    {
        $this->assertNull(Output::curMarker());
        
        Output::mark("nested 1");
        $this->assertEquals("nested 1", Output::curMarker());

        Output::mark("nested 1-A");
        $this->assertEquals("nested 1-A", Output::curMarker());

        Output::mark("nested 1-A-I");
        $this->assertEquals("nested 1-A-I", Output::curMarker());
        Output::endMark();
        
        $this->assertEquals("nested 1-A", Output::curMarker());

        Output::mark("nested 1-A-II");
        $this->assertEquals("nested 1-A-II", Output::curMarker());
        Output::endMark();

        $this->assertEquals("nested 1-A", Output::curMarker());
        Output::endMark();
        
        $this->assertEquals("nested 1", Output::curMarker());
        Output::endMark();

        Output::mark("nested 2");
        $this->assertEquals("nested 2", Output::curMarker());
        Output::endMark();
        
        $this->assertEquals(Output::$defaultMarker, Output::curMarker());
    }

    /**
     * Tests marking output sections; calling endMark without mark.
     */
    public function testMark_ToManyEnd()
    {
        Output::mark("test");
        Output::endMark();
        
        $this->setExpectedException('Q\Exception', "Called Output::endMark() without an Output::mark() call.");
        Output::endMark();
    }
    
    
    /**
     * Tests Output::addUrlRewriteVar()
     */
    public function testAddUrlRewriteVar()
    {
        // TODO Auto-generated OutputTest::testAddUrlRewriteVar()
        $this->markTestIncomplete("addUrlRewriteVar test not implemented");
        Output::addUrlRewriteVar(/* parameters */);
    }

    /**
     * Tests Output::resetUrlRewriteVars()
     */
    public function testResetUrlRewriteVars()
    {
        // TODO Auto-generated OutputTest::testResetUrlRewriteVars()
        $this->markTestIncomplete("resetUrlRewriteVars test not implemented");
        Output::resetUrlRewriteVars(/* parameters */);
    }

    /**
     * Tests Output::getUrlRewriteVars()
     */
    public function testGetUrlRewriteVars()
    {
        // TODO Auto-generated OutputTest::testGetUrlRewriteVars()
        $this->markTestIncomplete("getUrlRewriteVars test not implemented");
        Output::getUrlRewriteVars(/* parameters */);
    }
}

