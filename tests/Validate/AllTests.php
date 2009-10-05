<?php

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'Validate/CreationTest.php';
require_once 'Validate/NL/BankAccount.php';
require_once 'Validate/BSN.php';
require_once 'Validate/Compare.php';
require_once 'Validate/Callback.php';
require_once 'Validate/Date.php';
require_once 'Validate/Email.php';
require_once 'Validate/Empty.php';
require_once 'Validate/Length.php';
require_once 'Validate/Regexp.php';
require_once 'Validate/Time.php';
require_once 'Validate/And.php';
require_once 'Validate/Or.php';
require_once 'Validate/Xor.php';

/**
 * Static test suite.
 */
class ValidatorTest extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct()
    {
        $this->setName('ValidatorTest');
        $this->addTestSuite('Validator_CreationTest');
        $this->addTestSuite('Validator_NL_BankAccountTest');
        $this->addTestSuite('Validator_NL_BSNTest');
        $this->addTestSuite('Validator_CompareTest');
        $this->addTestSuite('Validator_CallbackTest');
        $this->addTestSuite('Validator_DateTest');
        $this->addTestSuite('Validator_EmailTest');
        $this->addTestSuite('Validator_EmptyTest');
        $this->addTestSuite('Validator_LengthTest');
        $this->addTestSuite('Validator_RegexpTest');
        $this->addTestSuite('Validator_TimeTest');
        $this->addTestSuite('Validator_AndTest');
        $this->addTestSuite('Validator_OrTest');
        $this->addTestSuite('Validator_XorTest');
    }
    
    /**
     * Creates the suite.
     */
    public static function suite()
    {
        return new self();
    }
}

