<?php
use Q\DB, Q\DB_MySQL, Q\DB_SQLStatement;

require_once __DIR__ . '/../../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/DB/MySQL.php';

/**
 * Tests for table properties, DB_Table, DB_Field and DB_Record
 * 
 * @todo Put in test for DB_Table->getInfo()
 */
class Test_DB_MySQL_Advanced extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    
    //--------
    
	/**
	 * DB connection object
	 *
	 * @var DB
	 */
	public $conn;

	
    /**
     * Create database for testing
     */
	public function createTestSchema()
	{
		try {
			$this->conn->query("CREATE database IF NOT EXISTS `qtest`");
			$this->conn->query("USE `qtest`");
			
			$this->conn->query("DROP TABLE IF EXISTS test");
			$this->conn->query("CREATE TABLE test (`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT, `key` VARCHAR(50), `title` VARCHAR(255), `status` ENUM('NEW', 'ACTIVE', 'PASSIVE') NOT NULL DEFAULT 'NEW', PRIMARY KEY(id))");
			$this->conn->query("INSERT INTO test VALUES (NULL, 'one', 'first row', 'ACTIVE'), (NULL, 'two', 'next row', 'ACTIVE'), (NULL, 'three', 'another row', 'PASSIVE')");
			
			$this->conn->query("DROP TABLE IF EXISTS `child`");
			$this->conn->query("CREATE TABLE `child` (`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT, `test_id` INTEGER UNSIGNED NOT NULL, `subtitle` VARCHAR(255), `status` ENUM('NEW', 'ACTIVE', 'PASSIVE') NOT NULL DEFAULT 'NEW', PRIMARY KEY(id))");
			$this->conn->query("INSERT INTO `child` VALUES (NULL, 1, 'child 1', 'ACTIVE'), (NULL, 1, 'child 2', 'ACTIVE'), (NULL, 2, 'child X', 'ACTIVE')");

			$this->conn->query("DROP TABLE IF EXISTS `junction`");
			$this->conn->query("CREATE TABLE `junction` (`test_id` INTEGER UNSIGNED NOT NULL, `child_id` INTEGER UNSIGNED NOT NULL, PRIMARY KEY(test_id, child_id))");
			$this->conn->query("INSERT INTO `junction` VALUES (1, 1), (1, 2), (2, 1)");
		} catch (Exception $e) {
			$this->markTestSkipped('Failed to create a test table. ' . $e->getMessage());
		}
	}
	
	/**
	 * Drop test database
	 */
	public function dropTestSchema()
	{
		$this->conn->query("DROP database IF EXISTS `qtest`");
	}
	
    /**
	 * Init test
	 */
	public function setUp()
	{
	    try {
		    $this->conn = DB_MySQL::connect("localhost", "qtest", null, null, null, null, array('config'=>array('driver'=>'yaml', 'path'=>__DIR__ . '/settings')));
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
		
    /**
     * Helper function to remove spaces from a query.
     */
    static function cleanQuery($sql)
    {
		return trim(preg_replace('/(?:\s+(\s|\)|\,)|(\()\s+)/', '\1\2', $sql));
    }
    
	
	//--------

	/**
	 * Test getting a table definition
	 */
	public function testTableDefinition()
	{
	    $td = $this->conn->table('test');
		
	    $this->assertType('Q\DB_Table', $td);
		$this->assertEquals('test', $td->getName());
		$this->assertEquals('test', $td->getTablename());
		
		$this->assertSame($this->conn, $td->getLink());
	}

	/**
	 * Test getting a table definition
	 */
	public function testTableDefinition_Alias()
	{
	    $td = $this->conn->table('a_test');
		
		$this->assertEquals('a_test', $td->getName());
		$this->assertEquals('test', $td->getTablename());
		$this->assertEquals('test_record', $td->getRecordType());
		
		$this->assertSame($this->conn, $td->getLink());
	}
	
	/**
	 * Test getting all properties of table 'test'
	 */
	public function testTableGetProperties()
	{
		$props = $this->conn->table('test')->getProperties();
		if (empty($props)) $this->fail("No properties returned");

		$this->assertEquals("test", $props['#table']['name'], '#table.name ');
		$this->assertEquals("test", $props['#table']['table'], '#table.table ');
		$this->assertEquals("Test", $props['#table']['description'], '#table.description ');
		
		$this->assertArrayHasKey('id', $props);
		$this->assertArrayHasKey('title', $props);
		$this->assertArrayHasKey('status', $props);
		
		$this->assertEquals("test", $props['id']['table'], 'id.table ');
		$this->assertEquals("test", $props['id']['table_def'], 'id.table_def ');
		$this->assertTrue($props['id']['is_primary'], 'id.is_primary ');
		$this->assertFalse(!empty($props['id']['required']), 'id.required ');
		$this->assertTrue(!empty($props['id']['numeric']), 'id.numeric ');
		
		$this->assertEquals("title", $props['title']['name'], 'title.name ');
		$this->assertEquals("Title", $props['title']['description'], 'title.description ');
		$this->assertEquals("test", $props['title']['table'], 'title.table ');
		$this->assertEquals("test", $props['title']['table_def'], 'title.table_def ');
		$this->assertContains($props['title']['type'], array('string', 'varchar'), 'title.type ');
		$this->assertFalse(!empty($props['title']['required']), 'title.required ');
		$this->assertFalse(!empty($props['title']['numeric']), 'title.numeric ');

		$this->assertTrue(!empty($props['status']['required']), 'status.required ');
		
		$this->assertArrayHasKey('#role:id', $props);
		$this->assertArrayHasKey('#role:description', $props);
		$this->assertArrayHasKey('#role:active', $props);
		
		if ($props['#role:id'] !== $props['id']) $this->fail('id !== #role:id ');
		$this->assertEquals("id", $props['#role:id']['name'], '#role:id.name ');
		$this->assertContains("id", $props['#role:id']['role'], '#role:id.role ');

		$this->assertSame($props['title'], $props['#role:description']);
		$this->assertContains("description", $props['#role:description']['role']);
		
		$this->assertEquals("test.status != 'PASSIVE'", $props['#role:active']['name'], '#role:active.name ');
		$this->assertContains("active", $props['#role:active']['role']);
	}
	
	/**
	 * Test getting all properties of aliased table 'a_test'
	 */
	public function testTableGetProperties_Aliased()
	{
		$props = $this->conn->table('a_test')->getProperties();
		if (empty($props)) $this->fail("No properties returned");

		$this->assertEquals("a_test", $props['#table']['name'], '#table.name ');
		$this->assertEquals("test", $props['#table']['table'], '#table.table ');
		$this->assertEquals("Alias of PHPUnit test table", $props['#table']['description'], '#table.description ');
		
		$this->assertArrayHasKey('id', $props);
		$this->assertArrayHasKey('title', $props);

		$this->assertEquals("test", $props['id']['table'], 'id.table ');
		$this->assertEquals("a_test", $props['id']['table_def'], 'id.table_def ');
		$this->assertEquals('int', $props['id']['type'], 'id.type ');
		$this->assertTrue($props['id']['is_primary'], 'id.is_primary ');
		$this->assertFalse(!empty($props['id']['required']), 'id.required ');
		$this->assertTrue(!empty($props['id']['numeric']), 'id.numeric ');
		
		$this->assertEquals("title", $props['title']['name'], 'title.name ');
		$this->assertEquals("Name", $props['title']['description'], 'title.description ');
		$this->assertEquals("test", $props['title']['table'], 'title.table ');
		$this->assertEquals("a_test", $props['title']['table_def'], 'title.table_def ');
		$this->assertEquals("string", $props['title']['type'], 'title.type ');
		$this->assertEquals("alphanumeric", $props['title']['datatype'], 'title.datatype ');
		$this->assertFalse(!empty($props['title']['required']), 'title.required ');
		$this->assertFalse(!empty($props['title']['numeric']), 'title.numeric ');

		$this->assertArrayHasKey('#role:id', $props);
		$this->assertArrayNotHasKey('#role:active', $props);
		
		if ($props['#role:id'] !== $props['id']) $this->fail('id !== #role:id ');
	}
	
	/**
	 * Test getting single properties of table 'a_test'
	 */
	public function testTableGetTableProperty()
	{
		$this->assertEquals("test", $this->conn->table('a_test')->getTableProperty('table'));
		$this->assertEquals("Alias of PHPUnit test table", $this->conn->table('a_test')->getTableProperty('description'));
	}
    
	/**
	 * Test getting all properties of field test.title
	 */
	public function testTableGetFieldProperties()
	{
		$props = $this->conn->table('test')->getFieldProperties('title');
		if (empty($props)) $this->fail("No properties for 'test.title' returned");

		$this->assertEquals("title", $props['name'], 'title.name ');
		$this->assertEquals("Title", $props['description'], 'title.description ');
		$this->assertEquals("test", $props['table'], 'title.table ');
		$this->assertEquals("string", $props['type'], 'title.type ');
	}

	/**
	 * Test getting single properties of field test.title
	 */
	public function testTableGetFieldProperty()
	{
		$this->assertEquals("Title", $this->conn->table('test')->getFieldProperty('title', 'description'));
	}

	/**
	 * Test getting single properties of mapped fields
	 */
	public function testTableGetFieldProperty_Role()
	{
		$this->assertEquals("title", $this->conn->table('test')->getFieldProperty('#role:description', 'name'), "test.#role:description");
		$this->assertEquals("int", $this->conn->table('test')->getFieldProperty('#role:id', 'type'), "test.#role:id");
		$this->assertEquals("key", $this->conn->table('test')->getFieldProperty('#role:index', 'name'), "test.#role:index");
		$this->assertEquals("Logical status", $this->conn->table('test')->getFieldProperty('#role:active', 'description'), "test.#role:active");
	}
	
	/**
	 * Test getting single properties of field mapped with role parentkey
	 */
	public function testTableGetFieldProperty_Parentkey()
	{
		$this->assertEquals("test_id", $this->conn->table('child')->getFieldProperty('#role:parentkey', 'name'), "child.#role:parentkey");
	}
	
	/**
	 * Test getting single properties of mapped fields using an aliased table
	 */
	public function testTableGetFieldProperty_Role_Aliased()
	{
		$this->assertEquals("title", $this->conn->table('a_test')->getFieldProperty('#role:description', 'name'), "a_test.#role:description");
		$this->assertEquals("int", $this->conn->table('a_test')->getFieldProperty('#role:id', 'type'), "a_test.#role:id");
	}


	/**
	 * Test getting single properties of table 'a_test'
	 */
	public function testTableGetProperties_Junction()
	{
		$this->assertEquals("junction", $this->conn->table('junction')->getTableProperty('role'), '#table.role');
		$this->assertFalse($this->conn->table('junction')->hasField('#role:id'), '#role:id');
		$this->assertEquals('test_id', $this->conn->table('junction')->getFieldProperty('#role:parentkey', 'name'), '#role:parentkey.name');
		
		$this->assertEquals('test', $this->conn->table('junction')->getFieldProperty('#role:parentkey', 'foreign_table'), "#role:parentkey.foreign_table");
		$this->assertEquals("test", $this->conn->table('junction')->getTableProperty('parent'), "#table.parent");
	}	
	
	
    //--------

    
	/**
	 * Test getting a field definition
	 */
	public function testFieldDefinition()
	{
		$td = $this->conn->table('test');
		$field = $td->getField('title');
		
	    $this->assertType('Q\DB_Field', $field);
	    $this->assertEquals('title', $field->getName());
		$this->assertEquals('Title', $field['description']);
		$this->assertEquals('test', $field['table']);
	}

	/**
	 * Test getting a field definition
	 */
	public function testTableGetField_ByPosition()
	{
		$td = $this->conn->table('test');
		$field = $td->getField(2);
		
		$this->assertType('Q\DB_Field', $field);
		$this->assertEquals('title', $field->getName());
	}	
	/**
	 * Test getting a field definition
	 */
	public function testTableGetFields()
	{
		$td = $this->conn->table('test');
		$fields = $td->getFields();
		
		$this->assertType('array', $fields);
		$this->assertEquals(4, count($fields), "Field count");
	
		$this->assertEquals('id', $fields[0]->getName());
		$this->assertEquals('key', $fields[1]->getName());
		$this->assertEquals('title', $fields[2]->getName());
		$this->assertEquals('status', $fields[3]->getName());
	}
		
	/**
	 * Test getting a field definition using #role
	 */
	public function testFieldDefinition_Role()
	{
		$td = $this->conn->table('test');
		
	    $field = $td->getField('#role:description');
		$this->assertType('Q\DB_Field', $field);
		$this->assertEquals('title', $field->getName());
		$this->assertEquals('Title', $field['description']);
		$this->assertEquals('test', $field['table']);
		
		$field = $td->getField('#role:index');
		$this->assertEquals('Reference', $field['description']);
	}
	
	/**
	 * Test getting a field definition using #role:parentkey
	 */
    public function testFieldDefinition_Parentkey()
	{	
		$this->assertNotNull($this->conn->table('child')->getFieldProperties('#role:parentkey'), "Didn't find field '#role:parentkey'");
		$field = $this->conn->table('child')->getField('#role:parentkey');
		$this->assertEquals('test_id', $field['name']);
	}
    
	/**
	 * Test getting a field definition for an expression
	 */
	public function testFieldDefinition_RoleExpression()
	{
        $this->setExpectedException('Q\Exception');
	    $field = $this->conn->table('test')->getField('#role:active');
	}
	
    //--------
    

	public function testResultBaseTable()
	{
		$qs = $this->conn->table("test")->getStatement();

		$result = $qs->execute();
		if ($result->getBaseTable() !== $this->conn->table("test")) $this->fail('Base table of result is not set correctly.');

		$result = $this->conn->query($qs);
		if ($result->getBaseTable() !== $this->conn->table("test")) $this->fail('Base table of result is not set correctly, when using conn->query.');
	}
	
	public function testResultBaseTable_Alias()
	{
		$qs = $this->conn->table("a_test")->getStatement();

		$result = $qs->execute();
		if ($result->getBaseTable() !== $this->conn->table("a_test")) $this->fail('Base table of result is not set correctly.');

		$result = $this->conn->query($qs);
		if ($result->getBaseTable() !== $this->conn->table("a_test")) $this->fail('Base table of result is not set correctly, when using conn->query.');
	}

	public function testResultField()
	{
		$result = $this->conn->query("SELECT *, title as `xyz` FROM `test`");
		$this->assertEquals($result->numFields(), sizeof($result->getFields()), "Incorrect number of fields:");
		
		$field = $result->getField('title');
		$this->assertNotNull($field, "Did not find field 'title'");
		$this->assertEquals('title', $field['name']);
		$this->assertEquals('title', $field['name_db']);
		$this->assertEquals('Title', $field['description']);
		$this->assertEquals('test', $field['table']);

		$field = $result->getField('xyz');
		$this->assertNotNull($field, "Did not find field 'xyz'");
		$this->assertEquals('xyz', $field['name']);
		$this->assertEquals('title', $field['name_db']);
		$this->assertEquals('Xyz', $field['description']);
		$this->assertEquals('test', $field['table']);

		$field = $result->getField('key');
		$this->assertNotNull($field, "Did not find field 'key'");
		$this->assertEquals('Reference', $field['description']);
		
		$field = $result->getField('#role:description');
		$this->assertNotNull($field, "Did not find field '#role:description'");
		$this->assertEquals('title', $field['name']);
	}

	public function testResultJoinedField()
	{
		$result = $this->conn->query("SELECT child.*, test.id as parent_id, test.title as parent_desc FROM test LEFT JOIN child ON test.id = child.test_id");
		$this->assertEquals($result->numFields(), sizeof($result->getFields()), "Incorrect number of fields:");
		
		$field = $result->getField('subtitle');
		$this->assertNotNull($field, "Did not find field 'subtitle'");
		$this->assertEquals('subtitle', $field['name']);
		$this->assertEquals('subtitle', $field['name_db']);
		$this->assertEquals('Subtitle', $field['description']);
		$this->assertEquals('child', $field['table']);

		$field = $result->getField('parent_desc');
		$this->assertNotNull($field, "Did not find field 'parent_desc'");
		$this->assertEquals('parent_desc', $field['name']);
		$this->assertEquals('title', $field['name_db']);
		$this->assertEquals('Parent desc', $field['description']);
		$this->assertEquals('test', $field['table']);
		$this->assertEquals('test', $field['table_def']);
		
		$field = $result->getField('#role:description');
		$this->assertNotNull($field, "Did not find field '#role:description'");
		$this->assertEquals('subtitle', $field['name']);
		$this->assertEquals('child', $field['table']);
	}

	public function testResultField_BaseTableAlias()
	{
		$qs = new DB_SQLStatement($this->conn->table('a_test'), "SELECT *, title AS `xyz` FROM `test`");
		$result = $qs->execute(); 
		$this->assertEquals($result->numFields(), count($result->getFields()), "Incorrect number of fields");
		
		$field = $result->getField('title');
		$this->assertNotNull($field, "Did not find field 'title'");
		$this->assertEquals('title', $field['name']);
		$this->assertEquals('title', $field['name_db']);
		$this->assertEquals('Name', $field['description']);
		$this->assertEquals('test', $field['table']);
		$this->assertEquals('a_test', $field['table_def']);

		$field = $result->getField('xyz');
		$this->assertNotNull($field, "Did not find field 'xyz'");
		$this->assertEquals('xyz', $field['name']);
		$this->assertEquals('title', $field['name_db']);
		$this->assertEquals('XYZ!!!', $field['description']);
		$this->assertEquals('test', $field['table']);

		$field = $result->getField('key');
		$this->assertNotNull($field, "Did not find field 'key'");
		$this->assertEquals('Key', $field['description']);
		
		$field = $result->getField('#role:description');
		$this->assertNotNull($field, "Did not find field '#role:description'");
		$this->assertEquals('title', $field['name'], '#role:description');

		$field = $result->getField('#role:active');
		if (isset($field)) $this->fail("Should not have found field '#role:active'. Found " . $field['name']);
	}

	
    //--------


	public function testTableGetStatement()
	{
		$td = $this->conn->table('test');
		
		$result = $td->getStatement()->execute();

		$field = $result->getField('id');
		$this->assertNotNull($field, "Did not find field 'id'");
		
		$field = $result->getField('title');
		$this->assertNotNull($field, "Did not find field 'title'");
		$this->assertEquals('Title', $field['description']);
		$this->assertEquals('test', $field['table']);
		
		$field = $result->getField('#role:description');
		$this->assertNotNull($field, "Did not find field '#role:description'");
		$this->assertEquals('title', $field['name']);

		$field = $result->getField('#role:active');
		$this->assertNull($field, "Should not have found file '#role:active'");
	}

	public function testTableGetStatement_Overview()
	{
		$td = $this->conn->table('test');
		
		$result = $td->getStatement('overview')->execute();
		
		$field = $result->getField('title');
		$this->assertNotNull($field, "Did not find field 'title'");
		$this->assertEquals('Title', $field['description']);
		$this->assertEquals('test', $field['table']);
		
		$field = $result->getField('#role:description');
		$this->assertNotNull($field, "Did not find field '#role:description'");
		$this->assertEquals('title', $field['name']);

		$field = $result->getField('#role:active');
		$this->assertNull($field, "Should not have found file '#role:active'");
	}

	public function testTableGetStatement_Descview()
	{
		$td = $this->conn->table('test');		
		$result = $td->getStatement('descview')->execute();
		
		$field = $result->getField('title');
		$this->assertNotNull($field, "Did not find field 'title'");
		$this->assertEquals('Title', $field['description']);
		$this->assertEquals('test', $field['table']);
		
		$field = $result->getField('#role:description');
		$this->assertNotNull($field, "Did not find field '#role:description'");
		$this->assertEquals('title', $field['name']);

		$field = $result->getField('#role:active');
		$this->assertNotNull($field, "Did not find field '#role:active'");
	}

	public function testTableGetStatement_Roles()
	{
		$td = $this->conn->table('test');
		
		$result = $td->getStatement('overview', '#role:*')->execute();	

		$field = $result->getField('title');
		$this->assertNotNull($field, "Did not find field 'title'");
		$this->assertEquals('Title', $field['description']);
		$this->assertEquals('test', $field['table']);

		$field = $result->getField('role:id');
		$this->assertNotNull($field, "Did not find field 'role:id'");
		$this->assertEquals('role:id', $field['name'], "Name of 'role:id'");
		$this->assertEquals('id', $field['name_db'], "DB name of 'role:id'");
		
		$field = $result->getField('#role:id');
		$this->assertNotNull($field, "Did not find field '#role:id'");
		$this->assertEquals('id', $field['name'], "Name of '#role:id'");
		$this->assertEquals('id', $field['name_db'], "DB name of '#role:id'");
		
		$field = $result->getField('is_active');
		if (isset($field)) $this->fail("Should not have found field 'is_active'. Found " . $field['name']);
		
		$field = $result->getField('role:active');
		$this->assertNotNull($field, "Did not find field 'role:active'");
		$this->assertEquals('role:active', $field['name'], "Name of 'role:active'");
		$this->assertNull($field['name_db'], "Should not find a DB name for 'role:active', since it is an expression");
		$this->assertEquals('Logical status', $field['description'], "Description of 'role:active'");
		$this->assertContains('active', $field['role'], "Role of 'role:active'");
		
		$field_role = $result->getField('#role:active');
		$this->assertNotNull($field_role, "Did not find field '#role:active'");
		$this->assertSame($field, $field_role);
	}
	

    //--------

    /**
     * Helper function: Test field of a record
     *
     * @param DB_Record $record
     */
    function recordFieldsTest($record)
    {
		$field = $record->getField('id');
		$this->assertNotNull($field, "Did not find field 'id'");
		$this->assertEquals('Id', $field['description'], "Description of 'id'");
		$this->assertEquals('test', $field['table'], "Table of 'id'");
		$this->assertEquals(1, $field->getValue(), "Value of 'id'");

		$field_role = $record->getField('#role:id');
		$this->assertNotNull($field_role, "Did not find field '#role:id'");
		if ($field_role !== $field) $this->fail("#role:id != id, but " . $field_role->name);
		
		$field = $record->getField('title');
		$this->assertNotNull($field, "Did not find field 'title'");
		$this->assertEquals('Title', $field['description'], "Description of 'title'");
		$this->assertEquals('first row', $field->getValue(), "Value of 'title'");

		$field = $record->getField('#role:active');
		$this->assertNull($field, "Should not have found file '#role:active'");
	}
    
    function testRecordFields_Result()
	{
		$result = $this->conn->query("SELECT * FROM `test`");
		$record = $result->fetchRecord();
		$this->assertType('Q\DB_Record', $record);
		
		$this->recordFieldsTest($record);
	}    
    
    function testRecordFields_Statement()
	{
		$record = $this->conn->prepare("SELECT * FROM `test`")->load(1);
		$this->assertType('Q\DB_Record', $record);
		
		$this->recordFieldsTest($record);
	}
    
    function testRecordFields_Table()
	{
		$record = $this->conn->table('test')->load(1);
		$this->assertType('Q\DB_Record', $record);
		
		$this->recordFieldsTest($record);
	}
	
    function testRecordField_hasChanged()
	{
		$record = $this->conn->table('test')->load(1);
		$this->assertType('Q\DB_Record', $record);
		
		$record->title = "just a test";
		$this->assertFalse($record->getField('id')->hasCanged(), "id");
		$this->assertTrue($record->getField('title')->hasCanged(), "title");
	}	
	
    function testRecordField_Crypt()
	{
		$record = $this->conn->table('test')->load(1);
		$this->assertType('Q\DB_Record', $record);
		
		$record->getField('title')->setProperty('crypt', 'md5');
		$record->title = "just a test";
		$this->assertEquals(md5("just a test"), $record->getField('title')->getValueForSave());
	}
	
    //--------

    
    function testActiveRecord_get()
	{
		$result = $this->conn->query("SELECT * FROM `test` WHERE `status`='ACTIVE'");

		$record = $result->fetchRecord();
		$this->assertEquals(1, $record->id, 'id');
		$this->assertEquals('first row', $record->title, 'title');
		$this->assertEquals('one', $record->{'#role:index'}, '#role:index');

		$record = $result->fetchRow(DB::FETCH_RECORD);
		$this->assertEquals(2, $record->id, 'id');
		$this->assertEquals('next row', $record->title, 'title');
		$this->assertEquals('two', $record->{'#role:index'}, '#role:index');
		
		$record = $result->fetchRecord();
		$this->assertNull($record, "Fetch 3th time");

		$result->resetPointer();
		$record = $result->fetchRecord();
		$this->assertEquals(1, $record->id, "Fetch after reset pointer");
	}
	
    function testActiveRecord_update()
	{
		$record = $this->conn->prepare("SELECT * FROM `test`")->load(1);
		$this->assertEquals('first row', $record->title);
		$record->title = "Changed first row";
		$this->assertEquals("Changed first row", $record->title);
		$record->save();
		
		$record = $this->conn->prepare("SELECT * FROM `test`")->load(1);
		$this->assertEquals("Changed first row", $record->title);
	}
	
    function testActiveRecord_insert()
	{
		$record = $this->conn->prepare("SELECT * FROM `test`")->newRecord();
		$this->assertNull($record->id, "id before update");
		
		$record->title = "Added row";
		$record->save();
		$this->assertNotNull($record->id, "id after update");
		
		$id = $record->getId();
		$record = $this->conn->prepare("SELECT * FROM `test`")->load($id);
		$this->assertEquals("Added row", $record->title);
	}
	
	
    //--------


    function testActiveRecordJoined_get()
	{
		$result = $this->conn->query("SELECT child.*, test.id as parent_id, test.title as parent_desc FROM test LEFT JOIN child ON test.id = child.test_id");

		$record = $result->fetchRecord();
		$this->assertEquals(1, $record->id);
		$this->assertEquals('child 1', $record->subtitle);
		$this->assertEquals('first row', $record->parent_desc);
	}
	    
    function testActiveRecordJoined_update()
	{
		$record = $this->conn->prepare("SELECT child.*, test.id as parent_id, test.title as parent_desc FROM test LEFT JOIN child ON test.id = child.test_id")->load(1);
		$this->assertEquals('child 1', $record->subtitle);
		$record->subtitle = "Changed child";
		$record->parent_desc = "Changed first row";
		$record->save();
		
		$record = $this->conn->prepare("SELECT child.*, test.id as parent_id, test.title as parent_desc FROM test LEFT JOIN child ON test.id = child.test_id")->load(1);
		$this->assertEquals("Changed child", $record->subtitle);
		$this->assertEquals("Changed first row", $record->parent_desc);
	}

    function testActiveRecordJoined_updateTable()
	{
		$record = $this->conn->prepare("SELECT child.*, test.id as parent_id, test.title as parent_desc FROM test LEFT JOIN child ON test.id = child.test_id")->load(1);
		$this->assertEquals('child 1', $record->subtitle);
		$record->subtitle = "Changed child";
		$record->parent_desc = "Changed first row";
		$record->save('test');
		
		$record = $this->conn->prepare("SELECT child.*, test.id as parent_id, test.title as parent_desc FROM test LEFT JOIN child ON test.id = child.test_id")->load(1);
		$this->assertEquals("child 1", $record->subtitle);
		$this->assertEquals("Changed first row", $record->parent_desc);
	}
	
    function testActiveRecordJoined_insert()
	{
		$record = $this->conn->prepare("SELECT child.*, test.id as parent_id, test.title as parent_desc FROM test LEFT JOIN child ON test.id = child.test_id")->newRecord();
		$this->assertType('Q\DB_Record', $record, "New record");
		$this->assertNull($record->id, "id before update");
		
		$record->subtitle = "Added child";
		$record->parent_desc = "Added row";
		$record->save();
		$this->assertNotNull($record->id, "id after update");
		$this->assertNotNull($record->parent_id, "parent_id after update");
		$this->assertNotNull($record->parent_id, $record->test_id, "parent_id != test_id");
		
		$id = $record->getId();
		$record = $this->conn->prepare("SELECT child.*, test.id as parent_id, test.title as parent_desc FROM test LEFT JOIN child ON test.id = child.test_id")->load($id);
		$this->assertType('Q\DB_Record', $record, "Loaded record");
		$this->assertEquals("Added child", $record->subtitle);
		$this->assertEquals("Added row", $record->parent_desc);
	}
	
	
    function testActiveRecordCascaded()
	{
	    $result = $this->conn->query("SELECT *, ROWS(SELECT * FROM child CASCADE ON child.test_id = test.id) AS children FROM test");
	    
		$record = $result->fetchRecord();
		$this->assertEquals(1, $record->id);
		$this->assertEquals('first row', $record->title);	    
	    
		$values = $record->getValue('children');
		$this->assertEquals($values[0]['subtitle'], 'child 1');
		$this->assertEquals($values[1]['subtitle'], 'child 2');
		$this->assertType('ArrayObject', $record->children);
		$this->assertEquals('child 1', $record->children[0]->subtitle);

		$record = $result->fetchRecord();
		$this->assertEquals(2, $record->id);
		$this->assertEquals('child X', $record->children[0]->subtitle);
	}
}

?>