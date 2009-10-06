<?php
use Q\Fs, Q\Fs_Node, Q\Fs_Char, Q\Fs_Exception, Q\ExecException;

require_once 'Fs/NodeTest.php';
require_once 'Q/Fs/Char.php';

/**
 * Fs_Char test case.
 */
class Fs_CharTest extends Fs_NodeTest
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = "/dev/null";
        $this->Fs_Node = new Fs_Char($this->file);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Fs_Node = null;
    }
	
    
    /**
     * Tests Fs_Node->getContents()
     */
    public function testGetContents()
    {
        $this->assertEquals("\0", $this->Fs_Node->getContents());
    }

    /**
     * Tests Fs_Node->getContents() with maxlen = 100
     */
    public function testGetContents_maxlen()
    {
        $this->assertEquals(str_repeat("\0", 100), $this->Fs_Node->getContents(0, 0, 100));
    }
    
    /**
     * Tests Fs_Node->putContents()
     */
    public function testPutContents()
    {
        $this->Fs_Node->putContents('Test put contents');
    }

    /**
     * Tests Fs_Node->output()
     */
    public function testOutput()
    {
    	$this->setExpectedException('Fs_Exception', "Unable to output the contents of '{$this->file}': File is a char device");
        $this->Fs_Node->output();
    }

    /**
     * Tests Fs_Node->open()
     */
    public function testOpen()
    {
        $fp = $this->Fs_Node->open();
        $this->assertTrue(is_resource($fp), "File pointer $fp");
        $this->assertEquals(str_repeat("\0", 100), fread($fp, 100));
    }    
}
