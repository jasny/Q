<?php
use Q\Transform;

require_once dirname(dirname(dirname(__FILE__))) . '/TestHelper.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Transform.php';

/**
 * Test factory method
 */
class Transform_CreationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    /**
     * Test driver xsl
     */
    public function testDriverXSL()
    {
        $transform = Transform::with('xsl');
        $this->assertType('Q\Transform_XSL', $transform);
    }
    
    /**
     * Test driver replace
     */
    public function testDriverReplace()
    {
        $transform = Transform::with('replace');
        $this->assertType('Q\Transform_Replace', $transform);
    }
    
    /**
     * Test driver php
     */
    public function testDriverPHP()
    {
        $transform = Transform::with('php');
        $this->assertType('Q\Transform_PHP', $transform);
    }
    
    /**
     * Test driver array2xml
     */
    public function testDriverArray2XML()
    {
        $transform = Transform::with('array2xml');
        $this->assertType('Q\Transform_Array2XML', $transform);
    }
    
    public function testOptions()
    {
        $transform = Transform::with('xsl', array('test' => 'TESTAREA'));
        $this->assertType('Q\Transform_XSL', $transform);
        
        $refl = new ReflectionProperty($transform, 'test');
        $refl->setAccessible(true);
        $test = $refl->getValue($transform);
        $this->assertEquals('TESTAREA', $test);
    }
    
}

if (PHPUnit_MAIN_METHOD == 'Transform_CreationTest::main') Transform_CreationTest::main();

