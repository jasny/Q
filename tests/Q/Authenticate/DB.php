<?php
use Q\Authenticate;

require_once 'Test/Authenticate/Main.php';
require_once 'Q/Authenticate/DB.php';

/**
 * Authenticate_Manual test case.
 */
class Test_Authenticate_DB extends Test_Authenticate_Main 
{
	/**
	 * DB connection object
	 *
	 * @var Q\DB
	 */
	protected $conn;
	
    /**
     * Create database for testing
     */
	function createTestSchema()
	{
		try {
			$this->conn->query("CREATE database IF NOT EXISTS `qtest`");
			$this->conn->query("USE `qtest`");
			
			$this->conn->query("DROP TABLE IF EXISTS `user`");
			$this->conn->query("CREATE TABLE `user` (`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT, `fullname` VARCHAR(255), `username` VARCHAR(255), `password` VARCHAR(255), `status` ENUM('NEW', 'ACTIVE', 'PASSIVE') NOT NULL DEFAULT 'NEW', PRIMARY KEY(`id`), KEY(`username`))");
			$this->conn->query("INSERT INTO `user` VALUES (NULL, 'Mark Monkey', 'monkey', MD5('mark'), 'ACTIVE'), (NULL, 'Ben Baboon', 'baboon', MD5('ben'), 'PASSIVE'), (NULL, 'George Gorilla', 'gorilla', MD5('george'), 'NEW')");
			
			$this->conn->query("DROP TABLE IF EXISTS `user_group`");
			$this->conn->query("CREATE TABLE `user_group` (`user_id` INTEGER UNSIGNED NOT NULL, `group` VARCHAR(255), PRIMARY KEY(`user_id`, `group`))");
			$this->conn->query("INSERT INTO `user_group` VALUES (1, 'primate'), (2, 'primate'), (2, 'ape'), (3, 'primate'), (3, 'ape')");
		} catch (Exception $e) {
			$this->markTestSkipped('Failed to create a test table. ' . $e->getMessage());
		}
	}
	
	/**
	 * Drop test database
	 */
	function dropTestSchema()
	{
		$this->conn->query("DROP database IF EXISTS `qtest`");
	}
	
    /**
	 * Init test
	 */
	public function setUp()
	{
		try {
		    $this->conn = Q\DB::connect('mysql', "localhost", "qtest");
		} catch (Exception $e) {
			$this->markTestSkipped("Failed to connect to database. Please create a user 'qtest' with all privileges to database 'qtest'. " . $e->getMessage());
		}
		
		$this->createTestSchema();
		
        $this->Authenticate = Authenticate::with('db');
        $this->Authenticate->query = $this->conn->prepare("SELECT id, fullname, username, '%', password, VALUES (SELECT `group` FROM `user_group` ORDER BY `group` CASCADE ON `user_group`.`user_id`=`user`.`id`) AS `groups`, `status` != 'PASSIVE' AS `active`, IF(`status` = 'NEW', 1, NULL) AS `expire` FROM `user`");
        $this->Authenticate->onLoginQuery = $this->conn->prepare("SET @auth_uid = :id, @auth_username = :username, @auth_fullname = :fullname");
        $this->Authenticate->onLogoutQuery = $this->conn->prepare("SET @auth_uid = NULL, @auth_username = NULL, @auth_fullname = NULL");
        
        parent::setUp();
	}

	/**
	 * End test
	 */
	public function tearDown()
	{
	    parent::tearDown();
		$this->dropTestSchema();
		$this->conn->close();
	}
	
	/**
	 * Test Authenticator_DB::onLogin()
	 */
	public function testOnLogin()
	{
	    $this->Authenticate->login('monkey', 'mark');
	    list($id, $username, $fullname) = $this->conn->query("SELECT @auth_uid, @auth_username, @auth_fullname")->fetchRow();

	    $this->assertEquals(1, (int)$id, 'id');
	    $this->assertEquals('monkey', $username, 'username');
	    $this->assertEquals('Mark Monkey', $fullname, 'fullname');
	}


	/**
	 * Test Authenticator_DB::onLogout()
	 */
	public function testOnLogout()
	{
	    $this->Authenticate->loginRequired = false;
	    $this->Authenticate->login('monkey', 'mark');
	    $this->Authenticate->logout();

	    list($id, $username, $fullname) = $this->conn->query("SELECT @auth_uid, @auth_username, @auth_fullname")->fetchRow();

	    $this->assertNull($id, 'id');
	    $this->assertNull($username, 'username');
	    $this->assertNull($fullname, 'fullname');
	}
}

