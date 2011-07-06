<?php

require_once __DIR__ . '/../init.php';
require_once 'PHPUnit/Framework/TestSuite.php';

require_once 'Fs/Test.php';
require_once 'Fs/NodeTest.php';
require_once 'Fs/FileTest.php';
require_once 'Fs/FifoTest.php';
require_once 'Fs/DirTest.php';
require_once 'Fs/CharTest.php';
require_once 'Fs/BlockTest.php';
require_once 'Fs/SocketTest.php';
require_once 'Fs/Symlink/BrokenTest.php';
require_once 'Fs/Symlink/DirTest.php';
require_once 'Fs/Symlink/FileTest.php';

/**
 * Static test suite.
 *
 * @todo Write a test suite for external files (over http)
 */
class Fs_AllTests extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->addTestSuite('Fs_Test');
        $this->addTestSuite('Fs_NodeTest');
        $this->addTestSuite('Fs_FileTest');
        $this->addTestSuite('Fs_FifoTest');
        $this->addTestSuite('Fs_DirTest');
        $this->addTestSuite('Fs_CharTest');
        $this->addTestSuite('Fs_BlockTest');
        $this->addTestSuite('Fs_SocketTest');
        $this->addTestSuite('Fs_Symlink_BrokenTest');
        $this->addTestSuite('Fs_Symlink_DirTest');
        $this->addTestSuite('Fs_Symlink_FileTest');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}
