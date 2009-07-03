<?php

require_once __DIR__ . '/../init.inc';
require_once 'Q/Log/FirePHP.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Log_FirePHP test case.
 */
class Test_Log_FirePHP extends PHPUnit_Framework_TestCase
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
    protected $prop_counter;
    
	
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

	
    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name=null, array $data=array(), $dataName='')
    {
        $this->prop_counter = new ReflectionProperty('Q\Log_FirePHP', 'counter');
        $this->prop_counter->setAccessible(true);
        
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
		
        $this->Log_FirePHP = new Q\Log_FirePHP();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Log_FirePHP = null;
		$_SERVER['HTTP_USER_AGENT'] = $this->user_agent;
		parent::tearDown();
	}
    
	/**
     * Get the current counter of the 
     *
     */
    protected function getCounter()
    {
        return $this->prop_counter->getValue(null);
    }
	
	
	/**
	 * Tests Log_FirePHP->log()
	 */
	public function testLog()
	{
		$this->Log_FirePHP->log("This is a test");
		$this->assertEquals('["LOG",' . json_encode('This is a test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
		
		$this->Log_FirePHP->log('Yet another "test"', "warn");
		$this->assertEquals('["WARN",' . json_encode('Yet another "test"') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}

	/**
	 * Tests Log_FirePHP->log() with a format
	 */
	public function testLog_Format()
	{
	    $this->Log_FirePHP->format = '[{$type}] {$message}';
	    
		$this->Log_FirePHP->log("This is a test", 'info');
		$this->assertEquals('["INFO",' . json_encode('[info] This is a test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
		
		$this->Log_FirePHP->log('Yet another "test"', "warn");
		$this->assertEquals('["WARN",' . json_encode('[warn] Yet another "test"') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}

	/**
	 * Tests Log_FirePHP->log() with custom event value
	 */
	public function testLog_EventValue()
	{
		$this->Log_FirePHP->eventValues['user'] = 'just_me';
		
		$this->Log_FirePHP->log('This is a test');
		$this->assertEquals('["LOG",' . json_encode('just_me | This is a test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
				
		$this->Log_FirePHP->log('Yet another "test"', 'warn');
		$this->assertEquals('["WARN",' . json_encode('just_me | Yet another "test"') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }
	
	/**
	 * Tests Log_FirePHP->log() with different format and custom event value
	 */
	public function testLog_Format_EventValue()
	{
		$this->Log_FirePHP->format = '[{$user}] [{$type}] {$message}';
		$this->Log_FirePHP->eventValues['user'] = 'just_me';
		
		$this->Log_FirePHP->log('This is a test', 'info');
		$this->assertEquals('["INFO",' . json_encode('[just_me] [info] This is a test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
		
		$this->Log_FirePHP->log('Yet another "test"', 'warn');
		$this->assertEquals('["WARN",' . json_encode('[just_me] [warn] Yet another "test"') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}
		
	/**
	 * Tests Log_FirePHP->log() with a filter excluding types
	 */
	public function testLog_FilterExclude()
	{
		$this->Log_FirePHP->setFilter('info', Q\Log::FILTER_EXCLUDE);
		$this->Log_FirePHP->setFilter('!notice');

		$counter = $this->getCounter();
		
		$this->Log_FirePHP->log("This is a test", 'info');
		$this->assertEquals($counter, $this->getCounter(), "Counter position (info)");
		
		$this->Log_FirePHP->log("A notice", "notice");
		$this->assertEquals($counter, $this->getCounter(), "Counter position (notice)");
				
		$this->Log_FirePHP->log('Yet another "test"', 'warn');
		$this->assertEquals('["WARN",' . json_encode('Yet another "test"') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}
	
	/**
	 * Tests Log_FirePHP->log() with a filter including types
	 */
	public function testLog_FilterInclude()
	{
		$this->Log_FirePHP->setFilter('info', Q\Log::FILTER_INCLUDE);
		$this->Log_FirePHP->setFilter('notice');
		
		$this->Log_FirePHP->log('This is a test', 'info');
		$this->assertEquals('["INFO",' . json_encode('This is a test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
				
		$this->Log_FirePHP->log('A notice', 'notice');
		$this->assertEquals('["INFO",' . json_encode('A notice') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
				
		$counter = $this->getCounter();
		$this->Log_FirePHP->log('Yet another "test"', 'warn');
		$this->assertEquals($counter, $this->getCounter(), "Counter position (warn)");
	}
	
	/**
	 * Tests Log_FirePHP->log() using an alias type
	 */
	public function testLog_CustomType()
	{
	    $this->Log_FirePHP->format = '[{$type}] {$message}';
	    $this->Log_FirePHP->types['sql'] = Q\Log_FirePHP::INFO;
		
		$this->Log_FirePHP->log('This is a test', 'sql');
		$this->assertEquals('["INFO",' . json_encode('[sql] This is a test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)), "Type 'sql' specified as 'INFO'.");

		$this->Log_FirePHP->log('Yet another "test"', 'trick');
		$this->assertEquals('["LOG",' . json_encode('[trick] Yet another "test"') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)), "Type 'trick' not specified");
	}
	
	/**
	 * Tests Log_FirePHP->log() using an alias type
	 */
	public function testLog_Alias()
	{
	    $this->Log_FirePHP->format = '[{$type}] {$message}';
	    $this->Log_FirePHP->alias['sql'] = 'info';
		
		$this->Log_FirePHP->log('This is a test', 'sql');
		$this->assertEquals('["INFO",' . json_encode('[info] This is a test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}

	/**
	 * Tests Log_FirePHP->log() using a numeric alias type
	 */
	public function testLog_AliasNr()
	{
	    $this->Log_FirePHP->format = '[{$type}] {$message}';
	    
	    $this->Log_FirePHP->log('A notice', 5);
		$this->assertEquals('["INFO",' . json_encode('[notice] A notice') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}

	
	/**
	 * Tests Log_FirePHP->log() logging an array
	 */
	public function testLog_Array()
	{
		$this->Log_FirePHP->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals('["LOG",' . json_encode("SELECT * FROM Foo WHERE abc=\"A\" | 0.02 | (row1, row2)") . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }

	/**
	 * Tests Log_FirePHP->log() logging an array with a different glue char and quoting
	 */
	public function testLog_Array_CVS()
	{
	    $this->Log_FirePHP->format = ";";
	    $this->Log_FirePHP->quote = true;
	    $this->Log_FirePHP->arrayImplode = array('glue'=>'|', 'prefix'=>'[', 'suffix'=>']');
	    
		$this->Log_FirePHP->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals('["LOG",' . json_encode('"SELECT * FROM Foo WHERE abc=\\"A\\"";"0.02";"[row1|row2]"') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}
	
	/**
	 * Tests Log_FirePHP->log() logging an array with different format
	 */
	public function testLog_Array_Format()
	{
		$this->Log_FirePHP->format = '{$query} (took {$time}s): {$result}';
		
		$this->Log_FirePHP->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals('["LOG",' . json_encode('SELECT * FROM Foo WHERE abc="A" (took 0.02s): (row1, row2)') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}

	/**
	 * Tests Log_FirePHP->log() logging an array with custom event value
	 */
	public function testLog_Array_EventValue()
	{
		$this->Log_FirePHP->eventValues['user'] = 'just_me';
		
		$this->Log_FirePHP->log(array('query'=>'SELECT * FROM Foo WHERE abc="A"', 'time'=>'0.02', 'result'=>array('row1','row2')), 'sql');
		$this->assertEquals('["LOG",' . json_encode('just_me | SELECT * FROM Foo WHERE abc="A" | 0.02 | (row1, row2)') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
    }	

	
	/**
	 * Tests Log_FirePHP in Zend_Log compatibility mode
	 */
	public function testZendCompatible()
	{
		$this->Log_FirePHP->zendCompatible = true;
		
		$this->Log_FirePHP->info('This is a test');
		$this->assertEquals('["INFO",' . json_encode('This is a test') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
				
		$this->Log_FirePHP->warn('Yet another "test"');
		$this->assertEquals('["WARN",' . json_encode('Yet another "test"') . "],", Q\HTTP::header_getValue('X-FirePHP-Data-3' . str_pad($this->getCounter(), 11, '0', STR_PAD_LEFT)));
	}	
}

if (PHPUnit_MAIN_METHOD == 'Test_Log_FirePHP::main') Test_Log_FirePHP::main();
?>