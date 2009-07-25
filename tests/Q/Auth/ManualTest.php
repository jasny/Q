<?php
use Q\Auth;

require_once 'TestHelper.php';
require_once 'Q/Auth/MainTest.php';

/**
 * Auth_Manual test case.
 */
class Auth_ManualTest extends Auth_MainTest 
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        $this->Auth = Auth::with('manual');
        
        $this->Auth->users = array(
            (object)array('id'=>1, 'fullname'=>"Mark Monkey", 'username'=>'monkey', 'password'=>md5('mark'), 'groups'=>'primate', 'active'=>true, 'expire'=>null),
            (object)array('id'=>2, 'fullname'=>"Ben Baboon", 'username'=>'baboon', 'password'=>md5('ben'), 'groups'=>array('ape', 'primate'), 'active'=>false, 'expire'=>null),
            (object)array('id'=>3, 'fullname'=>"George Gorilla", 'username'=>'gorilla', 'password'=>md5('george'), 'groups'=>array('ape', 'primate'), 'active'=>true, 'expire'=>1)
        );        

        parent::setUp();
    }
}

