<?php
use Q\Fs, Q\Fs_Node, Q\Fs_Char, Q\Fs_Exception, Q\ExecException;

require_once __DIR__ . '/../init.php';
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
        $this->file = "/dev/zero";
        $this->Fs_Node = new Fs_Char($this->file);

		parent::setUp();
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
    	$this->setExpectedException('Q\Fs_Exception', "Unable to output the contents of '{$this->file}': File is a character device");
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
     * Tests Fs_Node::chown()
     */
    public function testChown()
    {}

    /**
     * Tests Fs_Node::chgrp()
     */
    public function testChgrp()
    {}

    /**
     * Tests Fs_Node::chown() with user:group
     */
    public function testChown_Chgrp()
    {}

    /**
     * Tests Fs_Node->copy()
     */
    public function testCopy()
    {
        $this->setExpectedException("Q\Fs_Exception", "Unable to copy '{$this->file}': File is a character device");
        $new = $this->Fs_Node->copy("{$this->file}.x");
    }    
}
