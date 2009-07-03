<?php

require_once __DIR__ . '/../init.inc';
require_once 'Q/Log/Header.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Log_Header test case.
 */
class Test_Log_Header extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Log_Header
	 */
	private $Log_Header;

	
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
		parent::setUp();
		$this->Log_Header = new Q\Log_Header();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Log_Header = null;
		parent::tearDown();
	}
	
	/**
	 * Helper function: Get header counter
	 * 
	 * @return int
	 */
	protected function getCounter()
	{
        $refl = new ReflectionClass('Q\Log_Header');
        $props = $refl->getStaticProperties();
        return $props['counter'];
	}
	
	/**
	 * Tests Log_Header->log()
	 */
	public function testLog()
	{
		$this->Log_Header->log("This is a test");
		$this->assertEquals("This is a test", Q\HTTP::header_getValue('X-Log-' . $this->getCounter()));
		
		$this->Log_Header->log("Yet another test", "trick");
		$this->assertEquals("Yet another test", Q\HTTP::header_getValue('X-Trick-' . $this->getCounter()));
	}

	/**
	 * Tests Log_Header->log() with a format
	 */
	public function testLog_Format()
	{
	    $this->Log_Header->format = '[{$type}] {$message}';
	    
		$this->Log_Header->log("This is a test", 'info');
		$this->assertEquals("[info] This is a test", Q\HTTP::header_getValue('X-Info-' . $this->getCounter()));
		
		$this->Log_Header->log("Yet another test", "trick");
		$this->assertEquals("[trick] Yet another test", Q\HTTP::header_getValue('X-Trick-' . $this->getCounter()));
	}

	/**
	 * Tests Log_Header->log() with custom event value
	 */
	public function testLog_EventValue()
	{
		$this->Log_Header->eventValues['user'] = 'just_me';
		
		$this->Log_Header->log('This is a test');
		$this->assertEquals("just_me | This is a test", Q\HTTP::header_getValue('X-Log-' . $this->getCounter()));
		
		$this->Log_Header->log('Yet another "test"', 'trick');
		$this->assertEquals("just_me | Yet another \"test\"", Q\HTTP::header_getValue('X-Trick-' . $this->getCounter()));
    }
	
	/**
	 * Tests Log_Header->log() with different format and custom event value
	 */
	public function testLog_Format_EventValue()
	{
		$this->Log_Header->format = '[{$user}] [{$type}] {$message}';
		$this->Log_Header->eventValues['user'] = 'just_me';
		
		$this->Log_Header->log('This is a test', 'info');
		$this->assertEquals("[just_me] [info] This is a test", Q\HTTP::header_getValue('X-Info-' . $this->getCounter()));

		$this->Log_Header->log('Yet another "test"', 'trick');
		$this->assertEquals("[just_me] [trick] Yet another \"test\"", Q\HTTP::header_getValue('X-Trick-' . $this->getCounter()));
	}
		
	/**
	 * Tests Log_Header->log() with a filter excluding types
	 */
	public function testLog_FilterExclude()
	{
		$this->Log_Header->setFilter('info', Q\Log::FILTER_EXCLUDE);
		$this->Log_Header->setFilter('!notice');

		$this->Log_Header->log("This is a test", 'info');
		$this->assertNull(Q\HTTP::header_getValue('X-Info-' . $this->getCounter()));
		
		$this->Log_Header->log("A notice", "notice");
		$this->assertNull(Q\HTTP::header_getValue('X-Notice-' . $this->getCounter()));
		
		$this->Log_Header->log('Yet another test', 'trick');
		$this->assertEquals("Yet another test", Q\HTTP::header_getValue('X-Trick-' . $this->getCounter()));
	}
	
	/**
	 * Tests Log_Header->log() with a filter including types
	 */
	public function testLog_FilterInclude()
	{
		$this->Log_Header->setFilter('info', Q\Log::FILTER_INCLUDE);
		$this->Log_Header->setFilter('notice');
		
		$this->Log_Header->log('This is a test', 'info');
		$this->assertEquals("This is a test", Q\HTTP::header_getValue('X-Info-' . $this->getCounter()));
		
		$this->Log_Header->log('A notice', 'notice');
		$this->assertEquals("A notice", Q\HTTP::header_getValue('X-Notice-' . $this->getCounter()));
				
		$this->Log_Header->log('Yet another test', 'trick');
		$this->assertNull(Q\HTTP::header_getValue('X-Trick-' . $this->getCounter()));
	}
	
	
	/**
	 * Tests Log_Header->log() using an alias type
	 */
	public function testLog_Alias()
	{
		$this->Log_Header->alias['sql'] = 'info';
		
		$this->Log_Header->log('This is a test', 'sql');
		$this->assertEquals("This is a test", Q\HTTP::header_getValue('X-Info-' . $this->getCounter()));

		$this->Log_Header->log('Yet another test', 'trick');
		$this->assertEquals("Yet another test", Q\HTTP::header_getValue('X-Trick-' . $this->getCounter()));
	}

	/**
	 * Tests Log_Header->log() using a numeric alias type
	 */
	public function testLog_AliasNr()
	{
		$this->Log_Header->log('A notice', 5);
		$this->assertEquals("A notice", Q\HTTP::header_getValue('X-Notice-' . $this->getCounter()));
	}

	
	/**
	 * Tests Log_Header->log() logging an array
	 */
	public function testLog_Array()
	{
		$this->Log_Header->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals("SELECT * FROM Foo WHERE abc=\"A\" | 0.02 | (row1, row2)", Q\HTTP::header_getValue('X-Sql-' . $this->getCounter()));
    }

	/**
	 * Tests Log_Header->log() logging an array with a different glue char and quoting
	 */
	public function testLog_Array_CVS()
	{
	    $this->Log_Header->format = ";";
	    $this->Log_Header->quote = true;
	    $this->Log_Header->arrayImplode = array('glue'=>'|', 'prefix'=>'[', 'suffix'=>']');
	    
		$this->Log_Header->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals('"SELECT * FROM Foo WHERE abc=\\"A\\"";"0.02";"[row1|row2]"', Q\HTTP::header_getValue('X-Sql-' . $this->getCounter()));
	}
	
	/**
	 * Tests Log_Header->log() logging an array with different format
	 */
	public function testLog_Array_Format()
	{
		$this->Log_Header->format = '{$query} (took {$time}s): {$result}';
		
		$this->Log_Header->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals("SELECT * FROM Foo WHERE abc=\"A\" (took 0.02s): (row1, row2)", Q\HTTP::header_getValue('X-Sql-' . $this->getCounter()));
	}

	/**
	 * Tests Log_Header->log() logging an array with custom event value
	 */
	public function testLog_Array_EventValue()
	{
		$this->Log_Header->eventValues['user'] = 'just_me';
		
		$this->Log_Header->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals("just_me | SELECT * FROM Foo WHERE abc=\"A\" | 0.02 | (row1, row2)", Q\HTTP::header_getValue('X-Sql-' . $this->getCounter()));
    }	

	
	/**
	 * Tests Log_Header in Zend_Log compatibility mode
	 */
	public function testZendCompatible()
	{
		$this->Log_Header->zendCompatible = true;
		
		$this->Log_Header->info('This is a test');
		$this->assertEquals("This is a test", Q\HTTP::header_getValue('X-Info-' . $this->getCounter()));
		
		$this->Log_Header->trick('Yet another test');
		$this->assertEquals("Yet another test", Q\HTTP::header_getValue('X-Trick-' . $this->getCounter()));
	}	
}

if (PHPUnit_MAIN_METHOD == 'Test_Log_Header::main') Test_Log_Header::main();
?>