<?php

require_once __DIR__ . '/../../init.inc';
require_once 'Q/RPC/Server/XMLRPC.php';
require_once 'Q/VariableStream.php';
require_once 'Q/ExpectedException.php';
require_once 'Q/HTTP.php';

class Test_RPC_Server_XMLRPC_ExcpectedException extends Exception implements Q\ExpectedException {}

/**
 * RPC_Server_XMLRPC test case.
 */
class Test_RPC_Server_XMLRPC extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Q\RPC_Server_XMLRPC
	 */
	private $RPC_Server_XMLRPC;

	/**
	 * Object used by RPC server
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	private $object;

	/**
	 * Mock object for a stream
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $stream;	

	/**
	 * Mock object for the alternative stream
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $altstream;
	
	
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
		
		$this->object = $this->getMock('stdClass', array('test'));

		$GLOBALS['__output'] = "";
		$GLOBALS['__extra'] = "";
		$this->stream = fopen('global://__output', 'w');
		$this->altstream = fopen('global://__extra', 'w');
		
		$this->RPC_Server_XMLRPC = new Q\RPC_Server_XMLRPC(array('stream'=>$this->stream, 'altstream'=>$this->altstream));
		$this->RPC_Server_XMLRPC->setObject($this->object);
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->RPC_Server_XMLRPC = null;
		$this->object = null;
		parent::tearDown();
	}

	
	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{
	}

	
	/**
	 * Tests RPC_Server_XMLRPC->handle()
	 */
	public function testHandle()
	{
		$this->object->expects($this->any())->method('test')->with("abc", array(10, 11), array('x'=>"ddd", 'y'=>"eee"))->will($this->returnValue(10));
		
		$request = xmlrpc_encode_request('test', array("abc", array(10, 11), array('x'=>"ddd", 'y'=>"eee")));
		$this->RPC_Server_XMLRPC->handle($request);

		$this->assertEquals(10, xmlrpc_decode($GLOBALS['__output']));
	}

	/**
	 * Tests RPC_Server_XMLRPC->handle() when an exception it thrown
	 */
	public function testHandleException()
	{
		$exception = new Exception("test-error");
		$this->object->expects($this->any())->method('test')->with()->will($this->throwException($exception));
		
		try {
			$this->RPC_Server_XMLRPC->handle(xmlrpc_encode_request('test', array()));
		} catch (Exception $e) {
			$this->fail("Exception was not caught");
		}

		$this->assertEquals(xmlrpc_encode(array("faultCode"=>-1, "faultString"=>"Unexpected exception.")), $GLOBALS['__output']);
		$this->assertEquals("<extraInfo><type>X-Exception</type><value><string>test-error</string></value></extraInfo>", str_replace(array("\n", " "), '', $GLOBALS['__extra']));
	}
	
	/**
	 * Tests RPC_Server_XMLRPC->handle() when an expected exception it thrown
	 */
	public function testHandleExpectedException()
	{
		$exception = new Test_RPC_Server_XMLRPC_ExcpectedException("Couldn't do it", 100);
		$this->object->expects($this->any())->method('test')->with()->will($this->throwException($exception));
		
		try {
			$this->RPC_Server_XMLRPC->handle(xmlrpc_encode_request('test', array()));
		} catch (Exception $e) {
			$this->fail("Exception was not caught");
		}

		$this->assertEquals(array("faultCode"=>100, "faultString"=>"Couldn't do it"), xmlrpc_decode($GLOBALS['__output']));
	}

	/**
	 * Tests RPC_Server_XMLRPC->putExtraInfo()
	 */
	public function testPutExtraInfo()
	{
		$this->RPC_Server_XMLRPC->putExtraInfo('test', 'Some-info');
		$this->assertEquals("<extraInfo><type>test</type><value><string>Some-info</string></value></extraInfo>", str_replace(array("\n", " "), '', $GLOBALS['__extra']));
	}
}

if (PHPUnit_MAIN_METHOD == 'Test_RPC_Server_XMLRPC::main') Test_RPC_Server_XMLRPC::main();
?>