<?php
use Q\Authenticate;

require_once __DIR__ . '/../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';

require_once 'Q/Authenticate.php';

/**
 * Authenticate test case.
 */
class Test_Authenticate_Main extends PHPUnit_Framework_TestCase
{
    /**
     * @var Authenticate
     */
    protected $Authenticate;

    /**
     * Original remote address
     * @var string
     */
    protected $remote_addr;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        
        $this->remote_addr = $_SERVER['REMOTE_ADDR']; 
        
        $this->Authenticate->loginRequired = true;
        $this->Authenticate->crypt = 'md5';
        $this->Authenticate->store = 'env';
        $this->Authenticate->storeAttemps = 'none';
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->Authenticate = null;
        
        $_SERVER['REMOTE_ADDR'] = $this->remote_addr;
        parent::tearDown();
    }
    
    
	/**
     * Tests if singleton is created
     */    
    public function testSingleton ()
    {
        $this->assertType('Q\Authenticate', $this->Authenticate);        
    }
    
	/**
     * Tests Authenticate->authUser() for result OK
     */
    public function testAuthUser ()
    {
        $code = 0;
        $user = $this->Authenticate->authUser('monkey', 'mark', $code);
        
        $this->assertEquals($code, Authenticate::OK);
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, (int)$user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);
    }

	/**
     * Tests Authenticate->authUser() for result UNKNOWN_USER
     */
    public function testAuthUser_UNKNOWN_USER ()
    {
        $code = 0;
        $user = $this->Authenticate->authUser('wolf', 'willem', $code);
        
        $this->assertEquals($code, Authenticate::UNKNOWN_USER);
        $this->assertNotNull($user, 'user');
        $this->assertNull($user->id, 'id');
        $this->assertEquals('wolf', $user->username);
    }
    
	/**
     * Tests Authenticate->authUser() for result INCORRECT_PASSWORD
     */
    public function testAuthUser_INCORRECT_PASSWORD ()
    {
        $code = 0;
        $user = $this->Authenticate->authUser('monkey', 'rudolf', $code);
        
        $this->assertEquals($code, Authenticate::INCORRECT_PASSWORD);
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, (int)$user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);
    }

	/**
     * Tests Authenticate->authUser() for result INACTIVE_USER
     */
    public function testAuthUser_INACTIVE_USER ()
    {
        $code = 0;
        $user = $this->Authenticate->authUser('baboon', 'ben', $code);
        
        $this->assertEquals($code, Authenticate::INACTIVE_USER);
        $this->assertNotNull($user, 'user');
        $this->assertEquals(2, (int)$user->id, 'id');
        $this->assertEquals('Ben Baboon', $user->fullname);
        $this->assertEquals('baboon', $user->username);
        $this->assertEquals(md5('ben'), $user->password);
        $this->assertEquals(array('ape', 'primate'), $user->groups);
    }    

	/**
     * Tests Authenticate->authUser() for result PASSWORD_EXPIRED
     */
    public function testAuthUser_PASSWORD_EXPIRED ()
    {
        $code = 0;
        $user = $this->Authenticate->authUser('gorilla', 'george', $code);
        
        $this->assertEquals($code, Authenticate::PASSWORD_EXPIRED);
        $this->assertNotNull($user, 'user');
        $this->assertEquals(3, (int)$user->id, 'id');
        $this->assertEquals('George Gorilla', $user->fullname);
        $this->assertEquals('gorilla', $user->username);
        $this->assertEquals(md5('george'), $user->password);
        $this->assertEquals(array('ape', 'primate'), $user->groups);
    }
    
    
    /**
     * Tests Authenticate->fetchUser() for result OK
     */
    public function testFetchUser ()
    {
        $user = $this->Authenticate->fetchUser(1);
        
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, (int)$user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);
    }

	/**
     * Tests Authenticate->fetchUser() for result UNKNOWN_USER
     */
    public function testFetchUser_UNKNOWN_USER ()
    {
        $user = $this->Authenticate->fetchUser(9999);
        $this->assertNull($user);
    }
    
    /**
     * Tests Authenticate->fetchUser() with active INACTIVE_USER
     */
    public function testFetchUser_INACTIVE_USER ()
    {
        $user = $this->Authenticate->fetchUser(2);
        
        $this->assertNotNull($user, 'user');
        $this->assertEquals(2, (int)$user->id, 'id');
        $this->assertEquals('Ben Baboon', $user->fullname);
        $this->assertEquals('baboon', $user->username);
        $this->assertEquals(md5('ben'), $user->password);
        $this->assertEquals(array('ape', 'primate'), $user->groups);
        $this->assertFalse((bool)$user->active, 'active');
    }
    
    
    /**
     * Tests Authenticate->login()
     */
    public function testLogin ()
    {
        $this->Authenticate->login('monkey', 'mark');
        $user = $this->Authenticate->user;
        
        $this->assertTrue($this->Authenticate->isLoggedIn());
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, (int)$user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);        
    }

	/**
     * Tests Authenticate->authUser() for result UNKNOWN_USER
     */
    public function testLogin_UNKNOWN_USER ()
    {
        $this->setExpectedException('Q\Authenticate_Login_Exception', null, Authenticate::UNKNOWN_USER);
        $this->Authenticate->login('wolf', 'willem');
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertNull($this->Authenticate->user->id, 'id');
        $this->assertEquals('wolf', $this->Authenticate->user->username);
    }
    
	/**
     * Tests Authenticate->authUser() for result INCORRECT_PASSWORD
     */
    public function testLogin_INCORRECT_PASSWORD ()
    {
        $this->setExpectedException('Q\Authenticate_Login_Exception', null, Authenticate::UNKNOWN_USER);
        $this->Authenticate->login('monkey', 'rudolf');
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(1, (int)$this->Authenticate->user->id, 'id');
        $this->assertNull($this->Authenticate->user->username, 'monkey');
    }

	/**
     * Tests Authenticate->authUser() for result INACTIVE_USER
     */
    public function testLogin_INACTIVE_USER ()
    {
        $this->setExpectedException('Q\Authenticate_Login_Exception', null, Authenticate::INACTIVE_USER);
        $this->Authenticate->login('baboon', 'ben');
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(2, (int)$this->Authenticate->user->id, 'id');
    }    

	/**
     * Tests Authenticate->authUser() for result PASSWORD_EXPIRED
     */
    public function testLogin_PASSWORD_EXPIRED ()
    {
        $this->setExpectedException('Q\Authenticate_Login_Exception', null, Authenticate::PASSWORD_EXPIRED);
        $this->Authenticate->login('gorilla', 'george');
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(3, (int)$this->Authenticate->user->id, 'id');
    }
    
    
    /**
     * Tests Authenticate->logout()
     */
    public function testLogout ()
    {
        $this->Authenticate->login('monkey', 'mark');
        $this->assertTrue($this->Authenticate->isLoggedIn());
        $this->assertEquals(1, $this->Authenticate->user->id, 'id');

        $this->setExpectedException('Q\Authenticate_Session_Exception');
        $this->Authenticate->logout();
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(1, (int)$this->Authenticate->user->id, 'id');
    }

    
    /**
     * Tests Authenticate->start() for result OK
     */
    public function testStart ()
    {
        $_ENV['PHP_AUTH__uid'] = 1;
        $_ENV['PHP_AUTH__hash'] = md5(1 . md5('mark'));
        
        $this->Authenticate->loginRequired = true;
        
        $this->Authenticate->start();
        $this->assertTrue($this->Authenticate->isLoggedIn());
        
        $user = $this->Authenticate->user;
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, (int)$user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);        
    }

    /**
     * Tests Authenticate->start() with no session
     */
    public function testStart_NoSession ()
    {
        $this->setExpectedException('Q\Authenticate_Session_Exception');
        $this->Authenticate->start();
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNull($this->Authenticate->user, 'user');
    }

    /**
     * Tests Authenticate->start() without required login
     */
    public function testStart_NoLoginRequired ()
    {
        $this->Authenticate->loginRequired = false;
        $result = $this->Authenticate->start();
        
        $this->assertEquals(Authenticate::NO_SESSION, $result, 'result code');
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNull($this->Authenticate->user, 'user');
    }
    
    /**
     * Tests Authenticate->start() for result OK
     */
    public function testStart_UNKNOWN_USER ()
    {
        $_ENV['PHP_AUTH__uid'] = 7;
        
        $this->setExpectedException('Q\Authenticate_Session_Exception', null, Authenticate::UNKNOWN_USER);
        $this->Authenticate->start();
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNull($this->Authenticate->user, 'user');
    }
    
	/**
     * Tests Authenticate->start() for result INACTIVE_USER
     */
    public function testStart_INACTIVE_USER ()
    {
        $_ENV['PHP_AUTH__uid'] = 2;
        $_ENV['PHP_AUTH__hash'] = md5(2 . md5('ben'));
        
        $this->setExpectedException('Q\Authenticate_Session_Exception', null, Authenticate::INACTIVE_USER);
        $this->Authenticate->start();
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(2, (int)$this->Authenticate->user->id, 'id');
        $this->assertFalse((bool)$this->Authenticate->user->active, 'active');
    }    

    /**
     * Tests Authenticate->start() for result INACTIVE_USER
     */
    public function testStart_NoLoginRequired_INACTIVE_USER ()
    {
        $_ENV['PHP_AUTH__uid'] = 2;
        $_ENV['PHP_AUTH__hash'] = md5(2 . md5('ben'));
        
        $this->Authenticate->loginRequired = false;
        $result = $this->Authenticate->start();
        
        $this->assertEquals(Authenticate::INACTIVE_USER, $result, 'result code');
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(2, (int)$this->Authenticate->user->id, 'id');
    }
    
    /**
     * Tests Authenticate->start() without required login for result PASSWORD_EXPIRED
     */
    public function testStart_PASSWORD_EXPIRED ()
    {
        $_ENV['PHP_AUTH__uid'] = 3;
        $_ENV['PHP_AUTH__hash'] = md5(3 . md5('george'));
                
        $result = $this->Authenticate->start();

        $this->assertEquals(Authenticate::OK, $result, 'result code');
        $this->assertTrue($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(3, (int)$this->Authenticate->user->id, 'id');
    }
        
    /**
     * Tests Authenticate->start() with an incorrect hash
     */
    public function testStart_INVALID_SESSION ()
    {
        $_ENV['PHP_AUTH__uid'] = 1;
        $_ENV['PHP_AUTH__hash'] = "abc";
        
        $this->setExpectedException('Q\Authenticate_Session_Exception', null, Authenticate::INVALID_SESSION);
        $this->Authenticate->start();
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(1, (int)$this->Authenticate->user->id, 'id');
    }

    /**
     * Tests Authenticate->start() with an incorrect hash
     */
    public function testStart_INVALID_SESSION_NoHash ()
    {
        $_ENV['PHP_AUTH__uid'] = 1;
        
        $this->setExpectedException('Q\Authenticate_Session_Exception', null, Authenticate::INVALID_SESSION);
        $this->Authenticate->start();
        
        $this->assertFalse($this->Authenticate->isLoggedIn());
        $this->assertNotNull($this->Authenticate->user, 'user');
        $this->assertEquals(1, $this->Authenticate->user->id, 'id');
    }
    
    
    /**
     * Tests Authenticate->isBlocked()
     */
    public function testIsBlocked ()
    {
        $this->Authenticate->loginAttempts = 1;
        
        $this->assertFalse($this->Authenticate->isBlocked('10.0.0.1', true), '1st attempt');
        $this->assertTrue($this->Authenticate->isBlocked('10.0.0.1', true), '2nd attempt');
    }
    
    /**
     * Tests Authenticate->isBlocked()
     */
    public function testIsBlocked_Unblockable ()
    {
        $this->Authenticate->loginAttempts = 1;
        
        $this->assertEquals(0, $this->Authenticate->isBlocked('127.0.0.1', true), '1st attempt');
        $this->assertEquals(0, $this->Authenticate->isBlocked('127.0.0.1', true), '2nd attempt');
        $this->assertEquals(0, $this->Authenticate->isBlocked('127.0.0.1', true), '3rd attempt');
    }
    
    /**
     * Tests Authenticate->isBlocked()
     */
    public function testLogin_Blocked ()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $this->Authenticate->loginAttempts = 1;
        $this->Authenticate->isBlocked('10.0.0.1', 5);

        $this->setExpectedException('Q\Authenticate_Login_Exception', null, Authenticate::HOST_BLOCKED);
        $this->Authenticate->login('monkey', 'mark');
    }    
}

