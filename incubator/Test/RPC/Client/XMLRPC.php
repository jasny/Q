<?php

require_once __DIR__ . '/../../init.inc';
require_once 'Q/StreamingConnection.php';
require_once 'Q/RPC/Client/XMLRPC.php';
require_once 'Q/VariableStream.php';

/**
 * RPC_Client_XMLRPC test case.
 */
class Test_RPC_Client_XMLRPC extends PHPUnit_Framework_TestCase
{
	/**
	 * Mock object for a stream
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $stream;
	
	/**
	 * @var Q\RPC_Client_XMLRPC
	 */
	private $RPC_Client_XMLRPC;

	
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		$GLOBALS['__xmlrpc_input'] = "";
		$GLOBALS['__xmlrpc_output'] = "";
		
		$this->stream = $this->getMock('Q\StreamingConnection', array('isOpen', 'close', 'reconnect', 'forInput', 'forOutput', 'getExtraInfo', 'about'));
		$this->stream->expects($this->any())->method('isOpen')->will($this->returnValue(true));
		$this->stream->expects($this->any())->method('forInput')->will($this->returnValue(fopen('global://__xmlrpc_input', 'r')));
		$this->stream->expects($this->any())->method('forOutput')->will($this->returnValue(fopen('global://__xmlrpc_output', 'w')));
		
		if (!($this->stream instanceof Q\StreamingConnection)) $this->markTestSkipped("Failed to make stream mock");
		
		$this->RPC_Client_XMLRPC = new Q\RPC_Client_XMLRPC($this->stream);
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->RPC_Client_XMLRPC = null;
		$this->stream = null;
		parent::tearDown();
	}

	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{
	}

	
	/**
	 * Tests RPC_Client_XMLRPC->about()
	 */
	public function testAbout()
	{
		$this->stream->expects($this->any())->method('about')->will($this->returnValue("Mock stream for unit test"));
		$this->assertEquals('Mock stream for unit test (XMLRPC)', $this->RPC_Client_XMLRPC->about());
	}

	/**
	 * Tests RPC_Client_XMLRPC->close()
	 */
	public function testClose()
	{
		$this->stream->expects($this->any())->method('close')->with();
		$this->RPC_Client_XMLRPC->close();
	}
	
	/**
	 * Tests RPC_Client_XMLRPC->execute()
	 */
	public function testExecute()
	{
		$request = xmlrpc_encode_request('test', array("abc", array(10, 11), array('x'=>"ddd", 'y'=>"eee")));
		$GLOBALS['__xmlrpc_output'] = xmlrpc_encode_request(null, 10); 
		
		$result = $this->RPC_Client_XMLRPC->getInterface()->test("abc", array(10, 11), array('x'=>"ddd", 'y'=>"eee"));
		
		$this->assertEquals(trim($request), trim($GLOBALS['__xmlrpc_input']), "XMLRPC request");
		$this->assertEquals(10, $result, "XMLRPC result");
	}
	
	/**
	 * Tests RPC_Client_XMLRPC->getExtraInfo()
	 */
	public function testGetExtraInfo()
	{
		$GLOBALS['__xmlrpc_output'] = xmlrpc_encode_request(null, 10); 

		$sxml = new SimpleXMLElement(xmlRPC_encode("test xyz"));
		$info = "Test extra info\n<extraInfo><type>test_info</type>" . $sxml->param->value->asXML() . "</extraInfo>";
		
		$this->stream->expects($this->any())->method('getExtraInfo')->will($this->returnValue($info));
		$this->RPC_Client_XMLRPC->getInterface()->test();
		
		$this->assertEquals($info, $this->RPC_Client_XMLRPC->getExtraInfo('_raw_'));
		$this->assertEquals(array((object)array('type'=>'_raw_', 'value'=>$info), (object)array('type'=>'test_info', 'value'=>"test xyz")), $this->RPC_Client_XMLRPC->getExtraInfo());
	}
}

if (PHPUnit_MAIN_METHOD == 'Test_RPC_Client_XMLRPC::main') Test_RPC_Client_XMLRPC::main();
?>