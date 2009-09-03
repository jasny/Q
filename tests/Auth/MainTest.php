<?php
use Q\Auth;

require_once 'TestHelper.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'Q/Auth.php';

/**
 * Auth test case.
 * 
 * @todo Test with different checksum options.
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
        $this->Auth->checksumPassword = true;
        $this->Auth->checksumClientIp = true;
        $this->Auth->passwordCrypt = 'md5';
        $this->Auth->checksumCrypt = 'md5:secret=s3cret';
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
        putenv('AUTH=');
        
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
        $user = $this->Auth->authUser('monkey', 'mark');
        
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(1, $user->getId(), 'id');
        $this->assertEquals('Mark Monkey', $user->getFullname());
        $this->assertEquals('monkey', $user->getUsername());
        $this->assertEquals(md5('mark'), $user->getPassword());
        $this->assertEquals(array('primate'), $user->getRoles());
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
        
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(1, $user->getId(), 'id');
        $this->assertEquals('Mark Monkey', $user->getFullname());
        $this->assertEquals('monkey', $user->getUsername());
        $this->assertEquals(md5('mark'), $user->getPassword());
        $this->assertEquals(array('primate'), $user->getRoles());
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
        
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(2, $user->getId(), 'id');
        $this->assertEquals('Ben Baboon', $user->getFullname());
        $this->assertEquals('baboon', $user->getUsername());
        $this->assertEquals(md5('ben'), $user->getPassword());
        $this->assertEquals(array('ape' , 'primate'), $user->getRoles());
        $this->assertFalse($user->isActive(), 'active');
    }

    
    /**
     * Tests Auth->login()
     */
    public function testLogin()
    {
        $this->Auth->login('monkey', 'mark');
        $user = $this->Auth->user();
        
        $this->assertEquals(Auth::OK, $this->Auth->getStatus(), 'status');
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(1, $user->getId(), 'id');
        $this->assertEquals('Mark Monkey', $user->getFullname());
        $this->assertEquals('monkey', $user->getUsername());
        $this->assertEquals(md5('mark'), $user->getPassword());
        $this->assertEquals(array('primate'), $user->getRoles());
    }

    /**
     * Tests Auth->authUser() for result UNKNOWN_USER
     */
    public function testLogin_UNKNOWN_USER()
    {
        $this->setExpectedException('Q\Auth_LoginException', "Unknown user");
        $this->Auth->login('wolf', 'willem');
        
        $this->assertEquals(Auth::UNKNOWN_USER, $this->Auth->getStatus(), 'status');
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertNull($this->Auth->user()->getId(), 'id');
        $this->assertEquals('wolf', $this->Auth->user()->getUsername());
    }

    /**
     * Tests Auth->authUser() for result INCORRECT_PASSWORD
     */
    public function testLogin_INCORRECT_PASSWORD()
    {
        $this->setExpectedException('Q\Auth_LoginException', "Unknown user");
        $this->Auth->login('monkey', 'rudolf');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertEquals(Auth::INCORRECT_PASSWORD, $this->Auth->getStatus(), 'status');
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->getId(), 'id');
        $this->assertNull($this->Auth->user()->getUsername(), 'monkey');
    }

    /**
     * Tests Auth->authUser() for result INACTIVE_USER
     */
    public function testLogin_INACTIVE_USER()
    {
        $this->setExpectedException('Q\Auth_LoginException', "Inactive user");
        $this->Auth->login('baboon', 'ben');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertEquals(Auth::INACTIVE_USER, $this->Auth->getStatus(), 'status');
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(2, $this->Auth->user()->getId(), 'id');
    }

    /**
     * Tests Auth->authUser() for result PASSWORD_EXPIRED
     */
    public function testLogin_PASSWORD_EXPIRED()
    {
        $this->setExpectedException('Q\Auth_ExpiredException', "Password expired");
        $this->Auth->login('gorilla', 'george');
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertEquals(Auth::PASSWORD_EXPIRED, $this->Auth->getStatus(), 'status');
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(3, $this->Auth->user()->getId(), 'id');
    }

    
    /**
     * Tests Auth->logout()
     */
    public function testLogout()
    {
        $this->Auth->login('monkey', 'mark');
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertEquals(1, $this->Auth->user()->getId(), 'id');
        
        $this->Auth->logout();
        
        $this->assertFalse($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->getId(), 'id');
        
        $this->setExpectedException('Q\Auth_SessionException', 'No session');
        $this->Auth->authz();
    }

    
    /**
     * Tests Auth->authz() for result OK
     */
    public function testAuthz()
    {
        putenv('AUTH=' . escapeshellarg(Q\implode_assoc(";", array('uid'=>1, 'checksum'=>md5(1 . md5('mark') . 's3cret')))));
                
        $this->Auth->authz();
        $this->assertEquals(Auth::OK, $this->Auth->getStatus(), 'status code');
        $this->assertTrue($this->Auth->isLoggedIn());
        
        $user = $this->Auth->user();
        $this->assertType('Q\Auth_SimpleUser', $user);
        $this->assertEquals(1, $user->getId(), 'id');
        $this->assertEquals('Mark Monkey', $user->getFullname());
        $this->assertEquals('monkey', $user->getUsername());
        $this->assertEquals(md5('mark'), $user->getPassword());
        $this->assertEquals(array('primate'), $user->getRoles());
    }

    /**
     * Tests Auth->authz() with no session
     */
    public function testAuthz_NO_SESSION()
    {
        $this->setExpectedException('Q\Auth_SessionException');
        $this->Auth->authz();
        
        $this->assertEquals(Auth::UNKNOWN_USER, $this->Auth->getStatus(), 'status code');
        $this->assertNull($this->Auth->user(), 'user');
    }

    /**
     * Tests Auth->authz() for result UNKNOWN_USER
     */
    public function testAuthz_UNKNOWN_USER()
    {
        putenv('AUTH=' . escapeshellarg(Q\implode_assoc(";", array('uid'=>7, 'checksum'=>md5(7 . 's3cret')))));
        $this->Auth->checksumPassword = false;
        
        $this->setExpectedException('Q\Auth_SessionException', "Unknown user");
        $this->Auth->authz();
        
        $this->assertEquals(Auth::UNKNOWN_USER, $this->Auth->getStatus(), 'status code');
        $this->assertNull($this->Auth->user(), 'user');
    }

    /**
     * Tests Auth->authz() for result INACTIVE_USER
     */
    public function testAuthz_INACTIVE_USER()
    {
        putenv('AUTH=' . escapeshellarg(Q\implode_assoc(";", array('uid'=>2, 'checksum'=>md5(2 . md5('ben') . 's3cret')))));
        
        $this->setExpectedException('Q\Auth_SessionException', "Inactive user");
        $this->Auth->authz();
        
        $this->assertEquals(Auth::INACTIVE_USER, $this->Auth->getStatus(), 'status code');
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(2, $this->Auth->user()->getId(), 'id');
        $this->assertFalse((bool) $this->Auth->user()->active, 'active');
    }

    /**
     * Tests Auth->authz() for result PASSWORD_EXPIRED
     */
    public function testAuthz_PASSWORD_EXPIRED()
    {
        putenv('AUTH=' . escapeshellarg(Q\implode_assoc(";", array('uid'=>3, 'checksum'=>md5(3 . md5('george') . 's3cret')))));

        $this->Auth->authz();
        
        $this->assertEquals(Auth::PASSWORD_EXPIRED, $this->Auth->getStatus(), 'status code');
        $this->assertTrue($this->Auth->isLoggedIn());
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(3, $this->Auth->user()->getId(), 'id');
    }

    /**
     * Tests Auth->authz() with an incorrect hash
     */
    public function testAuthz_INVALID_SESSION()
    {
        putenv('AUTH=' . escapeshellarg(Q\implode_assoc(";", array('uid'=>1, 'checksum'=>md5(1 . md5('abc') . 's3cret')))));
        
        $this->setExpectedException('Q\Auth_SessionException', "Invalid session checksum");
        $this->Auth->authz();
        
        $this->assertEquals(Auth::INVALID_CHECKSUM, $this->Auth->getStatus(), 'status code');
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->getId(), 'id');
    }

    /**
     * Tests Auth->authz() with no hash
     */
    public function testAuthz_INVALID_SESSION_NoChecksum()
    {
        putenv('AUTH=' . escapeshellarg(Q\implode_assoc(";", array('uid'=>1))));
                
        $this->setExpectedException('Q\Auth_SessionException', "Invalid session checksum");
        $this->Auth->authz();
        
        $this->assertEquals(Auth::INVALID_CHECKSUM, $this->Auth->getStatus(), 'status code');
        $this->assertNotNull($this->Auth->user(), 'user');
        $this->assertEquals(1, $this->Auth->user()->getId(), 'id');
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
        
        $this->setExpectedException('Q\Auth_LoginException', "Host blocked");
        $this->Auth->login('monkey', 'mark');
    }

    
    /**
     * Tests Auth->authz()
     */
    public function testAuthz_Roles()
    {
        $this->Auth->login('monkey', 'mark');
        $this->Auth->authz('primate');
    }

    /**
     * Tests Auth->authz() where authorization fails
     */
    public function testAuthz_Roles_Fail()
    {
        $this->Auth->login('monkey', 'mark');
        
        $this->setExpectedException('Q\Authz_Exception', "User 'monkey' is not in role 'ape'.");
        $this->Auth->authz('ape');
    }

    /**
     * Tests Auth->authz() with multiple getRoles() where authorization fails
     */
    public function testAuthz_Roles_FailMultiple()
    {
        $this->Auth->login('monkey', 'mark');
        
        $this->setExpectedException('Q\Authz_Exception', "User 'monkey' is not in roles 'ape', 'pretty'.");
        $this->Auth->authz('primate', 'ape', 'pretty');
    }
}

