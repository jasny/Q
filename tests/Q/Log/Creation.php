<?php
use Q\Log;

require_once __DIR__ . '/../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Log.php';

/**
 * Test factory method
 */
class Test_Log_Creation extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
	/**
	 * Tests Log::extractDSN(), driver only
	 */
	public function testExtractDSN_DriverOnly()
	{
		$this->assertSame(array('test', array(), array(), array()), Log::extractDSN('test'));
	}
	
	/**
	 * Tests Log::extractDSN(), DSN to args
	 */
	public function testExtractDSN_SimpleDSN()
	{
		$this->assertSame(array('test', array('abc', 'def', 'xyz'), array(), array()), Log::extractDSN('test:abc;def;xyz'));
	}
	
	/**
	 * Tests Log::extractDSN(), DSN to args, filters and properties
	 */
	public function testExtractDSN_FullDSN()
	{
		$this->assertSame(array('test', array('abc', 'def', 'xyz', 'final'), array('error', '!notice'), array('alias'=>array('sql'=>'info', ""=>'debug'), 'format'=>"ABD DEF")), Log::extractDSN('test:abc;def;xyz; filter[]=error ;filter[]=!notice; alias[sql]=info; alias[""]=debug; format = "ABD DEF"; final'), "Filter array");
		$this->assertSame(array('test', array('abc', 'def', 'xyz', 'final'), array('error', '!notice'), array('alias'=>array('sql'=>'info', ""=>'debug'), 'format'=>"ABD DEF")), Log::extractDSN('test:abc;def;xyz; filter=error,!notice; alias[sql]=info; alias[""]=debug; format = "ABD DEF"; final'), "Filter combined");
	}

	/**
	 * Tests Log::extractDSN(), using an array instead of a string
	 */
	public function testExtractDSN_Array()
	{
		$this->assertSame(array('test', array('abc', 'def', 'xyz', 'final'), array('error', '!notice'), array('alias'=>array('sql'=>'info', ""=>'debug'), 'format'=>"ABD DEF")), Log::extractDSN(array('driver'=>'test', 'abc', 'def', 'xyz', 'filter'=>'error, !notice', 'alias'=>array('sql'=>'info', null=>'debug'), 'format'=>"ABD DEF", 'final')));
	}
	
	/**
	 * Tests Log::using()
	 */
	public function testCreate()
	{
		$this->assertType('Q\Log_Header', Log::to('header'));
		$this->assertType('Q\Log_Text', Log::to('output'));
	}

	/**
	 * Tests Log::using() specifying args
	 */
	public function testPath()
	{
		$log = Log::to('file:/tmp/test.log');
		$this->assertType('Q\Log_Text', $log);

		$this->assertAttributeEquals('/tmp/test.log', 'file', $log);
	}

	/**
	 * Tests Log::using() specifying args
	 */
	public function testDriverOptions()
	{
		$log = Log::to('logfile:/tmp/test.log');
		$this->assertType('Q\Log_Text', $log);

		$this->assertAttributeEquals('/tmp/test.log', 'file', $log);
		$this->assertAttributeEquals(Log::$drivers['logfile']['format'], 'format', $log);
	}
	
    public function testOptions()
    {
		$log = Log::to('file:/tmp/test.log;format="{$type} {$message}"');
		$this->assertType('Q\Log_Text', $log);
		$this->assertAttributeEquals('/tmp/test.log', 'file', $log);
		$this->assertAttributeEquals('{$type} {$message}', 'format', $log);
    }
    
    public function testInterface()
    {
        $this->assertType('Q\Log_Mock', Log::i());
        $this->assertFalse(Log::i()->exists());
        
        Log::i()->to('header');
        $this->assertType('Q\Log_Header', Log::i());
        $this->assertTrue(Log::i()->exists());
    }

    public function testAlternativeInterface()
    {
        $this->assertType('Q\Log_Mock', Log::mytest());
        $this->assertFalse(Log::mytest()->exists());
        
        Log::mytest()->to('output');
        $this->assertType('Q\Log_Text', Log::mytest());
        $this->assertTrue(Log::mytest()->exists());
    }
}
?>