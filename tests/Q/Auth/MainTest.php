<?php
use Q\Auth;

require_once 'TestHelper.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Q/Auth.php';

/**
 * Auth test case.
 */
class Auth_MainTest extends PHPUnit_Framework_TestCase
{
    /**
     * Q\Auth object
     * @var Auth
     */
    protected $Auth;
    
    /**
     * Original remote address
     * @var string
     */
    protected $remote_addr;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        if (isset($_SERVER['REMOTE_ADDR'])) $this->remote_addr = $_SERVER['REMOTE_ADDR'];
        
        $this->Auth->loginRequired = true;
        $this->Auth->passwordCrypt = 'md5';
        $this->Auth->store = 'env';
        $this->Auth->storeAttemps = 'var';
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->Auth = null;
        
        $_SERVER['REMOTE_ADDR'] = $this->remote_addr;
        parent::tearDown();
    }

    
    /**
     * Tests if singleton is created
     */
    public function testSingleton()
    {
        $this->assertType('Q\Auth', $this->Auth);
    }

    /**
     * Tests Auth->authUser() for result OK
     */
    public function testAuthUser()
    {
        $code = 0;
        $user = $this->Auth->authUser('monkey', 'mark', $code);
        
        $this->assertEquals($code, Auth::OK);
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, $user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);
    }

    /**
     * Tests Auth->authUser() for result UNKNOWN_USER
     */
    public function testAuthUser_UNKNOWN_USER()
    {
        $code = 0;
        $user = $this->Auth->authUser('wolf', 'willem');
        $this->assertEquals($user, Auth::UNKNOWN_USER);
    }

    /**
     * Tests Auth->authUser() for result INCORRECT_PASSWORD
     */
    public function testAuthUser_INCORRECT_PASSWORD()
    {
        $code = 0;
        $user = $this->Auth->authUser('monkey', 'rudolf');
        $this->assertEquals($user, Auth::INCORRECT_PASSWORD);
    }

    
    /**
     * Tests Auth->fetchUser() for result OK
     */
    public function testFetchUser()
    {
        $user = $this->Auth->fetchUser(1);
        
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, $user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);
    }

    /**
     * Tests Auth->fetchUser() for result UNKNOWN_USER
     */
    public function testFetchUser_UNKNOWN_USER()
    {
        $user = $this->Auth->fetchUser(9999);
        $this->assertNull($user);
    }

    /**
     * Tests Auth->fetchUser() with active INACTIVE_USER
     */
    public function testFetchUser_INACTIVE_USER()
    {
        $user = $this->Auth->fetchUser(2);
        
        $this->assertNotNull($user, 'user');
        $this->assertEquals(2, $user->id, 'id');
        $this->assertEquals('Ben Baboon', $user->fullname);
        $this->assertEquals('baboon', $user->username);
        $this->assertEquals(md5('ben'), $user->password);
        $this->assertEquals(array('ape' , 'primate'), $user->groups);
        $this->assertFalse((bool) $user->active, 'active');
    }

    
    /**
     * Tests Auth->login()
     */
    public function testLogin()
    {
        $this->Auth->login('monkey', 'mark');
        $user = $this->Auth->user();
        
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, $user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);
    }

    /**
     * Tests Auth->authUser() for result UNKNOWN_USER
     */
    public function testLogin_UNKNOWN_USER()
    {
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::UNKNOWN_USER);
        $this->Auth->login('wolf', 'willem');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertNull($this->Auth->user()->id, 'id');
        $this->assertEquals('wolf', $this->Auth->user()->username);
    }

    /**
     * Tests Auth->authUser() for result INCORRECT_PASSWORD
     */
    public function testLogin_INCORRECT_PASSWORD()
    {
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::UNKNOWN_USER);
        $this->Auth->login('monkey', 'rudolf');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->id, 'id');
        $this->assertNull($this->Auth->user()->username, 'monkey');
    }

    /**
     * Tests Auth->authUser() for result INACTIVE_USER
     */
    public function testLogin_INACTIVE_USER()
    {
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::INACTIVE_USER);
        $this->Auth->login('baboon', 'ben');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(2, $this->Auth->user()->id, 'id');
    }

    /**
     * Tests Auth->authUser() for result PASSWORD_EXPIRED
     */
    public function testLogin_PASSWORD_EXPIRED()
    {
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::PASSWORD_EXPIRED);
        $this->Auth->login('gorilla', 'george');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(3, $this->Auth->user()->id, 'id');
    }

    
    /**
     * Tests Auth->logout()
     */
    public function testLogout()
    {
        $this->Auth->login('monkey', 'mark');
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertEquals(1, $this->Auth->user()->id, 'id');
        
        $this->setExpectedException('Q\Auth_Session_Exception');
        $this->Auth->logout();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->id, 'id');
    }

    
    /**
     * Tests Auth->start() for result OK
     */
    public function testStart()
    {
        $_ENV['Q_AUTH__uid'] = 1;
        $_ENV['Q_AUTH__hash'] = md5(1 . md5('mark'));
        
        $this->Auth->loginRequired = true;
        
        $this->Auth->start();
        $this->assertTrue($this->Auth->isLoggedIn());
        
        $user = $this->Auth->user();
        $this->assertNotNull($user, 'user');
        $this->assertEquals(1, $user->id, 'id');
        $this->assertEquals('Mark Monkey', $user->fullname);
        $this->assertEquals('monkey', $user->username);
        $this->assertEquals(md5('mark'), $user->password);
        $this->assertEquals(array('primate'), $user->groups);
    }

    /**
     * Tests Auth->start() with no session
     */
    public function testStart_NoSession()
    {
        $this->setExpectedException('Q\Auth_Session_Exception');
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNull($this->Auth->user(), 'user');
    }

    /**
     * Tests Auth->start() without required login
     */
    public function testStart_NoLoginRequired()
    {
        $this->Auth->loginRequired = false;
        $result = $this->Auth->start();
        
        $this->assertEquals(Auth::NO_SESSION, $result, 'result code');
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNull($this->Auth->user(), 'user');
    }

    /**
     * Tests Auth->start() for result OK
     */
    public function testStart_UNKNOWN_USER()
    {
        $_ENV['Q_AUTH__uid'] = 7;
        
        $this->setExpectedException('Q\Auth_Session_Exception', null, Auth::UNKNOWN_USER);
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNull($this->Auth->user(), 'user');
    }

    /**
     * Tests Auth->start() for result INACTIVE_USER
     */
    public function testStart_INACTIVE_USER()
    {
        $_ENV['Q_AUTH__uid'] = 2;
        $_ENV['Q_AUTH__hash'] = md5(2 . md5('ben'));
        
        $this->setExpectedException('Q\Auth_Session_Exception', null, Auth::INACTIVE_USER);
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(2, $this->Auth->user()->id, 'id');
        $this->assertFalse((bool) $this->Auth->user()->active, 'active');
    }

    /**
     * Tests Auth->start() for result INACTIVE_USER
     */
    public function testStart_NoLoginRequired_INACTIVE_USER()
    {
        $_ENV['Q_AUTH__uid'] = 2;
        $_ENV['Q_AUTH__hash'] = md5(2 . md5('ben'));
        
        $this->Auth->loginRequired = false;
        $result = $this->Auth->start();
        
        $this->assertEquals(Auth::INACTIVE_USER, $result, 'result code');
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(2, $this->Auth->user()->id, 'id');
    }

    /**
     * Tests Auth->start() without required login for result PASSWORD_EXPIRED
     */
    public function testStart_PASSWORD_EXPIRED()
    {
        $_ENV['Q_AUTH__uid'] = 3;
        $_ENV['Q_AUTH__hash'] = md5(3 . md5('george'));
        
        $result = $this->Auth->start();
        
        $this->assertEquals(Auth::OK, $result, 'result code');
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(3, $this->Auth->user()->id, 'id');
    }

    /**
     * Tests Auth->start() with an incorrect hash
     */
    public function testStart_INVALID_SESSION()
    {
        $_ENV['Q_AUTH__uid'] = 1;
        $_ENV['Q_AUTH__hash'] = "abc";
        
        $this->setExpectedException('Q\Auth_Session_Exception', null, Auth::INVALID_SESSION);
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->id, 'id');
    }

    /**
     * Tests Auth->start() with an incorrect hash
     */
    public function testStart_INVALID_SESSION_NoHash()
    {
        $_ENV['Q_AUTH__uid'] = 1;
        
        $this->setExpectedException('Q\Auth_Session_Exception', null, Auth::INVALID_SESSION);
        $this->Auth->start();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->id, 'id');
    }

    
    /**
     * Tests Auth->isBlocked()
     */
    public function testIsBlocked()
    {
        $this->Auth->loginAttempts = 1;
        
        $this->assertFalse($this->Auth->isBlocked('10.0.0.1', true), '1st attempt');
        $this->assertTrue($this->Auth->isBlocked('10.0.0.1', true), '2nd attempt');
    }

    /**
     * Tests Auth->isBlocked()
     */
    public function testIsBlocked_Unblockable()
    {
        $this->Auth->loginAttempts = 1;
        
        $this->assertEquals(0, $this->Auth->isBlocked('127.0.0.1', true), '1st attempt');
        $this->assertEquals(0, $this->Auth->isBlocked('127.0.0.1', true), '2nd attempt');
        $this->assertEquals(0, $this->Auth->isBlocked('127.0.0.1', true), '3rd attempt');
    }

    /**
     * Tests Auth->isBlocked()
     */
    public function testLogin_Blocked()
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $this->Auth->loginAttempts = 1;
        $this->Auth->isBlocked('10.0.0.1', 5);
        
        $this->setExpectedException('Q\Auth_Login_Exception', null, Auth::HOST_BLOCKED);
        $this->Auth->login('monkey', 'mark');
    }

    
    /**
     * Tests Auth->authz()
     */
    public function testAuthz()
    {
        $this->Auth->login('monkey', 'mark');
        $this->Auth->authz('primate');
    }

    /**
     * Tests Auth->authz() where authorization fails
     */
    public function testAuthz_Fail()
    {
        $this->Auth->login('monkey', 'mark');
        
        $this->setExpectedException('Q\Authz_Exception', "User 'monkey' is not in group 'ape'.");
        $this->Auth->authz('ape');
    }

    /**
     * Tests Auth->authz() with multiple groups where authorization fails
     */
    public function testAuthz_FailMultiple()
    {
        $this->Auth->login('monkey', 'mark');
        
        $this->setExpectedException('Q\Authz_Exception', "User 'monkey' is not in groups 'ape', 'pretty'.");
        $this->Auth->authz('primate', 'ape', 'pretty');
    }

    /**
     * Tests Auth->authz() whith no session
     */
    public function testAuthz_NoSession()
    {
        $this->setExpectedException('Q\Auth_Session_Exception', "User is not logged in.", Auth::NO_SESSION);
        $this->Auth->authz('primate');
    }
    
    /**
     * Tests Auth->authz() whith no session
     */
    public function testAuthz_INACTIVE_USER()
    {
        try {
            $this->Auth->login('baboon', 'ben');
        } catch (Q\Auth_Login_Exception $e) {}
        
        $this->setExpectedException('Q\Auth_Session_Exception', "User is not logged in.", Auth::NO_SESSION);
        $this->Auth->authz('primate');
    }    
}

