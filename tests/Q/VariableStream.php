<?php

require_once __DIR__ . '/init.inc';
require_once 'Q/VariableStream.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * VariableStream test case.
 */
class Test_VariableStream extends PHPUnit_Framework_TestCase
{
	protected $stream;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$GLOBALS['testvar'] = "This is a test";
		$this->stream = fopen('global://testvar', 'rw');
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->stream = null;
		unset($GLOBALS['testvar']);
		parent::tearDown();
	}
	
	/**
	 * Tests fread() for Q\VariableStream
	 */
	public function testStream_read()
	{
		$this->assertSame("This is a test", fread($this->stream, 256));
	}
	
	/**
	 * Tests fread() for Q\VariableStream, reading a specific length
	 */
	public function testStream_read_length()
	{
		$this->assertSame("This i", fread($this->stream, 6));
		$this->assertSame("s a test", fread($this->stream, 256));
	}

	/**
	 * Tests fread() for Q\VariableStream when the var doesn't exist
	 */
	public function testStream_read_new()
	{
		unset($GLOBALS['testvar']);
		$this->assertSame('', fread($this->stream, 6));
	}
		
	/**
	 * Tests feof() for Q\VariableStream after an fread
	 */
	public function testStream_read_eof()
	{
		$this->assertFalse(feof($this->stream), "Before read");
		
		fread($this->stream, 6);
		$this->assertFalse(feof($this->stream), "After 6 chars");
		
		fread($this->stream, 256);
		$this->assertTrue(feof($this->stream), "After reading it all");
	}
	
	/**
	 * Tests ftell() for Q\VariableStream after an fread
	 */
	public function testStream_read_tell()
	{
		$this->assertSame(0, ftell($this->stream), "Before read");
		
		fread($this->stream, 6);
		$this->assertSame(6, ftell($this->stream), "After 6 chars");

		fread($this->stream, 3);
		$this->assertSame(9, ftell($this->stream), "After 6+3 chars");

		fread($this->stream, 256);
		$this->assertSame(14, ftell($this->stream), "After reading it all");
	}
	
	
	/**
	 * Tests fwrite() for Q\VariableStream
	 */
	public function testStream_write()
	{
		fwrite($this->stream, "I'm not");
		$this->assertEquals("I'm not a test", $GLOBALS['testvar']);
	}

	/**
	 * Tests fwrite() for Q\VariableStream writing only a few chars
	 */
	public function testStream_write_length()
	{
		fwrite($this->stream, "I'm not", 3);
		$this->assertEquals("I'ms is a test", $GLOBALS['testvar']);
	}

	/**
	 * Tests fwrite() for Q\VariableStream, when the var doesn't exist
	 */
	public function testStream_write_new()
	{
		unset($GLOBALS['testvar']);
		
		fwrite($this->stream, "I'm not a test");
		$this->assertEquals("I'm not a test", $GLOBALS['testvar']);
	}
		
	/**
	 * Tests feof() for Q\VariableStream after an fwrite
	 */
	public function testStream_write_eof()
	{
		unset($GLOBALS['testvar']);
		
		$this->assertTrue(feof($this->stream), "Before write");
		
		fwrite($this->stream, "I'm not a test");
		$this->assertTrue(feof($this->stream), "After write");
	}
	
	/**
	 * Tests ftell() for Q\VariableStream after an fread
	 */
	public function testStream_write_tell()
	{
		unset($GLOBALS['testvar']);
		
		$this->assertSame(0, ftell($this->stream), "Before write");
		
		fwrite($this->stream, "I'm no", 6);
		$this->assertSame(6, ftell($this->stream), "After 6 chars");

		fwrite($this->stream, "t a", 3);
		$this->assertSame(9, ftell($this->stream), "After 6+3 chars");
	}
	
	
	/**
	 * Tests fseek() for Q\VariableStream
	 */
	public function testStream_seek()
	{
		fseek($this->stream, 6);
		$this->assertSame("s a test", fread($this->stream, 256), "SEEK_SET + 6");

		fseek($this->stream, 0);
		fread($this->stream, 6);
		fseek($this->stream, 3, SEEK_CUR);
		$this->assertSame(" test", fread($this->stream, 256), "6 + SEEK_CUR + 3");
		
		fseek($this->stream, -2, SEEK_END);
		$this->assertSame("st", fread($this->stream, 256), "SEEK_END - 2");
	}
	
	/**
	 * Tests fseek() for Q\VariableStream after an fseek
	 */
	public function testStream_seek_tell()
	{
		fseek($this->stream, 6);
		$this->assertSame(6, ftell($this->stream), "SEEK_SET + 6");
		
		fseek($this->stream, 3, SEEK_CUR);
		$this->assertSame(9, ftell($this->stream), "SEEK_CUR + 3");

		fseek($this->stream, -5, SEEK_CUR);
		$this->assertSame(4, ftell($this->stream), "SEEK_CUR - 5");

		fseek($this->stream, -2, SEEK_END);
		$this->assertSame(12, ftell($this->stream), "SEEK_END - 2");
	}
}

?>