<?php
use Q\RPC_Client_Exec;

require_once __DIR__ . '/../../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/RPC/Client/Exec.php';

/**
 * RPC_Client_Exec test case.
 */
class Test_RPC_Client_Exec extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    /**
     * Assert an ls command.
     * 
     * @param string $result
     */
    protected function assertLs($result)
    {
    	$list = scandir(__DIR__, 0);
    	$result_list = explode("\n", trim($result));
    	
    	$this->assertEquals(sort($list), sort($result_list));
    }
    
    /**
     * Test RPC_Client_Exec->about()
     */
    public function testAbout()
    {
    	$client = new RPC_Client_Exec('/bin/ls');
    	
    	$this->assertEquals('(Exec) -> /bin/ls', $client->about());
    	$this->assertEquals('(Exec)', $client->about(false));
    	$this->assertEquals('(Exec) -> /bin/ls /etc', $client->about("/bin/ls /etc"));
    }
    
    /**
     * Test RPC_Client_Exec->escapeArg()
     */
    public function testEscapeArg()
    {
    	$client = new RPC_Client_Exec('/bin/ls');
    	
    	$this->assertEquals("'Test'", $client->escapeArg("Test"));
    	$this->assertEquals("'Te'\\''st'", $client->escapeArg("Te'st"));
    	$this->assertEquals("'a' 'b' 'c'", $client->escapeArg(array('a', 'b', 'c')));
    	$this->assertEquals("'--host'='localhost' '--port'='3306' '--db'='mydb'", $client->escapeArg(array('host'=>'localhost', 'port'=>3306, 'db'=>'mydb')));
    }

    /**
     * Test RPC_Client_Exec->escapeArg() with alternative glue
     */
    public function testEscapeArgArrayc()
    {
    	$client = new RPC_Client_Exec('/bin/ls', array('arrayc'=>array('glue'=>',', 'key-value'=>':', 'key-prefix'=>'-')));

    	$this->assertEquals("'a','b','c'", $client->escapeArg(array('a', 'b', 'c')));
    	$this->assertEquals("'-host':'localhost','-port':'3306','-db':'mydb'", $client->escapeArg(array('host'=>'localhost', 'port'=>3306, 'db'=>'mydb')));
    }

    /**
     * Test RPC_Client_Exec->escapeArg() with serialize callback
     */
    public function testEscapeArgSerialize()
    {
    	$client = new RPC_Client_Exec('/bin/ls');
    	$client->options->serialize = create_function('$arg', 'return "{" . (is_scalar($arg) ? $arg : join(",", $arg)) . "}";');
    	
    	$this->assertEquals("{Test}", $client->escapeArg("Test"));
    	$this->assertEquals("{Te'st}", $client->escapeArg("Te'st"));
    	$this->assertEquals("{a,b,c}", $client->escapeArg(array('a', 'b', 'c')));
    	$this->assertEquals("{localhost,3306,mydb}", $client->escapeArg(array('host'=>'localhost', 'port'=>3306, 'db'=>'mydb')));
    }    
    
	/**
	 * Execute a command using the function name as first argument
	 */
	public function testCommand()
    {
    	if (!file_exists('/usr/bin/svn')) $this->markTestSkipped("Unable to test command: /usr/bin/svn does not exist.");

    	$url = "svn://office.javeline.nl/jasny/qdb/trunk";
    	$list = shell_exec('/usr/bin/svn list ' . escapeshellarg($url));
    	
    	$client = new RPC_Client_Exec('/usr/bin/svn');
    	$svn = $client->getInterface();
    	$this->assertEquals($list, $svn->list($url));
    }

	/**
	 * Execute an ls command replacing {$0} with the function name
	 */
	public function testLsArg0()
    {
    	$client = new RPC_Client_Exec('/bin/{$0}');
    	$rpc = $client->getInterface();
    	
    	$result = $rpc->ls(__DIR__, '-1a');
    	$this->assertLs($result);
	}
    
	/**
	 * Execute an ls command replacing {$0} with the function name and {$1} with the arg 1, etc
	 */
	public function testLsArgs()
    {
    	$client = new RPC_Client_Exec('/bin/{$0} -1 {$1} {$2}');
    	$rpc = $client->getInterface();
    	
    	$result = $rpc->ls(__DIR__, '-a');
    	$this->assertLs($result);
    }

	/**
	 * Execute an ls command replacing {$0} with the function name and {$*} with the args
	 */
	public function testLsArgAll()
    {
    	$client = new RPC_Client_Exec('/bin/{$0} -1 {$*}');
    	$rpc = $client->getInterface();
    	
    	$result = $rpc->ls(__DIR__, '-a');
    	$this->assertLs($result);
	}
	
	/**
	 * Execute a command which throws a fault.
	 */
	public function testFault()
    {
    	$client = new RPC_Client_Exec('/bin/' . md5(microtime()));
    	$rpc = $client->getInterface();
    	
    	$this->setExpectedException('Q\RPC_Fault', "Execution of command failed");
    	$result = $rpc->test();
	}
}
   
if (PHPUnit_MAIN_METHOD == 'Test_RPC_Client_Exec::main') Test_RPC_Client_Exec::main();
?>