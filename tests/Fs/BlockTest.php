<?php
use Q\Fs, Q\Fs_Node, Q\Fs_Block, Q\Fs_Exception, Q\ExecException;

require_once 'Fs/NodeTest.php';
require_once 'Q/Fs/Block.php';

/**
 * Fs_Block test case.
 */
class Fs_BlockTest extends Fs_NodeTest
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = "/dev/loop0";
        $this->Fs_Node = new Fs_Block($this->file);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Fs_Node = null;
    }

    

    /**
     * Tests Fs_Node->getContents() with maxlen = 100
     */
    public function testGetContents_maxlen()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get the contents of '{$this->file}': File is a block device");
        $this->Fs_Node->getContents();
    }
    
    /**
     * Tests Fs_Node->putContents()
     */
    public function testPutContents()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to write data to '{$this->file}': File is a block device");
    	$this->Fs_Node->putContents('Test put contents');
    }

    /**
     * Tests Fs_Node->output()
     */
    public function testOutput()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to output data from '{$this->file}': File is a block device");
        $this->Fs_Node->output();
    }

    /**
     * Tests Fs_Node->open()
     */
    public function testOpen()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to open '{$this->file}': File is a block device");
    	$fp = $this->Fs_Node->open();
    }    

    /**
     * Tests Fs_Node->touch()
     */
    public function testTouch()
    {}

    /**
     * Tests Fs_Node->touch() with atime
     */
    public function testTouch_atime()
    {}

    /**
     * Tests Fs_Node->touch() with DateTime
     */
    public function testTouch_DateTime()
    {}    

    /**
     * Tests Fs_Node->touch() with strings
     */
    public function testTouch_string()
    {}

    /**
     * Tests Fs_Node->setAttribute()
     */
    public function testSetAttribute()
    {}
    
    /**
     * Tests Fs_Node->testClearStatCache()
     */
    public function testClearStatCache()
    {}
    
    /**
     * Tests Fs_Node->offsetSet()
     */
    public function testOffsetSet()
    {}

    /**
     * Tests Fs_Node::chmod()
     */
    public function testChmod()
    {}
    
    /**
     * Tests Fs_Node::chmod() with string
     */
    public function testChmod_string()
    {}
    
    /**
     * Tests Fs_Node::chmod() with invalid string
     */
    public function testChmod_invalidString()
    {}

    /**
     * Tests Fs_Node->copy()
     */
    public function testCopy()
    {
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}': File is a block device");
        $new = $this->Fs_Node->copy("{$this->file}.x");
    }
}
