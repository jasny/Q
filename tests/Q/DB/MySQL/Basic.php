<?php
require_once __DIR__ . '/../../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/DB/MySQL.php';

/**
 * Testing basic features of Q\DB_MySQL.
 * This includes querying, quoting and basic parsing.
 */
class Test_DB_MySQL_Basic extends PHPUnit_Framework_TestCase
{
    /**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
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
			
			$this->conn->query("DROP TABLE IF EXISTS test");
			$this->conn->query("CREATE TABLE test (`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT, `key` VARCHAR(50), `title` VARCHAR(255), `status` ENUM('NEW', 'ACTIVE', 'PASSIVE') NOT NULL DEFAULT 'NEW', PRIMARY KEY(id))");
			$this->conn->query("INSERT INTO test VALUES (NULL, 'one', 'first row', 'ACTIVE'), (NULL, 'two', 'next row', 'ACTIVE'), (NULL, 'three', 'another row', 'PASSIVE')");
			
			$this->conn->query("DROP TABLE IF EXISTS child");
			$this->conn->query("CREATE TABLE child (`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT, `test_id` INTEGER UNSIGNED NOT NULL, `subtitle` VARCHAR(255), `status` ENUM('NEW', 'ACTIVE', 'PASSIVE') NOT NULL DEFAULT 'NEW', PRIMARY KEY(id))");
			$this->conn->query("INSERT INTO child VALUES (NULL, 1, 'child 1', 'ACTIVE'), (NULL, 1, 'child 2', 'ACTIVE'), (NULL, 2, 'child X', 'ACTIVE')");
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
		    $this->conn = Q\DB_MySQL::connect("localhost", "qtest");
		} catch (Exception $e) {
			$this->markTestSkipped("Failed to connect to database. Please create a user 'qtest' with all privileges to database 'qtest'. " . $e->getMessage());
		}
		
		$this->createTestSchema();
	}

	/**
	 * End test
	 */
	public function tearDown()
	{
		$this->dropTestSchema();
		$this->conn->close();
	}
	
    //--------

