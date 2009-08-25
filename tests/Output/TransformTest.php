<?php
require_once 'Q/Output/Transform.php';
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Output_Transform test case.
 */
class Output_TransformTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Output_Transform
     */
    private $Output_Transform;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        // TODO Auto-generated Output_TransformTest::setUp()
        $this->Output_Transform = new Output_Transform(/* parameters */);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated Output_TransformTest::tearDown()
        $this->Output_Transform = null;
        parent::tearDown();
    }

    /**
     * Constructs the test case.
     */
    public function __construct()
    {    // TODO Auto-generated constructor
    }

    /**
     * Tests Output_Transform->__construct()
     */
    public function test__construct()
    {
        // TODO Auto-generated Output_TransformTest->test__construct()
        $this->markTestIncomplete("__construct test not implemented");
        $this->Output_Transform->__construct(/* parameters */);
    }

    /**
     * Tests Output_Transform->callback()
     */
    public function testCallback()
    {
        // TODO Auto-generated Output_TransformTest->testCallback()
        $this->markTestIncomplete("callback test not implemented");
        $this->Output_Transform->callback(/* parameters */);
    }
}

