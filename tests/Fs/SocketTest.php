<?php
use Q\Fs, Q\Fs_Node, Q\Fs_Socket, Q\Fs_Exception, Q\ExecException;

require_once 'Fs/NodeTest.php';
require_once 'Q/Fs/Socket.php';

/**
 * Fs_Socket test case.
 */
class Fs_SocketTest extends Fs_NodeTest
{
	/**
	 * @var resource
	 */
	protected $socket;
	
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-fs_sockettest-' . md5(uniqid());

        $errno = null;
        $errstr = null;
		$this->socket = stream_socket_server('unix://' . $this->file, $errno, $errstr);
		if (!$this->socket) $this->markTestSkipped("Could not create socket: $errstr ($errno)");
        
        $this->Fs_Node = new Fs_Socket($this->file);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
    	fclose($this->socket);
        $this->cleanup($this->file);
        $this->Fs_Node = null;
    }

    
    /**
     * Test creating an Fs_Socket for a dir
     */
    public function testConstruct_Dir()
    {
    	$this->setExpectedException('Q\Fs_Exception', "File '".__DIR__."' is not a socket, but a " . filetype(__DIR__) . ".");
    	new Fs_Socket(__DIR__);
    }

    /**
     * Test creating an Fs_Socket for a symlink
     */
    public function testConstruct_Symlink()
    {
    	if (!symlink(__FILE__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is a symlink");
    	new Fs_Socket("{$this->file}.x");
    }

    /**
     * Test creating an Fs_Socket for a symlink to a dir
     */
    public function testConstruct_SymlinkDir()
    {
    	if (!symlink(__DIR__, "{$this->file}.x")) $this->markTestSkipped("Could not create symlink '{$this->file}.x'");
    	    	
    	$this->setExpectedException('Q\Fs_Exception', "File '{$this->file}.x' is a symlink");
    	new Fs_Socket("{$this->file}.x");
    }
    
    
    /**
     * Tests Fs_Node->getContents()
     */
    public function testGetContents()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get contents of socket '{$this->file}'. Use Fs_Socket::open() + fread() instead");
        $this->Fs_Node->getContents();
    }

    /**
     * Tests Fs_Node->putContents()
     */
    public function testPutContents()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to write contents to socket '{$this->file}'. Use Fs_Socket::open() + fwrite() instead");
        $this->Fs_Node->putContents('Test put contents');
    }

    /**
     * Tests Fs_Node->output()
     */
    public function testOutput()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to get contents of socket '{$this->file}'. Use Fs_Socket::open() + fread() instead");
        $this->Fs_Node->output();
    }

    /**
     * Tests Fs_Node->open()
     * 
     * @todo Test for Fs_Socket->open() should be improved
     */
    public function testOpen()
    {
    	$fp = $this->Fs_Node->open();    	
    	$this->assertTrue(is_resource($fp), "File pointer $fp");
    }

    /**
     * Tests Fs_Node->listen()
     * 
     * @todo Test for Fs_Socket->listen() should be improved
     */
    public function testListen()
    {
    	$node = new Fs_Socket("{$this->file}.x");
        $fp = $node->listen();       
        $this->assertTrue(is_resource($fp), "File pointer $fp");
    }

    /**
     * Tests Fs_Node->listen()
     * 
     * @todo Test for Fs_Socket->listen() should be improved
     */
    public function testListen_Exists()
    {
        $this->setExpectedException('Q\Fs_Exception', "Failed to create socket '{$this->file}': unable to connect to unix://{$this->file} (Unknown error)");
        $this->Fs_Node->listen();
    }    
    
    /**
     * Tests Fs_Node->exec()
     */
    public function testExec()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': This is not a regular file, but a " . Fs::typeOfNode($this->Fs_Node, Fs::DESCRIPTION));
    	$this->Fs_Node->exec();
    }

    /**
     * Tests Fs_Node->__invoke()
     */
    public function test__invoke()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to execute '{$this->file}': This is not a regular file, but a " . Fs::typeOfNode($this->Fs_Node, Fs::DESCRIPTION));
    	$file = $this->Fs_Node;
        $file();
    }
    
    /**
     * Tests Fs_Node->create()
     */
    public function testCreate()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to create socket '{$this->file}'. Use Fs_Socket::listen() instead");
    	$this->Fs_Node->create();
    }

    /**
     * Tests Fs_Node->create() with existing file no error
     */
    public function testCreate_Preserve()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to create socket '{$this->file}'. Use Fs_Socket::listen() instead");
    	$this->Fs_Node->create(0660, Fs::PRESERVE);
    }
    /**
     * Tests Fs_Node->copy()
     */
    public function testCopy()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to copy '{$this->file}': File is a socket");
        $this->Fs_Node->copy("{$this->file}.x");        
    }

    /**
     * Tests Fs_Node->copyTo()
     */
    public function testCopyTo()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to copy '{$this->file}': File is a socket");
    	mkdir("{$this->file}.y");
        $this->Fs_Node->copyTo("{$this->file}.y");
    }

    /**
     * Tests Fs_Node->rename()
     */
    public function testRename()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to move '{$this->file}': File is a socket");
    	$this->Fs_Node->rename("{$this->file}.x");
    }
	    
    /**
     * Tests Fs_Node->moveTo()
     */
    public function testMoveTo()
    {
        $this->setExpectedException('Q\Fs_Exception', "Unable to move '{$this->file}': File is a socket");
    	mkdir("{$this->file}.y");
        $this->Fs_Node->moveTo("{$this->file}.y");
    }

    /**
     * Tests Fs_Node::delete()
     */
    public function testDelete()
    {
        if (function_exists('posix_getuid') && posix_getuid() == 0) $this->markTestSkipped("Won't test this as root for safety reasons.");
        
        $this->Fs_Node->delete();
        $this->assertFalse(file_exists($this->file));
    }
}
