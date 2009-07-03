<?
require_once __DIR__ . '/init.inc';

require_once 'Q/misc.php';

interface tst_misc_Q {}
class tst_misc_A {
    function __toString()
    {
        return "Test";
    }
}
class tst_misc_AQ extends tst_misc_A implements tst_misc_Q {}

/**
 * Test case for misc functions of Q
 */
class Test_Misc extends PHPUnit_Framework_TestCase
{
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
	}
	    
    /**
     * Test Q\class_is_a()
     */
	function test_class_is_a()
	{
	    $o = new tst_misc_AQ();
	    if (!($o instanceof tst_misc_A)) $this->markTestSkipped("Precondition object tst_misc_AQ instanceof tst_misc_A has failed");
	    if (!($o instanceof tst_misc_Q)) $this->markTestSkipped("Precondition object tst_misc_AQ instanceof tst_misc_Q has failed");
	    
	    $this->assertTrue(Q\class_is_a('tst_misc_AQ', 'tst_misc_A'), 'tst_misc_AQ is a tst_misc_A');
	    $this->assertTrue(Q\class_is_a('tst_misc_AQ', 'tst_misc_Q'), 'tst_misc_AQ is a tst_misc_Q');
	    $this->assertFalse(Q\class_is_a('tst_misc_AQ', 'Test_Misc'), 'tst::misc_AQ is a Test_Misc');
	}
	
	/**
	 * Test Q\unquote()
	 */
	function test_unquote()
	{
		$this->assertSame('test', Q\unquote('"test"'));
		$this->assertSame('test', Q\unquote('\'test\''));
		$this->assertSame('"test', Q\unquote('"test'));
		$this->assertSame('es', Q\unquote('test', 't'));
		$this->assertSame('test', Q\unquote('$test$', 't$'));
	}

	/**
	 * Test Q\parse_key()
	 */
	function test_parse_key()
	{
	    $array = array();
	    
	    Q\parse_key('test1', 10, $array);
	    $this->assertArrayHasKey('test1', $array);
	    $this->assertSame(10, $array['test1']);
	    
	    Q\parse_key('test2[10]', 'atext', $array);
	    $this->assertArrayHasKey('test2', $array);
	    $this->assertArrayHasKey(10, $array['test2']);
	    $this->assertSame('atext', $array['test2'][10]);

	    Q\parse_key('test2[]', 27, $array);
	    $this->assertArrayHasKey('test2', $array);
	    $this->assertArrayHasKey(11, $array['test2']);
	    $this->assertSame(27, $array['test2'][11]);
	    
	    Q\parse_key('test3["red"][12]', 'qqq', $array);
	    $this->assertArrayHasKey('test3', $array);
	    $this->assertArrayHasKey('red', $array['test3']);
	    $this->assertArrayHasKey(12, $array['test3']['red']);
	    $this->assertSame('qqq', $array['test3']['red'][12]);
	    
	    unset($GLOBALS['__test_parse_key__']);
	    Q\parse_key('__test_parse_key__', 10);
	    $this->assertArrayHasKey('__test_parse_key__', $GLOBALS);
	    $this->assertSame(10, $GLOBALS['__test_parse_key__']);
	}
	
	/**
	 * Test Q\array_get_column()
	 */
	function test_array_get_column()
	{
		$this->assertSame(array(9, 2, 6), Q\array_get_column(array(array(0, 9, 8), array(1, 2, 3, 4), array(6, 6, 6)), 1), "Ordered arrays");
		$this->assertSame(array(9, 2, 6), Q\array_get_column(array(array('a'=>0, 'b'=>9, 'c'=>8), array('x'=>1, 'b'=>2, 'z'=>3, 'q'=>4), array('r'=>6, 'e'=>6, 'b'=>6)), 'b'), "Associated arrays");
		$this->assertSame(array('abz'=>9, 'xyz'=>2, 'q'=>6), Q\array_get_column(array('abz'=>array(0, 9, 8), 'xyz'=>array(1, 2, 3, 4), 'q'=>array(6, 6, 6)), 1), "Associated with ordered arrays");
		$this->assertSame(array('abz'=>9, null, 6), Q\array_get_column(array('abz'=>array('a'=>0, 'b'=>9, 'c'=>8), array('x'=>1, 'z'=>3, 'q'=>4), array('r'=>6, 'e'=>6, 'b'=>6)), 'b'), "Mixed arrays");
	}

	/**
	 * Test Q\array_filter_keys()
	 */
	function test_array_filter_keys()
	{
		$this->assertSame(array('a'=>'test', 'x'=>'adam'), Q\array_filter_keys(array('a'=>'test', 'c'=>'su', 'x'=>'adam', '$_dd'=>'do'), array('a', 'b', 'x')));
	}

	/**
	 * Test Q\split_set()
	 */
	function test_split_set()
	{
		$this->assertSame(array('test', 'adam'), Q\split_set('test;adam'), "Simple");
		$this->assertSame(array('test', 'adam'), Q\split_set('test;"adam"'), "Quoted");
		$this->assertSame(array('test', '"adam"'), Q\split_set('test;"adam"', ';', false), "Don't unquote");
		$this->assertSame(array('test', 'adam', 'qqq', 'def'), Q\split_set('test;adam~"qqq"^def', ';~&^'), "Other seperators");
	}
	
	/**
	 * Test Q\split_set_assoc()
	 */
	function test_split_set_assoc()
	{
		$this->assertSame(array('a'=>'test', 'x'=>'adam'), Q\split_set_assoc('a=test;x=adam'), "Simple");
		$this->assertSame(array('a'=>'test', 'x'=>'adam;eva'), Q\split_set_assoc('a=test;x="adam;eva"'), "Quoted");
		$this->assertSame(array('a'=>'test', 'x'=>'"adam"'), Q\split_set_assoc('a=test;x="adam"', ';', false), "Don't unquote");
		$this->assertSame(array('a'=>'test', 'x'=>'adam', 'dd'=>'qqq', 'abc'=>'def'), Q\split_set_assoc('a=test;x=adam~dd="qqq"^abc=def', ';~&^'), "Other seperators");
	}

	/**
	 * Test Q\split_set_assoc() with both ordered and associated parts
	 */
	function test_split_set_assoc_Mixed()
	{
		$this->assertSame(array('a'=>'test', 'abc', 'x'=>'adam'), Q\split_set_assoc('a=test;abc;x=adam'), "Simple");
		$this->assertSame(array('a'=>'test', 'abc;def', 'x'=>'adam;eva', 'qqq', 'def'), Q\split_set_assoc('a=test;"abc;def";x="adam;eva";"qqq";def'), "Quoted");
		$this->assertSame(array('a'=>'test', 'abc', 'x'=>'"adam"', '"qqq"', 'def'), Q\split_set_assoc('a=test;abc;x="adam";"qqq";def', ';', false), "Don't unquote");
		$this->assertSame(array('a'=>'test', 'abc', 'x'=>'adam', 'qqq', 'def'), Q\split_set_assoc('a=test;abc;x=adam~"qqq"^def', ';~&^'), "Other seperators");
    }
	
    /**
	 * Test Q\implode_recursive()
	 */
    function test_implode_recursive()
    {
        $this->assertSame("a, b, (1, 2, 3), c, (10, 11, (I, II, III), 12)", Q\implode_recursive(', ', array('a', 'b', array(1, 2, 3), 'c', array(10, 11, array('I', 'II', 'III'), 12))));
    }

    /**
	 * Test Q\implode_recursive()
	 */
    function test_implode_assoc()
    {
        $this->assertSame('a=test, b=another test, k.I=x, k.II=y, k.III="z=10", k.IV.abc=22, k.IV.def=33, c=go test', Q\implode_assoc(', ', array('a'=>"test", 'b'=>"another test", 'k'=>array('I'=>"x", 'II'=>"y", 'III'=>"z=10", 'IV'=>array('abc'=>22, 'def'=>33)), 'c'=>"go test")));
        $this->assertSame('a=test, b=another test, k=(I=x, II=y, III="z=10", IV=(abc=22, def=33)), c=go test', Q\implode_assoc(', ', array('a'=>"test", 'b'=>"another test", 'k'=>array('I'=>"x", 'II'=>"y", 'III'=>"z=10", 'IV'=>array('abc'=>22, 'def'=>33)), 'c'=>"go test"), '%s=%s', '%s=(', ')'));
        $this->assertSame('a:"test" + b:"another test" + k:[I:"x" + II:"y" + III:"z=10" + IV:[abc:"22" + def:"33"]] + c:"go test"', Q\implode_assoc(' + ', array('a'=>"test", 'b'=>"another test", 'k'=>array('I'=>"x", 'II'=>"y", 'III'=>"z=10", 'IV'=>array('abc'=>22, 'def'=>33)), 'c'=>"go test"), '%s:%s', '%s:[', ']', true));
    }
    
    
	/**
	 * Test Q\split_binset()
	 */
	function test_split_binset()
	{
		$this->assertSame(array(2, 8), Q\split_binset(10));
	}
	
	/**
	 * Test Q\extract_dsn()
	 */
	function test_extract_dsn()
	{
		$this->assertSame(array('driver'=>'mysql', 'host'=>'localhost', 'port'=>'3306', 'username'=>'myuser', 'password'=>'mypass'), Q\extract_dsn('mysql:host=localhost;port=3306;username=myuser;password=mypass'), "Simple");
		$this->assertSame(array('driver'=>'mysql', 'host'=>'localhost', 'port'=>'3306', 'username'=>'myuser', 'password'=>'ad;er=dee'), Q\extract_dsn('mysql:host=localhost;port=3306;username=myuser;password="ad;er=dee"'), "Quoted");
	}
	
	/**
	 * Test Q\extract_dsn()
	 */
	function test_array_map_recursive()
	{
		$this->assertSame(array('"a"', 'b', array('x', '"y"', 'z'), '"c'), Q\array_map_recursive('stripslashes', array('\"a\"', 'b', array('x', '\"y\"', 'z'), '\"c')));
	}
	
	/**
	 * Test Q\refsort()
	 */
    function test_refsort()
    {
		$x = Q\refsort(array('p2'=>array('ch1', 'ch3', 'ch4'), 'ch1'=>null, 'ch3'=>null, 'p1'=>array('ch1', 'p2')));
		$this->assertSame(array('ch3'=>null, 'ch1'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2')), $x);
		
		$x = Q\refsort(array('ch3'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'ch1'=>null, 'p1'=>array('ch1', 'p2')));
		$this->assertSame(array('ch1'=>null, 'ch3'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2')), $x);
		
		$x = Q\refsort(array('ch1'=>null, 'ch3'=>null, 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2')), SORT_DESC);
		$this->assertSame(array('p1'=>array('ch1', 'p2'), 'p2'=>array('ch1', 'ch3', 'ch4'), 'ch1'=>null, 'ch3'=>null), $x);
		
		// Should give warning and not hang
		$x = @Q\refsort(array('ch1'=>null, 'ch3'=>array('p2'), 'p2'=>array('ch1', 'ch3', 'ch4'), 'p1'=>array('ch1', 'p2')));
		if (function_exists('error_get_last')) {
		    $err = error_get_last();
		    $this->assertEquals("Unable to sort array because of cross-reference.", $err['message']);
		}
	}
	
	/**
	 * Test Q\array_merge_deep()
	 */
	function test_array_merge_deep()
	{
	    $this->assertEquals(array('a'=>10, 'b'=>array('x'=>25, 'y'=>40, 'z'=>70, 'q'=>array('k', 'l', 'm')), 'c'=>100), Q\array_merge_recursive(array('a'=>10, 'b'=>array('q'=>array('k', 'l'))), array('b'=>array('x'=>25, 'y'=>40, 'q'=>array('m'))), array('c'=>100, 'b'=>array('z'=>70))));
	    $this->assertEquals(array(0=>10, 1=>array('q'=>array('k', 'l')), 2=>array('x'=>25, 'y'=>40, 'q'=>array('m')), 'c'=>100, 3=>array('z'=>70)), Q\array_merge_recursive(array(0=>10, 1=>array('q'=>array('k', 'l'))), array(1=>array('x'=>25, 'y'=>40, 'q'=>array('m'))), array('c'=>100, 0=>array('z'=>70))));
	}
	
	/**
	 * Test Q\array_chunk_assoc()
	 */
	function test_array_chunk_assoc()
	{
	    $this->assertEquals(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), Q\array_chunk_assoc(array('test.a'=>10, 'hallo'=>"abc", "Q rules", 'test.b'=>"test", 'test.c'=>'xyz'), 'test'));
	    $this->assertEquals(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), Q\array_chunk_assoc(array('test::a'=>10, 'hallo'=>"abc", "Q rules", 'test::b'=>"test", 'test::c'=>'xyz'), 'test', '::'), "using :: seperator");
	    
	    $this->assertEquals(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), Q\array_chunk_assoc(array('test'=>array('a'=>10, 'b'=>"test", 'c'=>'xyz'), 'hallo'=>"abc", "Q rules"), 'test'));
	    $this->assertEquals(array('a'=>10, 'b'=>"test", 'c'=>'xyz'), Q\array_chunk_assoc(array('test'=>array('a'=>10, 'b'=>"test"), 'hallo'=>"abc", "Q rules", 'test.c'=>'xyz'), 'test'));
	}
	
	/**
	 * Test Q\var_give()
	 */
	function test_var_give()
	{
	    $this->assertSame("10", Q\var_give(10, true));
	    $this->assertSame("'test'", Q\var_give("test", true));
	    $this->assertSame("false", Q\var_give(false, true));
        
	    $this->assertSame("(" . __CLASS__  . ")", Q\var_give($this, true));
	    
	    $a = new tst_misc_A("Test");
	    $this->assertSame("(tst_misc_A) Test", Q\var_give($a, true));
	    
	    $this->assertSame("array ( 0 => 10, 'a' => 'test1', 'b' => 'another', 'c' => array ( 0 => 10, 1 => 20, 2 => (tst_misc_A) Test ) )", Q\var_give(array(10, 'a'=>'test1', 'b'=>'another', 'c'=>array(10, 20, $a)), true));
	}
	
	/**
	 * Test Q\serialize_trace()
	 */
	function test_serialize_trace()
	{
	    $debug = array(
	      array('function'=>'xyz'),
	      array('file'=>'/var/www/lib.php', 'line'=>334, 'class'=>'TestClass', 'type'=>'->', 'function'=>'DoItRight'),
	      array('file'=>'/var/www/index.php', 'line'=>523, 'args'=>array(222, "left"), 'function'=>'do_something')
        );

        $expect = array(
		  "unknown (unknown): xyz()",
		  "/var/www/lib.php (334): TestClass->DoItRight()",
		  "/var/www/index.php (523): do_something(222, 'left')"
        );

        $this->assertEquals("#0 $expect[0]\n#1 $expect[1]\n#2 $expect[2]", Q\serialize_trace($debug));
        $this->assertEquals("#0 $expect[1]\n#1 $expect[2]", Q\serialize_trace($debug, 1));
        $this->assertEquals("#0 $expect[2]", Q\serialize_trace($debug, array('file'=>'/var/www/lib.php', 'line'=>334)));
	}
}

if (PHPUnit_MAIN_METHOD == 'Test_Misc::main') Test_Misc::main();
?>