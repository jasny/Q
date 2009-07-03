<?php

require_once __DIR__ . '/../init.inc';
require_once 'Q/Log/Text.php';
require_once 'Q/VariableStream.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Log_Text test case.
 */
class Test_Log_Text extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Log_Text
	 */
	private $Log_Text;

	
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		$GLOBALS['__log'] = '';
		
		parent::setUp();
		$this->Log_Text = new Q\Log_Text('global://__log');
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$GLOBALS['__log'] = null;
		$this->Log_Text = null;

		parent::tearDown();
	}
	
	/**
	 * Tests Log_Text->log()
	 */
	public function testLog()
	{
		$this->Log_Text->log('This is a test');
		$this->assertEquals("This is a test\n", $GLOBALS['__log']);

		$this->Log_Text->log('Yet another "test"', 'trick');
		$this->assertEquals("This is a test\ntrick | Yet another \"test\"\n", $GLOBALS['__log']);
	}

	/**
	 * Tests Log_Text->log() with a different glue char and quoting
	 */
	public function testLog_CVS()
	{
	    $this->Log_Text->format = ";";
	    $this->Log_Text->quote = true;
	    
		$this->Log_Text->log('This is a test', 'info');
		$this->assertEquals("\"info\";\"This is a test\"\n", $GLOBALS['__log']);

		$this->Log_Text->log('Yet another "test"', 'trick');
		$this->assertEquals('"info";"This is a test"' . "\n" . '"trick";"Yet another \\"test\\""' . "\n", $GLOBALS['__log']);
	}
		
	/**
	 * Tests Log_Text->log() with a format for the values
	 */
	public function testLog_FormatValue()
	{
	    $this->Log_Text->formatValue = "%s: %s";
	    
		$this->Log_Text->log("This is a test");
		$this->assertEquals("message: This is a test\n", $GLOBALS['__log']);
		
		$this->Log_Text->log("Yet another test", "trick");
		$this->assertEquals("message: This is a test\ntype: trick | message: Yet another test\n", $GLOBALS['__log']);
	}

	/**
	 * Tests Log_Text->log() with a format for the values and using quoting
	 */
	public function testLog_FormatValueQuote()
	{
	    $this->Log_Text->formatValue = '"%s: %s"';
	    $this->Log_Text->quote = true;

		$this->Log_Text->log("This is a test");
		$this->assertEquals('"message: This is a test"' . "\n", $GLOBALS['__log']);
	    
		$this->Log_Text->log('Yet another "test"', "trick");
		$this->assertEquals('"message: This is a test"' . "\n" . '"type: trick" | "message: Yet another \\"test\\""' . "\n", $GLOBALS['__log']);
	}
		
	/**
	 * Tests Log_Text->log() with different format
	 */
	public function testLog_Format()
	{
		$this->Log_Text->format = '{$type}: {$message}';
		$this->Log_Text->log('This is a test', 'info');
		$this->assertEquals("info: This is a test\n", $GLOBALS['__log']);

		$this->Log_Text->log('Yet another "test"', 'trick');
		$this->assertEquals("info: This is a test\ntrick: Yet another \"test\"\n", $GLOBALS['__log']);
	}

	/**
	 * Tests Log_Text->log() with custom event value
	 */
	public function testLog_EventValue()
	{
		$this->Log_Text->eventValues['user'] = 'just_me';
		
		$this->Log_Text->log('This is a test', 'info');
		$this->assertEquals("just_me | info | This is a test\n", $GLOBALS['__log']);
		
		$this->Log_Text->log('Yet another "test"', 'trick');
		$this->assertEquals("just_me | info | This is a test\njust_me | trick | Yet another \"test\"\n", $GLOBALS['__log']);
    }
	
	/**
	 * Tests Log_Text->log() with different format and custom event value
	 */
	public function testLog_Format_EventValue()
	{
		$this->Log_Text->format = '[{$user}] [{$type}] {$message}';
		$this->Log_Text->eventValues['user'] = 'just_me';
		
		$this->Log_Text->log('This is a test', 'info');
		$this->assertEquals("[just_me] [info] This is a test\n", $GLOBALS['__log']);

		$this->Log_Text->log('Yet another "test"', 'trick');
		$this->assertEquals("[just_me] [info] This is a test\n[just_me] [trick] Yet another \"test\"\n", $GLOBALS['__log']);
	}
	
	/**
	 * Tests Log_Text->log() with a format for the values, format with event value
	 */
	public function testLog_FormatAll()
	{
	    $this->Log_Text->formatValue = "%s: %s";
	    $this->Log_Text->format = '[{$user}] [{$type}] {$message}';
		$this->Log_Text->eventValues['user'] = 'just_me';
	    
		$this->Log_Text->log("This is a test", 'info');
		$this->assertEquals("[user: just_me] [type: info] message: This is a test\n", $GLOBALS['__log']);
		
		$this->Log_Text->log('Yet another "test"', "trick");
		$this->assertEquals("[user: just_me] [type: info] message: This is a test\n[user: just_me] [type: trick] message: Yet another \"test\"\n", $GLOBALS['__log']);
	}

	/**
	 * Tests Log_Text->log() with a format for the values, format with event value and quoting
	 */
	public function testLog_FormatAllQuote()
	{
	    $this->Log_Text->formatValue = "%s: %s";
	    $this->Log_Text->format = '[{$user}] [{$type}] "{$message}"';
		$this->Log_Text->eventValues['user'] = 'just_me';
	    $this->Log_Text->quote = true;
	    
		$this->Log_Text->log("This is a test", 'info');
		$this->assertEquals('[user: just_me] [type: info] "message: This is a test"' . "\n", $GLOBALS['__log']);
		
		$this->Log_Text->log('Yet another "test"', "trick");
		$this->assertEquals('[user: just_me] [type: info] "message: This is a test"' . "\n" . '[user: just_me] [type: trick] "message: Yet another \\"test\\""' . "\n", $GLOBALS['__log']);
	}	

	
	/**
	 * Tests Log_Text->log() with a filter excluding types
	 */
	public function testLog_FilterExclude()
	{
		$this->Log_Text->format = '[{$type}] {$message}';
		$this->Log_Text->setFilter('info', Q\Log::FILTER_EXCLUDE);
		$this->Log_Text->setFilter('!notice');
		
		$this->Log_Text->log('This is a test', 'info');
		$this->assertEquals('', $GLOBALS['__log']);

		$this->Log_Text->log('A notice', 'notice');
		$this->assertEquals('', $GLOBALS['__log']);
		
		$this->Log_Text->log('Yet another "test"', 'trick');
		$this->assertEquals("[trick] Yet another \"test\"\n", $GLOBALS['__log']);
	}
	
	/**
	 * Tests Log_Text->log() with a filter including types
	 */
	public function testLog_FilterInclude()
	{
		$this->Log_Text->format = '[{$type}] {$message}';
		$this->Log_Text->setFilter('info', Q\Log::FILTER_INCLUDE);
		$this->Log_Text->setFilter('notice');
		
		$this->Log_Text->log('This is a test', 'info');
		$this->assertEquals("[info] This is a test\n", $GLOBALS['__log']);

		$this->Log_Text->log('A notice', 'notice');
		$this->assertEquals("[info] This is a test\n[notice] A notice\n", $GLOBALS['__log']);
		
		$this->Log_Text->log('Yet another "test"', 'trick');
		$this->assertEquals("[info] This is a test\n[notice] A notice\n", $GLOBALS['__log']);
	}
	
	/**
	 * Tests Log_Text->log() using an alias type
	 */
	public function testLog_Alias()
	{
		$this->Log_Text->format = '[{$type}] {$message}';
		$this->Log_Text->alias['sql'] = 'info';
		
		$this->Log_Text->log('This is a test', 'sql');
		$this->assertEquals("[info] This is a test\n", $GLOBALS['__log']);

		$this->Log_Text->log('Yet another "test"', 'trick');
		$this->assertEquals("[info] This is a test\n[trick] Yet another \"test\"\n", $GLOBALS['__log']);
	}

	/**
	 * Tests Log_Text->log() using a numeric alias type
	 */
	public function testLog_AliasNr()
	{
		$this->Log_Text->format = '[{$type}] {$message}';
		
		$this->Log_Text->log('A notice', 5);
		$this->assertEquals("[notice] A notice\n", $GLOBALS['__log']);
	}

	/**
	 * Tests Log_Text->log() logging an array
	 */
	public function testLog_Array()
	{
		$this->Log_Text->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals("sql | SELECT * FROM Foo WHERE abc=\"A\" | 0.02 | (row1, row2)\n", $GLOBALS['__log']);
    }

	/**
	 * Tests Log_Text->log() logging an array with a different glue char and quoting
	 */
	public function testLog_Array_CVS()
	{
	    $this->Log_Text->format = ";";
	    $this->Log_Text->quote = true;
	    $this->Log_Text->arrayImplode = array('glue'=>'|', 'prefix'=>'[', 'suffix'=>']');
	    
		$this->Log_Text->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals('"sql";"SELECT * FROM Foo WHERE abc=\\"A\\"";"0.02";"[row1|row2]"' . "\n", $GLOBALS['__log']);
	}
	
	/**
	 * Tests Log_Text->log() logging an array with different format
	 */
	public function testLog_Array_Format()
	{
		$this->Log_Text->format = '{$query} (took {$time}s): {$result}';
		
		$this->Log_Text->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals("SELECT * FROM Foo WHERE abc=\"A\" (took 0.02s): (row1, row2)\n", $GLOBALS['__log']);
	}

	/**
	 * Tests Log_Text->log() logging an array with custom event value
	 */
	public function testLog_Array_EventValue()
	{
		$this->Log_Text->eventValues['user'] = 'just_me';
		
		$this->Log_Text->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals("just_me | sql | SELECT * FROM Foo WHERE abc=\"A\" | 0.02 | (row1, row2)\n", $GLOBALS['__log']);
    }	

	
	/**
	 * Tests Log_Text in Zend_Log compatibility mode
	 */
	public function testZendCompatible()
	{
		$this->Log_Text->zendCompatible = true;
		$this->Log_Text->format = '[{$type}] {$message}';
		
		$this->Log_Text->info('This is a test');
		$this->assertEquals("[info] This is a test\n", $GLOBALS['__log']);

		$this->Log_Text->trick('Yet another "test"');
		$this->assertEquals("[info] This is a test\n[trick] Yet another \"test\"\n", $GLOBALS['__log']);
	}
}

if (PHPUnit_MAIN_METHOD == 'Test_Log_Text::main') Test_Log_Text::main();
?>