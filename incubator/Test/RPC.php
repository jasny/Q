<?php
require_once 'Test/RPC/Client/Exec.php';
require_once 'Test/RPC/Client/XMLRPC.php';
require_once 'Test/RPC/Server/XMLRPC.php';
/**
 * Static test suite.
 */
class Test_RPC extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('Test_RPC');
        $this->addTestSuite('Test_RPC_Client_Exec');
        $this->addTestSuite('Test_RPC_Client_XMLRPC');
        $this->addTestSuite('Test_RPC_Server_XMLRPC');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

