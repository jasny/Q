<?php
use Q\Validate;

require_once 'TestHelper.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Validate.php';

/**
 * Test factory method
 */
class Validate_CreationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    /**
     * Test driver regex
     */
    public function testDriverRegex()
    {
        $transform = Transform::with('regex');
        $this->assertType('Q\Validator_Regex', $transform);
    }
    
    /**
     * Test driver email
     */
    public function testDriverEmail()
    {
        $transform = Transform::with('email');
        $this->assertType('Q\Validator_Email', $transform);
    }

    /**
     * Test driver date
     */
    public function testDriverDate()
    {
        $transform = Transform::with('date');
        $this->assertType('Q\Validator_Date', $transform);
    }

    /**
     * Test driver time
     */
    public function testDriverTime()
    {
        $transform = Transform::with('time');
        $this->assertType('Q\Validator_Time', $transform);
    }

    /**
     * Test driver NL_bankaccount
     */
    public function testDriverNLBankAccount()
    {
        $transform = Transform::with('NL_bankaccount');
        $this->assertType('Q\Validator_NL_BankAccount', $transform);
    }

    /**
     * Test driver fn
     */
    public function testDriverFunction()
    {
        $transform = Transform::with('fn');
        $this->assertType('Q\Validator_Function', $transform);
    }
    
    /**
     * Test driver compare
     */
    public function testDriverCompare()
    {
        $transform = Transform::with('compare');
        $this->assertType('Q\Validator_Compare', $transform);
    }
    
    /**
     * Test driver empty
     */
    public function testDriverEmpty()
    {
        $transform = Transform::with('empty');
        $this->assertType('Q\Validator_Empty', $transform);
    }
    
    /**
     * Test driver required
     */
    public function testDriverRequired()
    {
        $transform = Transform::with('requiored');
        $this->assertType('Q\Validator_Required', $transform);
    }
    
    /**
     * Test driver length
     */
    public function testDriverLength()
    {
        $transform = Transform::with('length');
        $this->assertType('Q\Validator_Length', $transform);
    }
    
    /**
     * Test driver minlength
     */
    public function testDriverMinlength()
    {
        $transform = Transform::with('minlength');
        $this->assertType('Q\Validator_Minlength', $transform);
    }
    
    /**
     * Test driver maxlength
     */
    public function testDriverMaxlength()
    {
        $transform = Transform::with('maxlength');
        $this->assertType('Q\Validator_Maxlength', $transform);
    }
    
}
