<?php
use Q\Authenticate;

require_once 'Test/Authenticate/Main.php';

/**
 * Authenticate_Manual test case.
 */
class Test_Authenticate_Manual extends Test_Authenticate_Main 
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        $this->Authenticate = Authenticate::with('manual');
        
        $this->Authenticate->users = array(
            (object)array('id'=>1, 'fullname'=>"Mark Monkey", 'username'=>'monkey', 'password'=>md5('mark'), 'groups'=>'primate', 'active'=>true, 'expire'=>null),
            (object)array('id'=>2, 'fullname'=>"Ben Baboon", 'username'=>'baboon', 'password'=>md5('ben'), 'groups'=>array('ape', 'primate'), 'active'=>false, 'expire'=>null),
            (object)array('id'=>3, 'fullname'=>"George Gorilla", 'username'=>'gorilla', 'password'=>md5('george'), 'groups'=>array('ape', 'primate'), 'active'=>true, 'expire'=>1)
        );        

        parent::setUp();
    }
}

