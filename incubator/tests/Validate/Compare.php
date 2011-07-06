<?php
use Q\Validator_Compare, Q\Validator;

require_once 'TestHelper.php';
require_once 'Q/Validate/Compare.php';

/**
 * Validator_Compare test case.
 */
class Validator_CompareTest extends PHPUnit_Framework_TestCase 
{

	/**
	 * Run test from php
	 */
	public static function main() 
	{
		PHPUnit_TextUI_TestRunner::run ( new PHPUnit_Framework_TestSuite ( __CLASS__ ) );
	}
		
	/**
	 * Tests Validator_Compare->validate()
	 */
	public function testValidate() 
	{
		$validate = new Validator_Compare('>', 10);
		$contents = $transform->process(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original', 'b'=>array('w', 'x', 'y'))));

		$this->assertType('Q\Validator_compare', $validate);
		$this->assertEquals(true, $validate->validate(20));
	}

    /**
     * Tests Validator_Compare->validate() - return false
     */
    public function testValidateFalse() 
    {
        $validate = new Validator_Compare('test');
        $contents = $transform->process(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original', 'b'=>array('w', 'x', 'y'))));

        $this->assertType('Q\Validator_compare', $validate);
        $this->assertEquals(false, $validate->validate('10'));
    }
}
