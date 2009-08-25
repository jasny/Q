<?php
require_once 'Q/Output/Cache.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Output_Cache test case.
 */
class Output_CacheTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Output_Cache
     */
    private $Output_Cache;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        // TODO Auto-generated Output_CacheTest::setUp()
        $this->Output_Cache = new Output_Cache(/* parameters */);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated Output_CacheTest::tearDown()
        $this->Output_Cache = null;
        parent::tearDown();
    }

    /**
     * Constructs the test case.
     */
    public function __construct()
    {    // TODO Auto-generated constructor
    }

    /**
     * Tests Output_Cache->__construct()
     */
    public function test__construct()
    {
        // TODO Auto-generated Output_CacheTest->test__construct()
        $this->markTestIncomplete("__construct test not implemented");
        $this->Output_Cache->__construct(/* parameters */);
    }

    /**
     * Tests Output_Cache->callback()
     */
    public function testCallback()
    {
        // TODO Auto-generated Output_CacheTest->testCallback()
        $this->markTestIncomplete("callback test not implemented");
        $this->Output_Cache->callback(/* parameters */);
    }

    /**
     * Tests Output_Cache::makeKey()
     */
    public function testMakeKey()
    {
        // TODO Auto-generated Output_CacheTest::testMakeKey()
        $this->markTestIncomplete("makeKey test not implemented");
        Output_Cache::makeKey(/* parameters */);
    }
}