    /**
     * Compare DB_Result->getLink() is $this->conn
     */
    public function testResultGetLink()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
        $this->assertType('Q\DB_Result', $result);
    	$this->assertSame($this->conn, $result->getLink());
    }

    /**
     * Compare DB_Result->getStatement() with the statement used to create the result
     */
    public function testResultGetStatement()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	$this->assertEquals("SELECT * FROM test WHERE status='ACTIVE'", $result->getStatement());
    }
    
    
    //--------

    /**
     * Test query and fetching rows as ordered array
     */
    public function testResultFetchOrdered()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertNull($result->fetchOrdered());
    	
    	$result->resetPointer();
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $result->fetchOrdered(), "Fetch after reset pointer: ");
    }
    
    /**
     * Test query and fetching rows as associated array
     */
    public function testResultFetchAssoc()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	
    	$this->assertEquals(array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $result->fetchAssoc());
    	$this->assertEquals(array('id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE'), $result->fetchAssoc());
    	$this->assertNull($result->fetchAssoc());
    	
    	$result->resetPointer();
    	$this->assertEquals(array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $result->fetchAssoc(), "Fetch after reset pointer: ");
    }

    /**
     * Test query and fetching rows as stdClass object
     */
    public function testResultFetchObject()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	
    	$this->assertEquals((object)array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $result->fetchObject());
    	$this->assertEquals((object)array('id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE'), $result->fetchObject());
    	$this->assertNull($result->fetchObject());
    	
    	$result->resetPointer();
    	$this->assertEquals((object)array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $result->fetchObject(), "Fetch after reset pointer: ");
    }
    
    /**
     * Test query and fetching rows as full array
     */
    public function testResultFetchFullArray()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE', 'id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $result->fetchFullArray());
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE', 'id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE'), $result->fetchFullArray());
    	$this->assertNull($result->fetchFullArray());
    	
    	$result->resetPointer();
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE', 'id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $result->fetchFullArray(), "Fetch after reset pointer: ");
    }

    /**
     * Test query and fetching rows as assocated array grouped per table.
     * NOTE: Test is lame, because there is only 1 table.
     */
    public function testResultFetchPerTable()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	
    	$this->assertEquals(array('test'=>array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE')), $result->fetchPerTable());
    	$this->assertEquals(array('test'=>array('id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE')), $result->fetchPerTable());
    	$this->assertNull($result->fetchPerTable());
    	
    	$result->resetPointer();
    	$this->assertEquals(array('test'=>array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE')), $result->fetchPerTable(), "Fetch after reset pointer: ");
    }

    /**
     * Test query and fetching values
     */
    public function testResultFetchValue()
    {
    	$result = $this->conn->query("SELECT title FROM test WHERE status='ACTIVE'");
    	
    	$this->assertEquals('first row', $result->fetchValue());
    	$this->assertEquals('next row', $result->fetchValue());
    	$this->assertNull($result->fetchValue());
    	
    	$result->resetPointer();
    	$this->assertEquals('first row', $result->fetchValue(), "Fetch after reset pointer: ");
    }
    
    /**
     * Test query and fetching values for a specific column
     */
    public function testResultFetchValueColumn()
    {
       	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertEquals('first row', $result->fetchValue(2), "Fetch column 2: ");
    	$this->assertEquals('next row', $result->fetchValue('title'), "Fetch column 'title': ");
    	$this->assertEquals(3, $result->fetchValue(0), "Fetch column 0: ");
    	$this->assertNull($result->fetchValue(2));

    	$result->resetPointer();
    	$this->assertEquals('first row', $result->fetchValue(2), "Fetch column 2 after reset pointer: ");
    }

    /**
     * Test query and fetching rows in different ways
     */
    public function testResultFetchRow()
    {
    	$result = $this->conn->query("SELECT * FROM test");
    	
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $result->fetchRow(Q\DB::FETCH_ORDERED));
    	$this->assertEquals(array('id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE'), $result->fetchAssoc(Q\DB::FETCH_ASSOC));
    	$this->assertEquals(array(3, 'three', 'another row', 'PASSIVE', 'id'=>3, 'key'=>'three', 'title'=>'another row', 'status'=>'PASSIVE'), $result->fetchRow(Q\DB::FETCH_FULLARRAY));
    	$this->assertNull($result->fetchRow(Q\DB::FETCH_ORDERED));
    	
    	$result->resetPointer();
    	$this->assertEquals(array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $result->fetchRow(Q\DB::FETCH_ASSOC));
    	$this->assertEquals(2, $result->fetchRow(Q\DB::FETCH_VALUE), "Fetch after reset pointer: ");
    }

	//--------

    /**
     * Test parsing a string into the query statement.
     * More parsing tests are done in QuerySplitter test case
     */
    public function testParse()
    {
        $this->assertEquals('SELECT * FROM test WHERE status="ACTIVE"', $this->conn->parse('SELECT * FROM test WHERE status=?', array('ACTIVE')));
    }

    /**
     * Test parsing a named argument into the query statement.
     */
    public function testParse_Named()
    {
        $this->assertEquals('SELECT * FROM test WHERE status="ACTIVE"', $this->conn->parse('SELECT * FROM test WHERE status=:status', array('status'=>'ACTIVE')));
    }
        
    /**
     * Test parsing a value into the query statement through function query()
     */
    public function testQueryParse()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status=?", 'ACTIVE');
		$this->assertType('Q\DB_Result', $result);
		
		$this->assertEquals('SELECT * FROM test WHERE status="ACTIVE"', $result->getStatement());
    	
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertNull($result->fetchOrdered());
    }
        
    /**
     * Test parsing a value into the query statement through function query()
     */
    public function testQueryParse_Named()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status=:status", Q\DB::arg('status', 'ACTIVE'));
		$this->assertType('Q\DB_Result', $result);
		
		$this->assertEquals('SELECT * FROM test WHERE status="ACTIVE"', $result->getStatement());
    	
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertNull($result->fetchOrdered());
    }
    
    /**
     * Test preparing and executing a query statement
     */
    public function testPrepare()
    {
    	$qs = $this->conn->prepare('SELECT * FROM test WHERE status="ACTIVE"');
    	$this->assertType('Q\DB_Statement', $qs);
    	
    	$result = $qs->execute();
    	$this->assertType('Q\DB_Result', $result);
    	
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertNull($result->fetchOrdered());
    	
    	$result = $this->conn->query($qs);
		$this->assertType('Q\DB_Result', $result);
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $result->fetchOrdered(), 'Using conn->query(prepared statement)');
    }
    
    /**
     * Test preparing, parsing a value into the query statement and executing it 
     */
    public function testPrepareParse()
    {
    	$qs = $this->conn->prepare("SELECT * FROM test WHERE status=?");
    	$this->assertType('Q\DB_Statement', $qs);
    	
    	$result = $qs->execute('ACTIVE');
		$this->assertType('Q\DB_Result', $result);
		$this->assertEquals('SELECT * FROM test WHERE status="ACTIVE"', $result->getStatement());
		
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertNull($result->fetchOrdered());
    	
    	$result = $this->conn->query($qs, 'PASSIVE');
		$this->assertType('Q\DB_Result', $result);
    	$this->assertEquals(array(3, 'three', 'another row', 'PASSIVE'), $result->fetchOrdered(), 'Using conn->query(prepared statement)');
    }

    /**
     * Test preparing a query statement and loading a row
     */
    public function testPrepareLoad_Ordered()
    {
    	$qs = $this->conn->prepare('SELECT * FROM test');
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE'), $qs->load(2, Q\DB::FETCH_ORDERED));
    }
    
    /**
     * Test preparing a query statement and loading a row using a where statement
     */
    public function testPrepareLoad_FullArrayWhere()
    {
    	$qs = $this->conn->prepare('SELECT * FROM test');
        $this->assertEquals(array(3, 'three', 'another row', 'PASSIVE', 'id'=>3, 'key'=>'three', 'title'=>'another row', 'status'=>'PASSIVE'), $qs->load(array('`key`'=>'three'), Q\DB::FETCH_FULLARRAY));
    }
    
    
	//--------

    /**
     * Test query and get all values as ordered array
     */
    public function testResultGetAll_Ordered()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	$this->assertEquals(array(array(1, 'one', 'first row', 'ACTIVE'), array(2, 'two', 'next row', 'ACTIVE')), $result->getAll(Q\DB::FETCH_ORDERED));
    }
	
    /**
     * Test query and get all values as associated array
     */
    public function testResultGetAll_Assoc()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	$this->assertEquals(array(array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), array('id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE')), $result->getAll(Q\DB::FETCH_ASSOC));
    }

    /**
     * Test query and get all values as associated array
     */
    public function testResultGetAll_Object()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	$this->assertEquals(array((object)array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), (object)array('id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE')), $result->getAll(Q\DB::FETCH_OBJECT));
    }
    
    /**
     * Test query and get all values as full array
     */
    public function testResultGetAll_FullArray()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	$this->assertEquals(array(array(1, 'one', 'first row', 'ACTIVE', 'id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), array(2, 'two', 'next row', 'ACTIVE', 'id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE')), $result->getAll(Q\DB::FETCH_FULLARRAY));
    }
    
    /**
     * Test query and get all values as array
     */
    public function testResultGetAll_Values()
    {
    	$result = $this->conn->query("SELECT title FROM test WHERE status='ACTIVE'");
    	$this->assertEquals(array('first row', 'next row'), $result->getAll(Q\DB::FETCH_VALUE));
    }
    
    /**
     * Test query and get column
     */
    public function testResultGetColumn()
    {
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	$this->assertEquals(array('first row', 'next row'), $result->getColumn(2), "Get column 2");
    	$this->assertEquals(array('first row', 'next row'), $result->getColumn('title'), "Get column 'title'");
    	$this->assertEquals(array(1, 2), $result->getColumn(0), "Get column 0");
    }

	//--------
    
    /**
     * Test Q\DB_MySQL_Result->seekRows() searching on column name
     */
    public function testResultSeekRows_ColumnName()
    {
    	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertEquals(array(array(1, 'one', 'first row', 'ACTIVE'), array(2, 'two', 'next row', 'ACTIVE')), $result->seekRows('status', 'ACTIVE', Q\DB::FETCH_ORDERED));
    	$this->assertEquals(array(array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), array('id'=>2, 'key'=>'two', 'title'=>'next row', 'status'=>'ACTIVE')), $result->seekRows('status', 'ACTIVE', Q\DB::FETCH_ASSOC));
    	$this->assertEquals(array(1, 2), $result->seekRows('status', 'ACTIVE', Q\DB::FETCH_VALUE));
    }

    /**
     * Test Q\DB_MySQL_Result->seekRows() fetching assoc row
     */
    public function testResultSeekRows_Assoc()
    {
    	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertEquals(array(array(1, 'one', 'first row', 'ACTIVE'), array(2, 'two', 'next row', 'ACTIVE')), $result->seekRows(3, 'ACTIVE', Q\DB::FETCH_ORDERED), "Ordered - Column 3 == 'ACTIVE'");
    }

    /**
     * Test Q\DB_MySQL_Result->seekRows() fetching values
     */
    public function testResultSeekRows()
    {
    	$result = $this->conn->query("SELECT * FROM test");
    }
    
	//--------

    /**
     * Test query and get number of rows
     */
    public function testResultNumRows()
    {
    	$this->assertEquals(3, $this->conn->query('SELECT * FROM test')->numRows());
    	$this->assertEquals(2, $this->conn->query('SELECT * FROM test WHERE status="ACTIVE"')->numRows(), "Active rows");
    }

    /**
     * Test query and get number of fields
     */
    public function testResultNumFields()
    {
    	$this->assertEquals(4, $this->conn->query('SELECT * FROM test')->numFields());
    	$this->assertEquals(2, $this->conn->query('SELECT id, title FROM test')->numFields(), 'SELECT id, title FROM test');
    }
    
    /**
     * Test query and get number of tables
     */
    public function testResultNumTables()
    {
    	$this->assertEquals(1, $this->conn->query('SELECT * FROM test')->numTables());
    	$this->assertEquals(2, $this->conn->query('SELECT * FROM test INNER JOIN child ON test.id = child.test_id')->numTables(), "SELECT FROM test, child");
    }
    
    /**
     * Test query and get the fieldnames
     */
    public function testResultGetFieldNames()
    {
    	$this->assertEquals(array('id', 'key', 'title', 'status'), $this->conn->query('SELECT * FROM test')->getFieldNames());
    	$this->assertEquals(array('id', 'title'), $this->conn->query('SELECT id, title FROM test')->getFieldNames(), "Only id, title");
    	$this->assertEquals(array('id', 'test_id', 'title', 'subtitle'), $this->conn->query('SELECT child.id, child.test_id, title, subtitle FROM test INNER JOIN child ON test.id = child.test_id')->getFieldNames());
    }
    
    /**
     * Test query and get the tablenames
     */
    public function testResultGetTableNames()
    {
        $this->assertEquals(array('test'), $this->conn->query('SELECT * FROM test')->getTableNames());
    	$this->assertEquals(array('test', 'child'), $this->conn->query('SELECT * FROM test INNER JOIN child ON test.id = child.test_id')->getTableNames(), "test, child");
    }
    
	//--------

    /**
     * Test lookup value
     */
    public function testLookupValue()
    {
    	$this->assertEquals('first row', $this->conn->lookupValue("test", "title", 1), 'Using id=1');
    	$this->assertEquals('another row', $this->conn->lookupValue("test", "title", array('key'=>'three')), 'Using id=array("key"=>"three")');
    	$this->assertNull($this->conn->lookupValue("test", "title", 99), 'Using id=99');
    }

    /**
     * Test the constraint exception when using conn->lookupValue() 
     */
    public function testLoookValue_Contraint()
    {
        $this->setExpectedException('Q\DB_Constraint_Exception');
        $this->conn->lookupValue("test", "title", array('status'=>'ACTIVE'));
    }
        
    /**
     * Test counting rows
     */
    public function testCountAllRows()
    {
    	$this->assertEquals(3, $this->conn->countRows('test'));
    }
    
    /**
     * Test counting rows where status is active
     */
    public function testCountActiveRows()
    {
        $this->assertEquals(2, $this->conn->countRows('test', array('status'=>'ACTIVE')));
    }
    	
    /**
     * Test counting rows with where on id
     */
    public function testCountId()
    {
        $this->assertEquals(1, $this->conn->countRows('test', 1));
    }
    
    /**
     * Test counting rows with where on id with a non-existing value
     */
    public function testCountNonExistingId()
    {
        $this->assertEquals(0, $this->conn->countRows('test', 99));
    }
        
    /**
     * Test selecting a row using conn->load()
     */
    public function testLoad()
    {
    	$this->assertEquals(array('id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $this->conn->load("test", 1, Q\DB::FETCH_ASSOC), 'Using id=1');
    	$this->assertEquals(array('id'=>3, 'key'=>'three', 'title'=>'another row', 'status'=>'PASSIVE'), $this->conn->load("test", array('key'=>'three'), Q\DB::FETCH_ASSOC), 'Using id=array("key"=>"three")');

    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE'), $this->conn->load("test", 1, Q\DB::FETCH_ORDERED), 'Using id=1');
    	$this->assertEquals(array(1, 'one', 'first row', 'ACTIVE', 'id'=>1, 'key'=>'one', 'title'=>'first row', 'status'=>'ACTIVE'), $this->conn->load("test", 1, Q\DB::FETCH_FULLARRAY), 'Using id=1');
    	
    	$this->assertNull($this->conn->load("test", 99), 'Using id=99');
    }

    /**
     * Test the constraint exception when selecting multiple rows using conn->load() 
     */
    public function testLoad_Contraint()
    {
    	$this->setExpectedException('Q\DB_Constraint_Exception');
		$this->conn->lookupValue("test", "title", array('status'=>'ACTIVE'));
    }

    /**
     * Test inserting single rows using conn->store()
     */
    public function testStore_Insert()
    {
    	$id1 = $this->conn->store('test', array(null, 'new1', 'new row ONE', 'NEW'));
    	$id2 = $this->conn->store('test', array('key'=>'new2', 'title'=>'new row TWO'));
    	
    	$result = $this->conn->query('SELECT * FROM test where status="NEW"');
    	$this->assertEquals(array($id1, 'new1', 'new row ONE', 'NEW'), $result->fetchOrdered());
    	$this->assertEquals(array($id2, 'new2', 'new row TWO', 'NEW'), $result->fetchOrdered());
	}

    /**
     * Test inserting multiple rows using conn->store()
     */
    public function testStore_InsertMultiple()
    {
    	$this->conn->store('test', array('id', 'key', 'title', 'status'), array(null, 'new1', 'new row ONE', 'NEW'), array(null, 'new2', 'new row TWO', 'NEW'));
    	$this->conn->store('test', null, array(null, 'new3', 'new row THREE', 'NEW'), array(null, 'new4', 'new row FOUR', 'NEW'));
    	
    	$result = $this->conn->query('SELECT * FROM test where status="NEW"');
    	$this->assertEquals(array(4, 'new1', 'new row ONE', 'NEW'), $result->fetchOrdered());
    	$this->assertEquals(array(5, 'new2', 'new row TWO', 'NEW'), $result->fetchOrdered());
    	$this->assertEquals(array(6, 'new3', 'new row THREE', 'NEW'), $result->fetchOrdered());
    	$this->assertEquals(array(7, 'new4', 'new row FOUR', 'NEW'), $result->fetchOrdered());
	}

    /**
     * Test inserting multiple rows using conn->store() with Q\DB::arg('values', rows)
     */
    public function testStore_InsertMultipleArgs()
    {
    	$this->conn->store('test', array('id', 'key', 'title', 'status'), Q\DB::arg('values', array(array(null, 'new1', 'new row ONE', 'NEW'), array(null, 'new2', 'new row TWO', 'NEW'))));
    	
    	$result = $this->conn->query('SELECT * FROM test where status="NEW"');
    	$this->assertEquals(array(4, 'new1', 'new row ONE', 'NEW'), $result->fetchOrdered());
    	$this->assertEquals(array(5, 'new2', 'new row TWO', 'NEW'), $result->fetchOrdered());
	}

    /**
     * Test updating single rows using conn->update()
     */
    public function testStore_Update()
    {
    	$this->conn->store('test', array(1, 'one', 'updated row ONE', 'PASSIVE'));
    	$this->conn->store('test', array('id'=>2, 'title'=>'updated row TWO'));
    	
    	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertEquals(array(1, 'one', 'updated row ONE', 'PASSIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(2, 'two', 'updated row TWO', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(3, 'three', 'another row', 'PASSIVE'), $result->fetchOrdered());
    }

    /**
     * Test updating/inserting multiple rows using conn->update()
     */
    public function testStore_UpdateMultiple()
    {
    	$this->conn->store('test', array('id', 'key', 'title', 'status'), array(1, 'one', 'updated row ONE', 'PASSIVE'), array(2, 'two', 'updated row TWO', 'ACTIVE'), array(null, 'new1', 'new row ONE', 'NEW'));
    	
    	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertEquals(array(1, 'one', 'updated row ONE', 'PASSIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(2, 'two', 'updated row TWO', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(3, 'three', 'another row', 'PASSIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(4, 'new1', 'new row ONE', 'NEW'), $result->fetchOrdered());
    }
    
    /**
     * Test updating/inserting multiple rows using conn->update()
     */
    public function testStore_UpdateMultipleAgain()
    {
    	$this->conn->store('test', array('id', 'key', 'title', 'status'), array(1, 'one', 'updated row ONE', 'PASSIVE'), array(2, 'twp', 'updated row TWO', 'ACTIVE'), array(null, 'new1', 'new row ONE', 'NEW'));
    	$this->conn->store('test', null, array(2, 'two', 'updated again TWO', 'ACTIVE'), array(null, 'new2', 'new row TWO', 'NEW'));
    	
    	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertEquals(array(1, 'one', 'updated row ONE', 'PASSIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(2, 'two', 'updated again TWO', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(3, 'three', 'another row', 'PASSIVE'), $result->fetchOrdered());
    	$this->assertEquals(array(4, 'new1', 'new row ONE', 'NEW'), $result->fetchOrdered());
    	$this->assertEquals(array(5, 'new2', 'new row TWO', 'NEW'), $result->fetchOrdered());
    }
    
    /**
     * Test deleting single row using conn->delete()
     */
    public function testDelete()
    {
    	$this->conn->delete('test', 1);
    	
    	$result = $this->conn->query("SELECT * FROM test WHERE status='ACTIVE'");
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertNull($result->fetchOrdered());
    }
    
    /**
     * Test deleting single row (status is passive) using conn->delete()
     */
    public function testDelete_Again()
    {
    	$this->conn->delete('test', 1);
        $this->conn->delete('test', array('status'=>'PASSIVE'));
    	
    	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertEquals(array(2, 'two', 'next row', 'ACTIVE'), $result->fetchOrdered());
    	$this->assertNull($result->fetchOrdered());
    }
    
    /**
     * Test deleting multiple rows using conn->delete()
     */
    public function testDelete_Multi()
    {
    	$this->conn->delete('test', array('status'=>'ACTIVE'), Q\DB::MULTIPLE_ROWS);
    	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertEquals(array(3, 'three', 'another row', 'PASSIVE'), $result->fetchOrdered());
    	$this->assertNull($result->fetchOrdered());
    }

    /**
     * Test deleting multiple rows using conn->delete(), should hit constraint
     */
    public function testDelete_MultiConstraint()
    {
        $this->setExpectedException('Q\DB_Constraint_Exception');
        $this->conn->delete('test', array('status'=>'ACTIVE'), Q\DB::SINGLE_ROW);
    }	
    
    /**
     * Test truncating a table using conn->delete()
     */
    public function testTruncate()
    {
    	$this->conn->delete('test', NULL, Q\DB::ALL_ROWS);

    	$result = $this->conn->query("SELECT * FROM test");
    	$this->assertNull($result->fetchOrdered());
    }
    
}

?>