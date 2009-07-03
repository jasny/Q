<?php

require_once __DIR__ . '/../init.inc';
require_once 'Q/Log/Mail.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Test/Log/q-mail-overwrite.php';

/**
 * Log_Mail test case.
 */
class Test_Log_Mail extends PHPUnit_Framework_TestCase
{
    /**
     * The expected mail with default properties.
     * @var string 
     */
    protected $expected_mail = array('to'=>'test@example.com', 'subject'=>'Log message', 'additional_headers'=>"From: system@example.com\r\n", 'additional_parameters'=>'');
    
	/**
	 * @var Log_Mail
	 */
	private $Log_Mail;

	
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
        
		$GLOBALS['_mail_'] = array();
		
		$this->Log_Mail = new Q\Log_Mail('test@example.com');
		$this->Log_Mail->subject = 'Log message';
		$this->Log_Mail->headers['From'] = 'system@example.com';
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Log_Mail = null;
		parent::tearDown();
	}
	
	
	/**
	 * Tests Log_Mail->log()
	 */
	public function testLog()
	{
		$this->Log_Mail->log("This is a test");
		$this->assertEquals(array('message'=>"This is a test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
		
		$this->Log_Mail->log("Yet another test", "trick");
		$this->assertEquals(array('message'=>"trick\nYet another test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}

	/**
	 * Tests Log_Mail->log() with a format for the values
	 */
	public function testLog_FormatValue()
	{
	    $this->Log_Mail->formatValue = "%s: %s";
	    
		$this->Log_Mail->log("This is a test");
		$this->assertEquals(array('message'=>"message: This is a test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
		
		$this->Log_Mail->log("Yet another test", "trick");
		$this->assertEquals(array('message'=>"type: trick\nmessage: Yet another test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}
	
	/**
	 * Tests Log_Mail->log() with a format
	 */
	public function testLog_Format()
	{
	    $this->Log_Mail->format = "Type: {\$type}\n\n{\$message}";
	    
		$this->Log_Mail->log("This is a test", 'info');
		$this->assertEquals(array('message'=>"Type: info\n\nThis is a test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
				
		$this->Log_Mail->log("Yet another test", "trick");
		$this->assertEquals(array('message'=>"Type: trick\n\nYet another test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}

	/**
	 * Tests Log_Mail->log() with custom event value
	 */
	public function testLog_EventValue()
	{
		$this->Log_Mail->eventValues['user'] = 'just_me';
		
		$this->Log_Mail->log('This is a test');
		$this->assertEquals(array('message'=>"just_me\nThis is a test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
				
		$this->Log_Mail->log('Yet another "test"', 'trick');
		$this->assertEquals(array('message'=>"just_me\ntrick\nYet another \"test\"") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
    }
	
	/**
	 * Tests Log_Mail->log() with different format and custom event value
	 */
	public function testLog_Format_EventValue()
	{
		$this->Log_Mail->format = "User: {\$user}\nType: {\$type}\n\n{\$message}";
		$this->Log_Mail->eventValues['user'] = 'just_me';
		
		$this->Log_Mail->log('This is a test', 'info');
		$this->assertEquals(array('message'=>"User: just_me\nType: info\n\nThis is a test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));

		$this->Log_Mail->log('Yet another "test"', 'trick');
		$this->assertEquals(array('message'=>"User: just_me\nType: trick\n\nYet another \"test\"") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}
		
	/**
	 * Tests Log_Mail->log() with a filter excluding types
	 */
	public function testLog_FilterExclude()
	{
		$this->Log_Mail->setFilter('info', Q\Log::FILTER_EXCLUDE);
		$this->Log_Mail->setFilter('!notice');

		$this->Log_Mail->log("This is a test", 'info');
		$this->assertNull(array_pop($GLOBALS['_mail_']));
		
		$this->Log_Mail->log("A notice", "notice");
		$this->assertNull(array_pop($GLOBALS['_mail_']));
		
		$this->Log_Mail->log('Yet another "test"', 'trick');
		$this->assertEquals(array('message'=>"trick\nYet another \"test\"") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}
	
	/**
	 * Tests Log_Mail->log() with a filter including types
	 */
	public function testLog_FilterInclude()
	{
		$this->Log_Mail->setFilter('info', Q\Log::FILTER_INCLUDE);
		$this->Log_Mail->setFilter('notice');
		
		$this->Log_Mail->log('This is a test', 'info');
		$this->assertEquals(array('message'=>"info\nThis is a test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
		
		$this->Log_Mail->log('A notice', 'notice');
		$this->assertEquals(array('message'=>"notice\nA notice") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
						
		$this->Log_Mail->log('Yet another test', 'trick');
		$this->assertNull(array_pop($GLOBALS['_mail_']));
	}
	
	
	/**
	 * Tests Log_Mail->log() using an alias type
	 */
	public function testLog_Alias()
	{
		$this->Log_Mail->alias['sql'] = 'info';
		
		$this->Log_Mail->log('This is a test', 'sql');
		$this->assertEquals(array('message'=>"info\nThis is a test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
		
		$this->Log_Mail->log('Yet another test', 'trick');
		$this->assertEquals(array('message'=>"trick\nYet another test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}

	/**
	 * Tests Log_Mail->log() using a numeric alias type
	 */
	public function testLog_AliasNr()
	{
		$this->Log_Mail->log('A notice', 5);
		$this->assertEquals(array('message'=>"notice\nA notice") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}

	
	/**
	 * Tests Log_Mail->log() logging an array
	 */
	public function testLog_Array()
	{
		$this->Log_Mail->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals(array('message'=>"sql\nSELECT * FROM Foo WHERE abc=\"A\"\n0.02\n(row1, row2)") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
    }

	/**
	 * Tests Log_Mail->log() logging an array with a different glue char and quoting
	 */
	public function testLog_Array_FormatValue()
	{
	    $this->Log_Mail->formatValue = "%s: %s";
	    $this->Log_Mail->arrayImplode = array('glue'=>'|', 'prefix'=>'[', 'suffix'=>']');
	    
		$this->Log_Mail->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals(array('message'=>"type: sql\nquery: SELECT * FROM Foo WHERE abc=\"A\"\ntime: 0.02\nresult: [row1|row2]") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}
	
	/**
	 * Tests Log_Mail->log() logging an array with different format
	 */
	public function testLog_Array_Format()
	{
		$this->Log_Mail->format = '{$query} (took {$time}s): {$result}';
		
		$this->Log_Mail->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals(array('message'=>"SELECT * FROM Foo WHERE abc=\"A\" (took 0.02s): (row1, row2)") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}

	/**
	 * Tests Log_Mail->log() logging an array with custom event value
	 */
	public function testLog_Array_EventValue()
	{
		$this->Log_Mail->eventValues['user'] = 'just_me';
		
		$this->Log_Mail->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals(array('message'=>"just_me\nsql\nSELECT * FROM Foo WHERE abc=\"A\"\n0.02\n(row1, row2)") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}

	
	/**
	 * Tests Log_Mail->log() with different mail settings
	 */
	public function testLog_MailSettings()
	{
		$this->Log_Mail->to = "user@example.com";
		$this->Log_Mail->cc = "copy@example.com";
		$this->Log_Mail->from = "php-q@example.com";
		$this->Log_Mail->subject = "A subject";
		
		$this->Log_Mail->log("This is a test");
		$this->assertEquals(array('to'=>'user@example.com', 'subject'=>'A subject', 'message'=>"This is a test", 'additional_headers'=>"From: php-q@example.com\r\nCc: copy@example.com\r\n", 'additional_parameters'=>''), array_pop($GLOBALS['_mail_']));
	}

	/**
	 * Tests Log_Mail->log() with a header and footer for the message
	 */
	public function testLog_MsgHeaderFooter()
	{
		$this->Log_Mail->msgHeader = "This is an auto-generated message:\n\n";
		$this->Log_Mail->msgFooter = "\n-----\n";
		
		$this->Log_Mail->log("This is a test");
		$this->assertEquals(array('message'=>"This is an auto-generated message:\n\nThis is a test\n-----\n") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}
	
	/**
	 * Tests Log_Mail->log() combining messages
	 */
	public function testLog_Combine()
	{
		$this->Log_Mail->combine = true;
		$this->Log_Mail->messageDelimiter = "\n--\n\n";
		
		$this->Log_Mail->log("This is a test");
		$this->Log_Mail->log("A notice", 'notice');
		$this->Log_Mail->log('Yet another test', 'trick');
		$this->assertNull(array_pop($GLOBALS['_mail_']), "not send");
	
		$this->Log_Mail->flush();
		$this->assertEquals(array('message'=>"This is a test\n--\n\nnotice\nA notice\n--\n\ntrick\nYet another test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
		$this->assertNull(array_pop($GLOBALS['_mail_']), "2nd message");
	}
	
	
	/**
	 * Tests Log_Mail in Zend_Log compatibility mode
	 */
	public function testZendCompatible()
	{
		$this->Log_Mail->zendCompatible = true;
		
		$this->Log_Mail->info('This is a test');
		$this->assertEquals(array('message'=>"info\nThis is a test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
		
		$this->Log_Mail->trick('Yet another test');
		$this->assertEquals(array('message'=>"trick\nYet another test") + $this->expected_mail, array_pop($GLOBALS['_mail_']));
	}	
}

if (PHPUnit_MAIN_METHOD == 'Test_Log_Mail::main') Test_Log_Mail::main();
?>