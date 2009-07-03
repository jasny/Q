<?php

require_once __DIR__ . '/../init.inc';
require_once 'Q/Log/FirePHPTable.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Log_FirePHP test case.
 */
class Test_Log_FirePHPTable extends PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $user_agent;
    
	/**
	 * @var Log_FirePHP
	 */
	private $Log_FirePHP;

    /**
     * Reflection of Q\Log_FirePHP::$counter
     *
     * @var ReflectionProperty
     */
    protected $prop_unique_base;
    	
	/**
	 * Base value of FirePHP header.
	 * @var string
	 */
	protected $unique_base;
	
	
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    /**
     * Constructs a test case with the given name.
     *
     * @param  string $name
     * @param  array  $data
     * @param  string $dataName
     * @access public
     */
    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
		$this->prop_unique_base = new ReflectionProperty('Q\Log_FirePHPTable', 'unique_base');
		$this->prop_unique_base->setAccessible(true);
                
        parent::__construct($name, $data, $dataName);
    }
    
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.2) Gecko/2008092313 Ubuntu/8.04 (hardy) Firefox/3.0.2 FirePHP/0.1.2";

        $this->Log_FirePHP = new Q\Log_FirePHPTable("Test");
        $this->unique_base = $this->prop_unique_base->getValue($this->Log_FirePHP);
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Log_FirePHP = null;
		$this->unique_base = null;

		$_SERVER['HTTP_USER_AGENT'] = $this->user_agent;
		parent::tearDown();
	}

	
	/**
	 * Tests Log_FirePHP->log()
	 */
	public function testLog()
	{
		$this->Log_FirePHP->log("This is a test", 'info');
		$this->Log_FirePHP->log('Yet another "test"', "warn");
		
		$this->assertEquals("[\"Test\",", Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000000"));
		$this->assertEquals(json_encode(array('Type', 'Message')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000001"));
		$this->assertEquals(json_encode(array('info', 'This is a test')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
		$this->assertEquals(json_encode(array('warn', 'Yet another "test"')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000003"));
	}

	/**
	 * Tests Log_FirePHP->log() with custom event value
	 */
	public function testLog_EventValue()
	{
		$this->Log_FirePHP->eventValues['user'] = 'just_me';
		
		$this->Log_FirePHP->log('This is a test', 'info');
		$this->Log_FirePHP->log('Yet another "test"', 'warn');

		$this->assertEquals(json_encode(array('User', 'Type', 'Message')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000001"));
		$this->assertEquals(json_encode(array('just_me', 'info', 'This is a test')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
		$this->assertEquals(json_encode(array('just_me', 'warn', 'Yet another "test"')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000003"));
	}

	/**
	 * Tests Log_FirePHP->log() with different column order
	 */
	public function testLog_Columns()
	{
	    $this->Log_FirePHP->title = "My Table";
	    $this->Log_FirePHP->columns = array('message'=>"What's up", 'user'=>"User");
		$this->Log_FirePHP->eventValues['user'] = 'just_me';

	    $this->Log_FirePHP->log("This is a test", 'info');
		$this->Log_FirePHP->log('Yet another "test"', "warn");
		
		$this->assertEquals("[\"My Table\",", Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000000"));
		$this->assertEquals(json_encode(array("What's up", "User")), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000001"));
		$this->assertEquals(json_encode(array('This is a test', 'just_me')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
		$this->assertEquals(json_encode(array('Yet another "test"', 'just_me')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000003"));
	}
	
	/**
	 * Tests Log_FirePHP->log() with a filter excluding types
	 */
	public function testLog_FilterExclude()
	{
		$this->Log_FirePHP->setFilter('info', Q\Log::FILTER_EXCLUDE);
		$this->Log_FirePHP->setFilter('!notice');

		$this->Log_FirePHP->log("This is a test", 'info');
		$this->Log_FirePHP->log("A notice", "notice");
		$this->Log_FirePHP->log('Yet another "test"', 'warn');
		
		$this->assertEquals(json_encode(array('warn', 'Yet another "test"')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
		$this->assertNull(Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000003"));
	}
	
	/**
	 * Tests Log_FirePHP->log() with a filter including types
	 */
	public function testLog_FilterInclude()
	{
		$this->Log_FirePHP->setFilter('info', Q\Log::FILTER_INCLUDE);
		$this->Log_FirePHP->setFilter('notice');
		
		$this->Log_FirePHP->log('This is a test', 'info');
		$this->Log_FirePHP->log('A notice', 'notice');
		$this->Log_FirePHP->log('Yet another "test"', 'warn');

		$this->assertEquals(json_encode(array('info', 'This is a test')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
		$this->assertEquals(json_encode(array('notice', 'A notice')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000003"));
		$this->assertNull(Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000004"));
	}
	

	/**
	 * Tests Log_FirePHP->log() using an alias type
	 */
	public function testLog_Alias()
	{
	    $this->Log_FirePHP->alias['sql'] = 'info';
		
		$this->Log_FirePHP->log('This is a test', 'sql');
		$this->assertEquals(json_encode(array('info', 'This is a test')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
	}

	/**
	 * Tests Log_FirePHP->log() using a numeric alias type
	 */
	public function testLog_AliasNr()
	{
	    $this->Log_FirePHP->log('A notice', 5);
		$this->assertEquals(json_encode(array('notice', 'A notice')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
	}

	
	/**
	 * Tests Log_FirePHP->log() logging an array
	 */
	public function testLog_Array()
	{
	    $this->Log_FirePHP->columns = array('query'=>"Query", 'time'=>"Time", 'result'=>"Result");
	    
		$this->Log_FirePHP->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals(json_encode(array('Query', 'Time', 'Result')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000001"));
		$this->assertEquals(json_encode(array('SELECT * FROM Foo WHERE abc="A"', '0.02', array('row1','row2'))), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
	}

	/**
	 * Tests Log_FirePHP->log() logging an array with custom event value
	 */
	public function testLog_Array_EventValue()
	{
	    $this->Log_FirePHP->columns = array('user'=>'User', 'query'=>"Query", 'time'=>"Time", 'result'=>"Result");
	    $this->Log_FirePHP->eventValues['user'] = 'just_me';
	    
		$this->Log_FirePHP->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals(json_encode(array('User', 'Query', 'Time', 'Result')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000001"));
		$this->assertEquals(json_encode(array('just_me', 'SELECT * FROM Foo WHERE abc="A"', '0.02', array('row1','row2'))), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
    }	

	
	/**
	 * Tests Log_FirePHP in Zend_Log compatibility mode
	 */
	public function testZendCompatible()
	{
		$this->Log_FirePHP->zendCompatible = true;
		
		$this->Log_FirePHP->info('This is a test');
		$this->Log_FirePHP->warn('Yet another "test"');
		
		$this->assertEquals(json_encode(array('Type', 'Message')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000001"));
		$this->assertEquals(json_encode(array('info', 'This is a test')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000002"));
		$this->assertEquals(json_encode(array('warn', 'Yet another "test"')), Q\HTTP::header_getValue("X-FirePHP-Data-{$this->unique_base}00000003"));
	}
}

if (PHPUnit_MAIN_METHOD == 'Test_Log_FirePHP::main') Test_Log_FirePHP::main();
?>