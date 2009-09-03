<?php
use Q\Auth;

require_once 'TestHelper.php';
require_once 'Auth/MainTest.php';

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
            (object)array('id'=>1, 'fullname'=>"Mark Monkey", 'username'=>'monkey', 'password'=>md5('mark'), 'roles'=>'primate', 'active'=>true, 'expires'=>null),
            (object)array('id'=>2, 'fullname'=>"Ben Baboon", 'username'=>'baboon', 'password'=>md5('ben'), 'roles'=>array('ape', 'primate'), 'active'=>false, 'expires'=>null),
            (object)array('id'=>3, 'fullname'=>"George Gorilla", 'username'=>'gorilla', 'password'=>md5('george'), 'roles'=>array('ape', 'primate'), 'active'=>true, 'expires'=>1)
        );        

        parent::setUp();
    }
}
