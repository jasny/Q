<?php
use Q\DB, Q\DB_SQLStatement, Q\DB_MySQL_SQLSplitter;

require_once 'TestHelper.php';
require_once 'PHPUnit/Framework/TestCase.php';

require_once 'Q/DB/MySQL/SQLSplitter.php';
require_once 'Q/DB/SQLStatement.php';

/**
 * Test for DB_MySQL_SQLSplitter and modifing DB_SQLStatement objects. 
 */
class DB_MySQL_SQLSplitterTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Query splitter
	 * @var DB_MySQL_SQLSplitter
	 */
	public $qs;
	
   /**
	 * Init test
	 */
	public function setUp()
	{
		$this->qs = new DB_MySQL_SQLSplitter();
	}

	/**
	 * End test
	 */
	public function tearDown()
	{
		$this->qs = null;
	}

    /**
     * Create a query statement objects
     *
     * @param string $statment
     * @return DB_SQLStatement
     */
    public function statement($statement)
    {
        $s = new DB_SQLStatement($statement);
        $s->sqlSplitter = $this->qs;
        return $s;
    }
    
    /**
     * Helper function to remove spaces from a query.
     */
    static function cleanQuery($sql)
    {
		return trim(preg_replace('/(?:\s+(\s|\)|\,)|(\()\s+)/', '\1\2', $sql));
    }
    
	//--------
    
    public function testQuote_Null()
    {
    	$this->assertEquals('NULL', $this->qs->quote(null));
    }

    public function testQuote_NullDefault()
    {
    	$this->assertEquals('DEFAULT', $this->qs->quote(null, 'DEFAULT'));
    }
    
    public function testQuote_Int()
    {
    	$this->assertEquals('1', $this->qs->quote(1));
    }

    public function testQuote_Float()
    {
    	$this->assertEquals('1.3', $this->qs->quote(1.3));
    }
    
    public function testQuote_True()
    {
    	$this->assertEquals('TRUE', $this->qs->quote(true));
    }

    public function testQuote_False()
    {
    	$this->assertEquals('FALSE', $this->qs->quote(false));
    }

    public function testQuote_String()
    {
    	$this->assertEquals('"test"', $this->qs->quote('test'));
    }

    public function testQuote_StringQuotes()
    {
    	$this->assertEquals('"test \"abc\" test"', $this->qs->quote('test "abc" test'));
    }

    public function testQuote_StringMultiline()
    {
    	$this->assertEquals('"line1\nline2\nline3"', $this->qs->quote("line1\nline2\nline3"));
    }

    public function testQuote_Array()
    {
    	$this->assertEquals('1, TRUE, "abc", DEFAULT', $this->qs->quote(array(1, true, "abc", null), 'DEFAULT'));
    }
    
    
    public function testQuoteIdentifier_Simple()
    {
    	$this->assertEquals('`test`', $this->qs->quoteIdentifier("test"));
    }

    public function testQuoteIdentifier_Quoted()
    {
    	$this->assertEquals('`test`', $this->qs->quoteIdentifier("`test`"));
    }
    
    public function testQuoteIdentifier_TableColumn()
    {
    	$this->assertEquals('`abc`.`test`', $this->qs->quoteIdentifier("abc.test"));
    }

    public function testQuoteIdentifier_TableColumn_Quoted()
    {
    	$this->assertEquals('`abc`.`test`', $this->qs->quoteIdentifier("`abc`.`test`"));
    }
    
    public function testQuoteIdentifier_WithAlias()
    {
		$this->assertEquals('`abc`.`test` AS `def`', $this->qs->quoteIdentifier("abc.test AS def"));
    }

    public function testQuoteIdentifier_Function()
    {
    	$this->assertEquals('count(`abc`.`test`) AS `count`', $this->qs->quoteIdentifier("count(abc.test) AS count"));
    }

    public function testQuoteIdentifier_Cast()
    {
    	$this->assertEquals('`qqq`, cast(`abc`.`test` AS DATETIME)', $this->qs->quoteIdentifier("qqq, cast(`abc`.test AS DATETIME)"));
    }

    public function testQuoteIdentifier_Cast_Confuse()
    {
    	$this->assertEquals('`qqq`, cast(myfn(`abc`.`test` as `myarg`) AS DATETIME) AS `date`', $this->qs->quoteIdentifier("qqq, cast(myfn(`abc`.test as myarg) AS DATETIME) AS date"));
    }
    
    public function testQuoteIdentifier_Expression()
    {
    	$this->assertEquals('`abc`.`test` - `def`.`total`*10 AS `grandtotal`', $this->qs->quoteIdentifier("abc.test - def.total*10 AS grandtotal"));
    }

    public function testQuoteIdentifier_Strict()
    {
    	$this->assertEquals('`abd-def*10`', $this->qs->quoteIdentifier("abd-def*10", DB::QUOTE_STRICT));
    }

    public function testQuoteIdentifier_Strict_TableColumn()
    {
    	$this->assertEquals('`abc`.`test-10`', $this->qs->quoteIdentifier("`abc`.test-10", DB::QUOTE_STRICT));
    }
    
    public function testQuoteIdentifier_Strict_Fail()
    {
    	$this->setExpectedException('Q\SecurityException', "Unable to quote '`abc`.`test`-10' safely");
    	$this->qs->quoteIdentifier("`abc`.`test`-10", DB::QUOTE_STRICT);
    }
    
    
    public function testValidIdentifier_Simple()
    {
    	$this->assertTrue($this->qs->validIdentifier('test'));
    }

    public function testValidIdentifier_Quoted()
    {
    	$this->assertTrue($this->qs->validIdentifier('`test`'));
    }

    public function testValidIdentifier_TableColumn()
    {
    	$this->assertTrue($this->qs->validIdentifier('abc.test'));
    }

    public function testValidIdentifier_TableColumn_Quoted()
    {
    	$this->assertTrue($this->qs->validIdentifier('`abc`.`test`'));
    }

    public function testValidIdentifier_Strange()
    {
    	$this->assertFalse($this->qs->validIdentifier('ta-$38.934#34@dhy'));
    }
    
    public function testValidIdentifier_Strange_Quoted()
    {
    	$this->assertTrue($this->qs->validIdentifier('`ta-$38.934#34@dhy`'));
    }

    public function testValidIdentifier_NoGroup_Simple()
    {
    	$this->assertTrue($this->qs->validIdentifier('test', DB::FIELDNAME_COLUMN));
    }

    public function testValidIdentifier_NoGroup_Quoted()
    {
    	$this->assertTrue($this->qs->validIdentifier('`test`', DB::FIELDNAME_COLUMN));
    }

    public function testValidIdentifier_NoGroup_TableColumn()
    {
    	$this->assertFalse($this->qs->validIdentifier('abc.test', DB::FIELDNAME_COLUMN));
    }

    public function testValidIdentifier_NoGroup_TableColumn_Quoted()
    {
    	$this->assertFalse($this->qs->validIdentifier('`abc`.`test`', DB::FIELDNAME_COLUMN));
    }

    public function testValidIdentifier_NoGroup_Strange()
    {
    	$this->assertFalse($this->qs->validIdentifier('ta-$38.934#34@dhy', DB::FIELDNAME_COLUMN));
    }
    
    public function testValidIdentifier_NoGroup_Strange_Quoted()
    {
    	$this->assertTrue($this->qs->validIdentifier('`ta-$38.934#34@dhy`', DB::FIELDNAME_COLUMN));
    }
    
    public function testValidIdentifier_WithGroup_Simple()
    {
    	$this->assertFalse($this->qs->validIdentifier('test', DB::FIELDNAME_FULL));
    }

    public function testValidIdentifier_WithGroup_Quoted()
    {
    	$this->assertFalse($this->qs->validIdentifier('`test`', DB::FIELDNAME_FULL));
    }

    public function testValidIdentifier_WithGroup_TableColumn()
    {
    	$this->assertTrue($this->qs->validIdentifier('abc.test', DB::FIELDNAME_FULL));
    }

    public function testValidIdentifier_WithGroup_TableColumn_Quoted()
    {
    	$this->assertTrue($this->qs->validIdentifier('`abc`.`test`', DB::FIELDNAME_FULL));
    }

    public function testValidIdentifier_WithGroup_Strange()
    {
    	$this->assertFalse($this->qs->validIdentifier('ta-$38.934#34@dhy', DB::FIELDNAME_FULL));
    }
    
    public function testValidIdentifier_WithGroup_Strange_Quoted()
    {
    	$this->assertFalse($this->qs->validIdentifier('`ta-$38.934#34@dhy`', DB::FIELDNAME_FULL));
    }
    
    public function testValidIdentifier_WithoutAlias_AsAlias()
    {
    	$this->assertFalse($this->qs->validIdentifier('`test` AS def'));
    }
    
    public function testValidIdentifier_WithoutAlias_SpaceAlias()
    {
    	$this->assertFalse($this->qs->validIdentifier('`test` def'));
    }
    
    public function testValidIdentifier_WithoutAlias_Full()
    {
    	$this->assertFalse($this->qs->validIdentifier('`abc`.`test` AS def'));
    }
    
    public function testValidIdentifier_WithAlias_AsAlias()
    {
    	$this->assertTrue($this->qs->validIdentifier('`test` AS def', DB::WITH_ALIAS));
    }

    public function testValidIdentifier_WithAlias_SpaceAlias()
    {
    	$this->assertTrue($this->qs->validIdentifier('`test` def', DB::WITH_ALIAS));
    }
    
    public function testValidIdentifier_WithAlias_Full()
    {
    	$this->assertTrue($this->qs->validIdentifier('`abc`.`test` AS def', DB::WITH_ALIAS));
    }

    public function testValidIdentifier_WithGroupWithAlias_AsAlias()
    {
    	$this->assertFalse($this->qs->validIdentifier('`test` AS def', DB::FIELDNAME_FULL | DB::WITH_ALIAS));
    }
    
    public function testValidIdentifier_WithGroupWithAlias_Full()
    {
    	$this->assertTrue($this->qs->validIdentifier('`abc`.`test` AS def', DB::FIELDNAME_FULL | DB::WITH_ALIAS));
    }
    
    
    public function testSplitIdentifier_Column()
    {
    	$this->assertEquals(array(null, 'test', null), $this->qs->splitIdentifier("test"));
    }

    public function testSplitIdentifier_Column_Quoted()
    {
    	$this->assertEquals(array(null, 'test', null), $this->qs->splitIdentifier("`test`"));
    }
    
    public function testSplitIdentifier_TableColumn()
    {
    	$this->assertEquals(array('abc', 'test', null), $this->qs->splitIdentifier("abc.test"));
    }

    public function testSplitIdentifier_TableColumn_Quoted()
    {
    	$this->assertEquals(array('abc', 'test', null), $this->qs->splitIdentifier("`abc`.`test`"));
    }

    public function testSplitIdentifier_SchemaTableColumn()
    {
    	$this->assertEquals(array('mydb.abc', 'test', null), $this->qs->splitIdentifier("mydb.abc.test"));
    }

    public function testSplitIdentifier_SchemaTableColumn_Quoted()
    {
    	$this->assertEquals(array('mydb.abc', 'test', null), $this->qs->splitIdentifier("`mydb`.`abc`.`test`"));
    }
    
    public function testSplitIdentifier_QuotedDot()
    {
    	$this->assertEquals(array(null, 'abc.test', null), $this->qs->splitIdentifier("`abc.test`"));
    }

    public function testSplitIdentifier_Column_AsAlias()
    {
    	$this->assertEquals(array(null, 'test', 'def'), $this->qs->splitIdentifier("test AS def"));
    }
    
    public function testSplitIdentifier_Column_SpaceAlias()
    {
    	$this->assertEquals(array(null, 'test', 'def'), $this->qs->splitIdentifier("test def"));
    }
    
    public function testSplitIdentifier_TableColumn_AsAlias()
    {
    	$this->assertEquals(array('abc', 'test', 'def'), $this->qs->splitIdentifier("`abc`.`test` AS `def`"));
    }
    
    public function testSplitIdentifier_TableColumn_SpaceAlias()
    {
    	$this->assertEquals(array('abc', 'test', 'def'), $this->qs->splitIdentifier("`abc`.`test` `def`"));
    }

    
    public function testMakeIdentifier_Column()
    {
    	$this->assertEquals('`test`', $this->qs->makeIdentifier(null, 'test'));
    }

    public function testMakeIdentifier_TableColumn()
    {
    	$this->assertEquals('`abc`.`test`', $this->qs->makeIdentifier('abc', 'test'));
    }
    
    public function testMakeIdentifier_TableColumnAlias()
    {
    	$this->assertEquals('`abc`.`test` AS `def`', $this->qs->makeIdentifier('abc', 'test', 'def'));
    }

    public function testMakeIdentifier_SchemaTableColumn()
    {
    	$this->assertEquals('`mydb`.`abc`.`test`', $this->qs->makeIdentifier('mydb.abc', 'test'));
    }

    public function testMakeIdentifier_NoQuote()
    {
    	$this->assertEquals('abc.test AS def', $this->qs->makeIdentifier('abc', 'test', 'def', DB::QUOTE_NONE));
    }
    
    public function testMakeIdentifier_Expression()
    {
    	$this->assertEquals('`test`-`qqq` AS `grandtotal`', $this->qs->makeIdentifier('abc', "test-qqq", 'grandtotal'));
    }

    public function testMakeIdentifier_Expression_Strict()
    {
    	$this->assertEquals('`abc`.`test-qqq` AS `grandtotal`', $this->qs->makeIdentifier('abc', "test-qqq", 'grandtotal', DB::QUOTE_STRICT));
    }

    public function testMakeIdentifier_Expression_NoQuote()
    {
    	$this->assertEquals('test-qqq AS grandtotal', $this->qs->makeIdentifier('abc', "test-qqq", 'grandtotal', DB::QUOTE_NONE));
    }

    public function testMakeIdentifier_Quoted()
    {
    	$this->assertEquals('`abc`.`test-qqq` AS `grandtotal`', $this->qs->makeIdentifier('abc', "`test-qqq`", 'grandtotal'));
    }
    
    public function testMakeIdentifier_Quoted_NoQuote()
    {
    	$this->assertEquals('abc.`test-qqq` AS grandtotal', $this->qs->makeIdentifier('abc', "`test-qqq`", 'grandtotal', DB::QUOTE_NONE));
    }
    
    //--------
	
    
    public function testParse_Null()
    {
        $this->assertEquals('UPDATE phpunit_test SET description=NULL', $this->qs->parse('UPDATE phpunit_test SET description=?', array(null)));
    }
    
    public function testParse_Integer()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE status=10', $this->qs->parse("SELECT * FROM phpunit_test WHERE status=?", array(10)));
    }

    public function testParse_Float()
    {
    	$this->assertEquals('SELECT * FROM phpunit_test WHERE status=33.7', $this->qs->parse("SELECT * FROM phpunit_test WHERE status=?", array(33.7)));
    }
    
    public function testParse_Boolean()
    {
    	$this->assertEquals('SELECT * FROM phpunit_test WHERE status=TRUE AND disabled=FALSE', $this->qs->parse("SELECT * FROM phpunit_test WHERE status=? AND disabled=?", array(true, false)));
    }

    public function testParse_String()
    {
        $this->assertEquals('SELECT id, "test" AS `desc` FROM phpunit_test WHERE status="ACTIVE"', $this->qs->parse('SELECT id, ? AS `desc` FROM phpunit_test WHERE status=?', array('test', 'ACTIVE')));
    }

    public function testParse_String_Confuse()
    {
        $this->assertEquals('SELECT id, "?" AS `desc ?`, \'?\' AS x FROM phpunit_test WHERE status="ACTIVE"', $this->qs->parse('SELECT id, "?" AS `desc ?`, \'?\' AS x FROM phpunit_test WHERE status=?', array('ACTIVE', 'not me', 'not me', 'not me')));
    }
    
    public function testParse_String_Quote()
    {
    	$this->assertEquals('SELECT * FROM phpunit_test WHERE description="This is a \\"test\\""', $this->qs->parse('SELECT * FROM phpunit_test WHERE description=?', array('This is a "test"')));
    }
    
    public function testParse_String_Multiline()
    {
    	$this->assertEquals('SELECT * FROM phpunit_test WHERE description="This is a \\"test\\"\\nWith another line"', $this->qs->parse('SELECT * FROM phpunit_test WHERE description=?', array('This is a "test"' . "\n" . 'With another line')));
    }

    public function testParse_Array()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description IN ("test", 10, FALSE, "another test")', $this->qs->parse('SELECT * FROM phpunit_test WHERE description IN (?)', array(array("test", 10, FALSE, "another test"))));
    }
	
    public function testParse_NoQuote()
    {
        $this->assertEquals('SELECT id, test AS `desc` FROM phpunit_test WHERE status="ACTIVE"', $this->qs->parse('SELECT id, ?! AS `desc` FROM phpunit_test WHERE status=?', array('test', 'ACTIVE')));
    }

    public function testParse_AllNulls()
    {
        $this->assertEquals('SELECT id, (NULL) AS `desc` FROM phpunit_test WHERE status=(NULL)', $this->qs->parse('SELECT id, ?! AS `desc` FROM phpunit_test WHERE status=?', null));
    }
    
    public function testParse_Named()
    {
        $this->assertEquals('SELECT id, "test" AS `desc` FROM phpunit_test WHERE status="ACTIVE"', $this->qs->parse('SELECT id, :desc AS `desc` FROM phpunit_test WHERE status=?', array('desc'=>'test', 'ACTIVE')));
    }
    
    public function testParse_Named_NoQuote()
    {
        $this->assertEquals('SELECT id, test AS `desc` FROM phpunit_test WHERE status="ACTIVE"', $this->qs->parse('SELECT id, :!desc AS `desc` FROM phpunit_test WHERE status=?', array('desc'=>'test', 'ACTIVE')));
    }

    public function testParse_Named_AllNulls()
    {
        $this->assertEquals('SELECT id, (NULL) AS `desc` FROM phpunit_test WHERE status=(NULL)', $this->qs->parse('SELECT id, :desc AS `desc` FROM phpunit_test WHERE status=?', null));
    }
    
    
    public function testCountPlaceholders()
    {
    	$this->assertEquals(2, $this->qs->countPlaceholders('SELECT id, ? AS `desc`, :named AS `named` FROM phpunit_test WHERE status=?'));
    }

    public function testCountPlaceholders_Confuse()
    {
    	$this->assertEquals(1, $this->qs->countPlaceholders('SELECT id, "?" AS `desc ?`, \'?\' AS x FROM phpunit_test WHERE status=?'));
    }
    
	//--------
    
    
    public function testGetQueryType_Select()
    {
    	$this->assertEquals('SELECT', $this->qs->getQueryType("SELECT id, description FROM `test`"));
    }
    
    public function testGetQueryType_Select_Word()
    {
    	$this->assertEquals('SELECT', $this->qs->getQueryType("SELECT"));
    }
    
    public function testGetQueryType_Select_LowerCase()
    {
    	$this->assertEquals('SELECT', $this->qs->getQueryType("select id, description from `test`"));
    }
    
    public function testGetQueryType_Select_Spaces()
    {
    	$this->assertEquals('SELECT', $this->qs->getQueryType("\n\t\n  SELECT id, description FROM `test`"));
    }
    
    public function testGetQueryType_Insert()
    {
    	$this->assertEquals('INSERT', $this->qs->getQueryType("INSERT INTO `test` SELECT 10"));
    }

    public function testGetQueryType_Replace()
    {
    	$this->assertEquals('REPLACE', $this->qs->getQueryType("REPLACE INTO `test` VALUES (10, 'UPDATE')"));
    }

    public function testGetQueryType_Delete()
    {
    	$this->assertEquals('DELETE', $this->qs->getQueryType("DELETE FROM `test` WHERE `select`=10"));
    }
    
    public function testGetQueryType_Truncate()
    {
    	$this->assertEquals('TRUNCATE', $this->qs->getQueryType("TRUNCATE `test`"));
    }

    public function testGetQueryType_AlterTable()
    {
    	$this->assertEquals('ALTER TABLE', $this->qs->getQueryType("ALTER TABLE `test`"));
    }

    public function testGetQueryType_AlterView_Spaces()
    {
    	$this->assertEquals('ALTER VIEW', $this->qs->getQueryType("ALTER\n\t\tVIEW `test`"));
    }

    public function testGetQueryType_AlterUnknown()
    {
    	$this->assertNull($this->qs->getQueryType("ALTER test set abc"));
    }
    
    public function testGetQueryType_Set()
    {
    	$this->assertEquals('SET', $this->qs->getQueryType("SET @select=10"));
    }

    public function testGetQueryType_Begin()
    {
    	$this->assertEquals('START TRANSACTION', $this->qs->getQueryType("BEGIN"));
    }

    public function testGetQueryType_LoadDataInfile()
    {
    	$this->assertEquals('LOAD DATA INFILE', $this->qs->getQueryType("LOAD DATA INFILE"));
    }

    public function testGetQueryType_Comment()
    {
    	$this->assertNull($this->qs->getQueryType("-- SELECT `test`"));
    }

    public function testGetQueryType_Unknown()
    {
    	$this->assertNull($this->qs->getQueryType("something"));
    }
    
    
	public function testSplit_Select_Simple()
    {
		$parts = $this->qs->split("SELECT id, description FROM `test`");
		$this->assertEquals(array(0=>'SELECT', 'columns'=>'id, description', 'from'=>'`test`', 'where'=>'', 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>''), array_map('trim', $parts));
    }

    public function testSplit_Select_Advanced()
    {
		$parts = $this->qs->split("SELECT DISTINCTROW id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing FROM `test` INNER JOIN abc ON test.id = abc.id WHERE test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE' GROUP BY my_dd HAVING COUNT(1+3+xyz) < 100 LIMIT 15, 30 FOR UPDATE");
    	$this->assertEquals(array(0=>'SELECT DISTINCTROW', 'columns'=>"id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing", 'from'=>"`test` INNER JOIN abc ON test.id = abc.id", 'where'=>"test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE'", 'group by'=>"my_dd", 'having'=>"COUNT(1+3+xyz) < 100", 'order by'=>'', 'limit'=>"15, 30", 100=>"FOR UPDATE"), array_map('trim', $parts));
    }
    
    public function testSplit_Select_Subquery()
    {
		$parts = $this->qs->split("SELECT id, description, VALUES(SELECT id, desc FROM subt WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs FROM `test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' ORDER BY abx.dd");
    	$this->assertEquals(array(0=>'SELECT', 'columns'=>"id, description, VALUES(SELECT id, desc FROM subt WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs", 'from'=>"`test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc", 'where'=>"abc.x IN (1,2,3,6,7) AND qq!='(SELECT)'", 'group by'=>'', 'having'=>'', 'order by'=>'abx.dd', 'limit'=>'', 100=>''), array_map('trim', $parts));
    }

    public function testSplit_Select_SubqueryMadness()
    {
		$parts = $this->qs->split("SELECT id, description, VALUES(SELECT id, desc FROM subt1 INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON subt1.id = subt2.p_id WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs FROM `test` INNER JOIN (SELECT * FROM abc INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON abc.id = subt2.p_id WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' AND x_id IN (SELECT id FROM x) ORDER BY abx.dd LIMIT 10");
    	$this->assertEquals(array(0=>'SELECT', 'columns'=>"id, description, VALUES(SELECT id, desc FROM subt1 INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON subt1.id = subt2.p_id WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs", 'from'=>"`test` INNER JOIN (SELECT * FROM abc INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON abc.id = subt2.p_id WHERE i = 1 GROUP BY x) AS abc", 'where'=>"abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' AND x_id IN (SELECT id FROM x)", 'group by'=>'', 'having'=>'', 'order by'=>'abx.dd', 'limit'=>'10', 100=>''), array_map('trim', $parts));
    }

	public function testSplit_Select_Semicolon()
    {
		$parts = $this->qs->split("SELECT id, description FROM `test`; Please ignore this");
    	$this->assertEquals(array(0=>'SELECT', 'columns'=>'id, description', 'from'=>'`test`', 'where'=>'', 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>''), array_map('trim', $parts));
    }
    
    
	public function testJoinSelect_Simple()
    {
		$sql = $this->qs->join(array(0=>'SELECT', 'columns'=>'id, description', 'from'=>'`test`', 'where'=>'', 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>''));
    	$this->assertEquals("SELECT id, description FROM `test`", $sql);
    }

    public function testJoinSelect_Advanced()
    {
		$sql = $this->qs->join(array(0=>'SELECT DISTINCTROW', 'columns'=>"id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing", 'from'=>"`test` INNER JOIN abc ON test.id = abc.id", 'where'=>"test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE'", 'group by'=>"my_dd", 'having'=>"COUNT(1+3+xyz) < 100", 'order by'=>'', 'limit'=>"15, 30", 100=>"FOR UPDATE"));
    	$this->assertEquals("SELECT DISTINCTROW id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing FROM `test` INNER JOIN abc ON test.id = abc.id WHERE test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE' GROUP BY my_dd HAVING COUNT(1+3+xyz) < 100 LIMIT 15, 30 FOR UPDATE", $sql);
    }
    
    public function testJoinSelect_Subquery()
    {
		$sql = $this->qs->join(array(0=>'SELECT', 'columns'=>"id, description", 'from'=>"`test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc", 'where'=>"abc.x IN (1,2,3,6,7) AND qq!='(SELECT)'", 'group by'=>'', 'having'=>'', 'order by'=>'abx.dd', 'limit'=>'', 100=>''));
    	$this->assertEquals("SELECT id, description FROM `test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' ORDER BY abx.dd", $sql);
    }
    
    //--------
    
    
    public function testSplit_InsertValuesSimple()
    {
		$parts = $this->qs->split("INSERT INTO `test` VALUES (NULL, 'abc')");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>'', 'values'=>"(NULL, 'abc')", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

	public function testSplit_ReplaceValuesSimple()
    {
		$parts = $this->qs->split("REPLACE INTO `test` VALUES (NULL, 'abc')");
    	$this->assertEquals(array(0=>'REPLACE', 'into'=>'`test`', 'columns'=>'', 'values'=>"(NULL, 'abc')", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

	public function testSplit_InsertValuesColumns()
    {
		$parts = $this->qs->split("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>"(`id`, description, `values`)", 'values'=>"(NULL, 'abc', 10)", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

	public function testSplit_InsertValuesMultiple()
    {
		$parts = $this->qs->split("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>"(`id`, description, `values`)", 'values'=>"(NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)", 'on duplicate key update'=>''), array_map('trim', $parts));
    }
    
    
	public function testSplit_InsertSetSimple()
    {
		$parts = $this->qs->split("INSERT INTO `test` SET `id`=NULL, description = 'abc'");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'set'=>"`id`=NULL, description = 'abc'", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

    
	public function testSplit_InsertSelectSimple()
    {
		$parts = $this->qs->split("INSERT INTO `test` SELECT NULL, name FROM xyz");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>'', 'query'=>"SELECT NULL, name FROM xyz", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

	public function testSplit_InsertSelectSubquery()
    {
		$parts = $this->qs->split("INSERT INTO `test` SELECT NULL, name FROM xyz WHERE type IN (SELECT type FROM tt GROUP BY type HAVING SUM(qn) > 10)");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>'', 'query'=>"SELECT NULL, name FROM xyz WHERE type IN (SELECT type FROM tt GROUP BY type HAVING SUM(qn) > 10)", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

        
	public function testJoinInsertValuesSimple()
    {
		$sql = $this->qs->join(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>'', 'values'=>"(NULL, 'abc')", 'on duplicate key update'=>''));
    	$this->assertEquals("INSERT INTO `test` VALUES (NULL, 'abc')", $sql);
    }

	public function testJoinReplaceValuesSimple()
    {
		$sql = $this->qs->join(array(0=>'REPLACE', 'into'=>'`test`', 'columns'=>'', 'values'=>"(NULL, 'abc')", 'on duplicate key update'=>''));
    	$this->assertEquals("REPLACE INTO `test` VALUES (NULL, 'abc')", $sql);
    }

	public function testJoinInsertValuesColumns()
    {
		$sql = $this->qs->join(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>"(`id`, description, `values`)", 'values'=>"(NULL, 'abc', 10)", 'on duplicate key update'=>''));
    	$this->assertEquals("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)", $sql);
    }

	public function testJoinInsertValuesMultiple()
    {
		$sql = $this->qs->join(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>"(`id`, description, `values`)", 'values'=>"(NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)", 'on duplicate key update'=>''));
    	$this->assertEquals("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)", $sql);
    }
    
	public function testJoinInsertSelectSimple()
    {
		$sql = $this->qs->join(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>'', 'query'=>"SELECT NULL, name FROM xyz", 'on duplicate key update'=>''));
    	$this->assertEquals("INSERT INTO `test` SELECT NULL, name FROM xyz", $sql);
    }

	public function testJoinInsertSelectSubquery()
    {
		$sql = $this->qs->join(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>'', 'query'=>"SELECT NULL, name FROM xyz WHERE type IN (SELECT type FROM tt GROUP BY type HAVING SUM(qn) > 10)", 'on duplicate key update'=>''));
    	$this->assertEquals("INSERT INTO `test` SELECT NULL, name FROM xyz WHERE type IN (SELECT type FROM tt GROUP BY type HAVING SUM(qn) > 10)", $sql);
    }
	
    //--------


	public function testSplit_UpdateSimple()
    {
		$parts = $this->qs->split("UPDATE `test` SET status='ACTIVE' WHERE id=10");
    	$this->assertEquals(array(0=>'UPDATE', 'tables'=>'`test`', 'set'=>"status='ACTIVE'", 'where'=>'id=10', 'limit'=>''), array_map('trim', $parts));
    }

	public function testSplit_UpdateAdvanced()
    {
		$parts = $this->qs->split("UPDATE `test` LEFT JOIN atst ON `test`.id = atst.idTest SET fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE' WHERE id = 10 LIMIT 20 OFFSET 10");
    	$this->assertEquals(array(0=>'UPDATE', 'tables'=>'`test` LEFT JOIN atst ON `test`.id = atst.idTest', 'set'=>"fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE'", 'where'=>'id = 10', 'limit'=>'20 OFFSET 10'), array_map('trim', $parts));
    }
    
	public function testSplit_UpdateSubquery()
    {
		$parts = $this->qs->split("UPDATE `test` LEFT JOIN (SELECT idTest, a, f, count(*) AS cnt FROM atst) AS atst ON `test`.id = atst.idTest SET fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE' WHERE id IN (SELECT id FROM whatever LIMIT 100)");
    	$this->assertEquals(array(0=>'UPDATE', 'tables'=>'`test` LEFT JOIN (SELECT idTest, a, f, count(*) AS cnt FROM atst) AS atst ON `test`.id = atst.idTest', 'set'=>"fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE'", 'where'=>'id IN (SELECT id FROM whatever LIMIT 100)', 'limit'=>''), array_map('trim', $parts));
    }    

    public function testJoin_UpdateSimple()
    {
		$sql = $this->qs->join(array(0=>'UPDATE', 'tables'=>'`test`', 'set'=>"status='ACTIVE'", 'where'=>'id=10', 'limit'=>''));
    	$this->assertEquals("UPDATE `test` SET status='ACTIVE' WHERE id=10", $sql);
	}

    public function testJoin_UpdateAdvanced()
    {
		$sql = $this->qs->join(array(0=>'UPDATE', 'tables'=>'`test` LEFT JOIN atst ON `test`.id = atst.idTest', 'set'=>"fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE'", 'where'=>'id = 10', 'limit'=>'20 OFFSET 10'));
    	$this->assertEquals("UPDATE `test` LEFT JOIN atst ON `test`.id = atst.idTest SET fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE' WHERE id = 10 LIMIT 20 OFFSET 10", $sql);
	}
	
    //--------

    
	public function testSplit_DeleteSimple()
    {
		$parts = $this->qs->split("DELETE FROM `test` WHERE id=10");
    	$this->assertEquals(array(0=>'DELETE', 'columns'=>'', 'from'=>'`test`', 'where'=>'id=10', 'order by'=>'', 'limit'=>''), array_map('trim', $parts));
    }
        
	public function testSplit_DeleteAdvanced()
    {
		$parts = $this->qs->split("DELETE `test`.* FROM `test` INNER JOIN `dude where is my car`.`import` AS dude_import ON `test`.ref = dude_import.ref WHERE dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10 ORDER BY xyz LIMIT 1");
    	$this->assertEquals(array(0=>'DELETE', 'columns'=>'`test`.*', 'from'=>'`test` INNER JOIN `dude where is my car`.`import` AS dude_import ON `test`.ref = dude_import.ref', 'where'=>"dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10", 'order by'=>'xyz', 'limit'=>'1'), array_map('trim', $parts));
    }

	public function testSplit_DeleteSubquery()
    {
		$parts = $this->qs->split("DELETE `test`.* FROM `test` INNER JOIN (SELECT * FROM dude_import GROUP BY x_id WHERE status = 'OK' HAVING COUNT(*) > 1) AS dude_import ON `test`.ref = dude_import.ref WHERE status = 10");
    	$this->assertEquals(array(0=>'DELETE', 'columns'=>'`test`.*', 'from'=>"`test` INNER JOIN (SELECT * FROM dude_import GROUP BY x_id WHERE status = 'OK' HAVING COUNT(*) > 1) AS dude_import ON `test`.ref = dude_import.ref", 'where'=>"status = 10", 'order by'=>'', 'limit'=>''), array_map('trim', $parts));
    }

	public function testJoin_DeleteSimple()
    {
		$sql = $this->qs->join(array(0=>'DELETE', 'columns'=>'', 'from'=>'`test`', 'where'=>'id=10', 'order by'=>'', 'limit'=>''));
    	$this->assertEquals("DELETE FROM `test` WHERE id=10", $sql);
    }
        
	public function testJoin_DeleteAdvanced()
    {
		$sql = $this->qs->join(array(0=>'DELETE', 'columns'=>'`test`.*', 'from'=>'`test` INNER JOIN `dude where is my car`.`import` AS dude_import ON `test`.ref = dude_import.ref', 'where'=>"dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10", 'order by'=>'xyz', 'limit'=>'1'));
    	$this->assertEquals("DELETE `test`.* FROM `test` INNER JOIN `dude where is my car`.`import` AS dude_import ON `test`.ref = dude_import.ref WHERE dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10 ORDER BY xyz LIMIT 1", $sql);
    }
    
    //--------

    
    public function testSplit_Set()
    {
    	$parts = $this->qs->split("SET abc=10, @def='test'");
    	$this->assertEquals(array('set'=>"abc=10, @def='test'"), array_map('trim', $parts));
    }

    public function testJoin_Set()
    {
    	$sql = $this->qs->join(array('set'=>"abc=10, @def='test'"));
    	$this->assertEquals("SET abc=10, @def='test'", $sql);
    }
    
    //--------

    
    public function testSplitColumns_Simple()
    {
		$columns = $this->qs->splitColumns("abc, xyz, test");
    	$this->assertEquals(array("abc", "xyz", "test"), array_map('trim', $columns));
    }

    public function testSplitColumns_Advanced()
    {
		$columns = $this->qs->splitColumns("abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), test, 10+3 AS `bb`, 'Ho, Hi' AS HoHi, 22");
    	$this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "test", "10+3 AS `bb`", "'Ho, Hi' AS HoHi", "22"), array_map('trim', $columns));
    }

    public function testSplitColumns_SplitFieldname()
    {
		$columns = $this->qs->splitColumns("abc AS qqq, xyz, CONCAT('abc', 'der', 10+22, MYFUNC(x AS y, bla)) AS tst, mytable.`field1`, mytable.`field2` AS ad, `mytable`.field3 + 10", DB::SPLIT_IDENTIFIER);
    	$this->assertEquals(array(array("", "abc", "qqq"), array("", "xyz", ""), array("", "CONCAT('abc', 'der', 10+22, MYFUNC(x AS y, bla))", "tst"), array("mytable", "field1", ""), array("mytable", "field2", "ad"), array("", "`mytable`.field3 + 10", "")), $columns);
    }    
    
    public function testSplitColumns_Assoc()
    {
		$columns = $this->qs->splitColumns("abc AS qqq, xyz, CONCAT('abc', 'der', 10+22, MYFUNC(x AS y, bla)) AS tst, mytable.`field1`, adb.mytable.`field2` AS ad, `mytable`.field3 + 10", DB::SPLIT_ASSOC);
    	$this->assertEquals(array("qqq"=>"abc", "xyz"=>"xyz", "tst"=>"CONCAT('abc', 'der', 10+22, MYFUNC(x AS y, bla))", 'field1'=>"mytable.`field1`", 'ad'=>"adb.mytable.`field2`", '`mytable`.field3 + 10'=>"`mytable`.field3 + 10"), array_map('trim', $columns));
    }

    public function testSplitColumns_SplitFieldnameAssoc()
    {
		$columns = $this->qs->splitColumns("abc AS qqq, xyz, CONCAT('abc', 'der', 10+22, MYFUNC(x AS y, bla)) AS tst, mytable.`field1`, mytable.`field2` AS ad, `mytable`.field3 + 10", DB::SPLIT_IDENTIFIER | DB::SPLIT_ASSOC);
    	$this->assertEquals(array("qqq"=>array("", "abc", "qqq"), "xyz"=>array("", "xyz", ""), "tst"=>array("", "CONCAT('abc', 'der', 10+22, MYFUNC(x AS y, bla))", "tst"), 'field1'=>array("mytable", "field1", ""), 'ad'=>array("mytable", "field2", "ad"), '`mytable`.field3 + 10'=>array("", "`mytable`.field3 + 10", "")), $columns);
    }    
    
    public function testSplitColumns_Set()
    {
		$columns = $this->qs->splitColumns("SET @abc=18, def=CONCAT('test', '123', DATE_FORMAT(NOW(), '%d-%m-%Y %H:%M')), @uid=NULL");
    	$this->assertEquals(array("@abc=18", "def=CONCAT('test', '123', DATE_FORMAT(NOW(), '%d-%m-%Y %H:%M'))", "@uid=NULL"), array_map('trim', $columns));
    }

    public function testSplitColumns_Set_Assoc()
    {
		$columns = $this->qs->splitColumns("SET @abc=18, def=CONCAT('test', '123', DATE_FORMAT(NOW(), '%d-%m-%Y %H:%M')), @uid=NULL", DB::SPLIT_ASSOC);
    	$this->assertEquals(array("@abc"=>"18", "def"=>"CONCAT('test', '123', DATE_FORMAT(NOW(), '%d-%m-%Y %H:%M'))", "@uid"=>"NULL"), array_map('trim', $columns));
    }
    
    public function testSplitColumns_Select()
    {
		$columns = $this->qs->splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), test, 10+3 AS `bb`, 'Ho, Hi' AS HoHi, 22 FROM test INNER JOIN contact WHERE a='X FROM Y'");
    	$this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "test", "10+3 AS `bb`", "'Ho, Hi' AS HoHi", "22"), array_map('trim', $columns));
    }

    public function testSplitColumns_SelectSubquery()
    {
		$columns = $this->qs->splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), x IN (SELECT id FROM xy) AS subq FROM test");
    	$this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "x IN (SELECT id FROM xy) AS subq"), array_map('trim', $columns));
    }

    public function testSplitColumns_SelectSubFrom()
    {
		$columns = $this->qs->splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')) FROM test INNER JOIN (SELECT id, desc FROM xy) AS subq ON test.id = subq.id");
    	$this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))"), array_map('trim', $columns));
    }

    public function testSplitColumns_SelectRealLifeExample()
    {
		$columns = $this->qs->splitColumns("SELECT relation.id, IF( name = '', CONVERT( concat_name(last_name, suffix, first_name, '')USING latin1 ) , name ) AS fullname FROM relation LEFT JOIN relation_person_type ON relation.id = relation_person_type.relation_id LEFT JOIN person_type ON person_type.id = relation_person_type.person_type_id WHERE person_type_id =5 ORDER BY fullname");
    	$this->assertEquals(array("relation.id", "IF( name = '', CONVERT( concat_name(last_name, suffix, first_name, '')USING latin1 ) , name ) AS fullname"), array_map('trim', $columns));
    }

    public function testSplitColumns_InsertValues()
    {
    	$columns = $this->qs->splitColumns("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)");
    	$this->assertEquals(array('`id`', 'description', '`values`'), array_map('trim', $columns));
    }

    public function testSplitColumns_InsertSelect()
    {
    	$columns = $this->qs->splitColumns("INSERT INTO `test` (`id`, description, `values`) SELECT product_id, title, 22 AS values FROM `abc`");
    	$this->assertEquals(array('`id`', 'description', '`values`'), array_map('trim', $columns));
    }

    public function testSplitColumns_InsertSet()
    {
    	$columns = $this->qs->splitColumns("INSERT INTO `test` SET id=1, description='test', `values`=22", DB::SPLIT_ASSOC);
    	$this->assertEquals(array('id'=>'1', "description"=>"'test'", 'values'=>'22'), array_map('trim', $columns));
    }

    public function testSplitColumns_Update()
    {
    	$columns = $this->qs->splitColumns("UPDATE `test` INNER JOIN `xyz` ON test.id=xyz.test_id SET description='test', `values`=22 WHERE test.id=1", DB::SPLIT_ASSOC);
    	$this->assertEquals(array("description"=>"'test'", 'values'=>'22'), array_map('trim', $columns));
    }

    public function testSplitColumns_Delete()
    {
    	$columns = $this->qs->splitColumns("DELETE test.* FROM `test` INNER JOIN `xyz` ON test.id=xyz.test_id");
    	$this->assertEquals(array("test.*"), array_map('trim', $columns));
    }
    
    // -------
    
    
    public function testSplitTables_Simple()
    {
		$tables = $this->qs->splitTables("abc, xyz, test");
    	$this->assertEquals(array("abc", "xyz", "test"), array_map('trim', $tables));
    }

    public function testSplitTables_DBAlias()
    {
		$tables = $this->qs->splitTables("abc `a`, `xyz`, mysql.test AS tt");
    	$this->assertEquals(array("abc `a`", "`xyz`", "mysql.test AS tt"), array_map('trim', $tables));
    }

    public function testSplitTables_DBAlias_splitTablename()
    {
		$tables = $this->qs->splitTables("abc `a`, `xyz`, mysql.test AS tt", DB::SPLIT_IDENTIFIER);
    	$this->assertEquals(array(array(null, "abc", "a"), array(null, "xyz", null), array("mysql", "test", "tt")), $tables);
    }
    
    public function testSplitTables_Join()
    {
		$tables = $this->qs->splitTables("abc `a` INNER JOIN ufd.zzz AS `xyz` ON abc.id = xyz.abc_id LEFT JOIN def ON abc.x IN (SELECT abc FROM `xyz_link`) AND abc.y = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf, qwerty");
    	$this->assertEquals(array("abc `a`", "ufd.zzz AS `xyz`", "def", "tuf", "qwerty"), array_map('trim', $tables));
    }

    public function testSplitTables_Subjoin()
    {
		$tables = $this->qs->splitTables("abc `a` INNER JOIN (ufd.zzz AS `xyz` LEFT JOIN def ON abc.x IN (SELECT abc FROM `xyz_link`) AND abc.y = def.id, qwerty) ON abc.id = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf");
    	$this->assertEquals(array("abc `a`", "ufd.zzz AS `xyz`", "def", "qwerty", "tuf"), array_map('trim', $tables));
    }

    public function testSplitTables_Subquery()
    {
		$tables = $this->qs->splitTables("abc `a` INNER JOIN (SELECT * FROM ufd.zzz AS `xyz` LEFT JOIN def ON abc.y = def.id, qwerty) AS xyz ON abc.id = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf");
    	$this->assertEquals(array("abc `a`", "(SELECT * FROM ufd.zzz AS `xyz` LEFT JOIN def ON abc.y = def.id, qwerty) AS xyz", "tuf"), array_map('trim', $tables));
    }
    
    public function testSplitTables_Select()
    {
		$tables = $this->qs->splitTables("SELECT aaa, zzz FROM abc `a` INNER JOIN ufd.zzz AS `xyz` ON abc.id = xyz.abc_id LEFT JOIN def ON abc.x IN (SELECT abc FROM `xyz_link`) AND abc.y = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf, qwerty WHERE a='X FROM Y'");
    	$this->assertEquals(array("abc `a`", "ufd.zzz AS `xyz`", "def", "tuf", "qwerty"), array_map('trim', $tables));
    }
	
    public function testSplitTables_InsertValues()
    {
    	$tables = $this->qs->splitTables("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)");
    	$this->assertEquals(array('`test`'), array_map('trim', $tables));
    }

    public function testSplitTables_InsertSelect()
    {
    	$tables = $this->qs->splitTables("INSERT INTO `test` (`id`, description, `values`) SELECT product_id, title, 22 AS values FROM `abc`");
    	$this->assertEquals(array('`test`'), array_map('trim', $tables));
    }

    public function testSplitTables_InsertSet()
    {
    	$tables = $this->qs->splitTables("INSERT INTO `test` SET id=1, description='test', `values`=22");
    	$this->assertEquals(array('`test`'), array_map('trim', $tables));
    }

    public function testSplitTables_Update()
    {
    	$tables = $this->qs->splitTables("UPDATE `test` INNER JOIN `xyz` ON test.id=xyz.test_id SET description='test', `values`=22 WHERE test.id=1");
    	$this->assertEquals(array('`test`', '`xyz`'), array_map('trim', $tables));
    }

    public function testSplitTables_Delete()
    {
    	$tables = $this->qs->splitTables("DELETE test.* FROM `test` INNER JOIN `xyz` ON test.id=xyz.test_id");
    	$this->assertEquals(array("`test`", '`xyz`'), array_map('trim', $tables));    
    }
    
    // -------
	
    
    public function testSplitCriteria_Simple()
    {
    	$criteria = $this->qs->splitCriteria("abc=22 AND def > xyz OR test IS NOT TRUE");
    	$this->assertEquals(array(array('abc', '=', '22'), array('def', '>', 'xyz'), array('test', 'IS NOT', 'TRUE')), $criteria);
    }
    
    public function testSplitCriteria_Advanced()
    {
    	$criteria = $this->qs->splitCriteria("abc='test or 22' AND func(abc) < 77 AND (def > dor XOR test IS NOT TRUE) AND (`date` between '2009-01-01' AND NOW() + INTERVAL 1 MONTH OR `date`  IS  NULL  OR (`and` IN ('qq', 'or')))");
    	$this->assertEquals(array(array('abc', '=', "'test or 22'"), array('func(abc)', '<', '77'), array('def', '>', 'dor'), array('test', 'IS NOT', 'TRUE'), array('`date`', 'BETWEEN', "'2009-01-01' AND NOW() + INTERVAL 1 MONTH"), array('`date`', 'IS NULL', ''), array('`and`', 'IN', "('qq', 'or')")), $criteria);
    }

    public function testSplitCriteria_Subquery()
    {
    	$criteria = $this->qs->splitCriteria("abc=22 AND (def > xyz OR test IS NOT TRUE) AND `type` IN (SELECT `type` FROM `something` WHERE a>1 AND b<2)");
    	$this->assertEquals(array(array('abc', '=', '22'), array('def', '>', 'xyz'), array('test', 'IS NOT', 'TRUE'), array('`type`', 'IN', "(SELECT `type` FROM `something` WHERE a>1 AND b<2)")), $criteria);
    }
    
    public function testSplitCriteria_Where()
    {
		$criteria = $this->qs->splitCriteria("SELECT aaa, zzz FROM `atable` WHERE abc='test or 22' AND (def > dor XOR test IS NOT TRUE) AND (`date` between '2009-01-01' AND NOW() + INTERVAL 1 MONTH OR `date`  IS  NULL  OR (`and` IN ('qq', 'or'))) GROUP BY `group` HAVING count(*) > 5 AND AVG(total) > 50");
    	$this->assertEquals(array(array('abc', '=', "'test or 22'"), array('def', '>', 'dor'), array('test', 'IS NOT', 'TRUE'), array('`date`', 'BETWEEN', "'2009-01-01' AND NOW() + INTERVAL 1 MONTH"), array('`date`', 'IS NULL', ''), array('`and`', 'IN', "('qq', 'or')")), $criteria);
    }
    
    public function testSplitCriteria_Having()
    {
		$criteria = $this->qs->splitCriteria("SELECT aaa, zzz FROM `atable` WHERE abc='test or 22' AND (def > dor XOR test IS NOT TRUE) AND (`date` between '2009-01-01' AND NOW() + INTERVAL 1 MONTH OR `date`  IS  NULL  OR (`and` IN ('qq', 'or'))) GROUP BY `group` HAVING count(*) > 5 AND AVG(total) > 50", DB::HAVING);
    	$this->assertEquals(array(array("count(*)", ">", "5"), array("AVG(total)", ">", "50")), $criteria);
    }
    
    
    public function testSplitJoinOn_Simple()
    {
    	$criteria = $this->qs->splitJoinOn("abc LEFT JOIN `def` ON abc.id = `def`.abc_id INNER JOIN klm ON klm.type != 'ON' AND abc.klm_id = klm.id");
    	$this->assertEquals(array(array("abc.id", "=", "`def`.abc_id"), array("klm.type", "!=", "'ON'"), array("abc.klm_id", "=", "klm.id")), $criteria);
    }
    
    public function testSplitJoinOn_Advanced()
    {
    	$criteria = $this->qs->splitJoinOn("abc LEFT JOIN (`def` INNER JOIN klm ON klm.type != 'ON' AND abc.klm_id = klm.id) ON abc.id = `def`.abc_id AND (def.x IS NULL OR (def.x between 10 AND 20 AND def.y IN ('a', 'b', 'c')))");
    	$this->assertEquals(array(array("klm.type", "!=", "'ON'"), array("abc.klm_id", "=", "klm.id"), array("abc.id", "=", "`def`.abc_id"), array('def.x', 'IS NULL', ''), array('def.x', 'BETWEEN', '10 AND 20'), array('def.y', 'IN', "('a', 'b', 'c')")), $criteria);
    }

    public function testSplitJoinOn_Subquery()
    {
    	$criteria = $this->qs->splitJoinOn("abc LEFT JOIN (SELECT def.*, count(*) as cnt FROM `def` INNER JOIN klm ON klm.type != 'ON' AND abc.klm_id = klm.id GROUP BY `def`.`id`) AS `def` ON abc.id = `def`.abc_id AND (def.x IS NULL OR (def.x between 10 AND 20 AND def.y IN ('a', 'b', 'c')))");
    	$this->assertEquals(array(array("abc.id", "=", "`def`.abc_id"), array('def.x', 'IS NULL', ''), array('def.x', 'BETWEEN', '10 AND 20'), array('def.y', 'IN', "('a', 'b', 'c')")), $criteria);
    }

    public function testSplitJoinOn_Select()
    {
    	$criteria = $this->qs->splitJoinOn("SELECT * FROM abc LEFT JOIN (`def` INNER JOIN klm ON klm.type != 'ON' AND abc.klm_id = klm.id) ON abc.id = `def`.abc_id AND (def.x IS NULL OR (def.x between 10 AND 20 AND def.y IN ('a', 'b', 'c'))) WHERE xyz = 10");
    	$this->assertEquals(array(array("klm.type", "!=", "'ON'"), array("abc.klm_id", "=", "klm.id"), array("abc.id", "=", "`def`.abc_id"), array('def.x', 'IS NULL', ''), array('def.x', 'BETWEEN', '10 AND 20'), array('def.y', 'IN', "('a', 'b', 'c')")), $criteria);
    }

    public function testSplitJoinOn_Update()
    {
    	$criteria = $this->qs->splitJoinOn("UPDATE abc LEFT JOIN (`def` INNER JOIN klm ON klm.type != 'ON' AND abc.klm_id = klm.id) ON abc.id = `def`.abc_id AND (def.x IS NULL OR (def.x between 10 AND 20 AND def.y IN ('a', 'b', 'c'))) SET some='thing' WHERE xyz = 10");
    	$this->assertEquals(array(array("klm.type", "!=", "'ON'"), array("abc.klm_id", "=", "klm.id"), array("abc.id", "=", "`def`.abc_id"), array('def.x', 'IS NULL', ''), array('def.x', 'BETWEEN', '10 AND 20'), array('def.y', 'IN', "('a', 'b', 'c')")), $criteria);
    }
    
    public function testSplitJoinOn_Delete()
    {
    	$criteria = $this->qs->splitJoinOn("DELETE abc.* FROM abc LEFT JOIN (`def` INNER JOIN klm ON klm.type != 'ON' AND abc.klm_id = klm.id) ON abc.id = `def`.abc_id AND (def.x IS NULL OR (def.x between 10 AND 20 AND def.y IN ('a', 'b', 'c'))) WHERE xyz = 10");
    	$this->assertEquals(array(array("klm.type", "!=", "'ON'"), array("abc.klm_id", "=", "klm.id"), array("abc.id", "=", "`def`.abc_id"), array('def.x', 'IS NULL', ''), array('def.x', 'BETWEEN', '10 AND 20'), array('def.y', 'IN', "('a', 'b', 'c')")), $criteria);
    }
    
    //--------
    
    
    public function testExtractSubsets_Select()
    {
    	$set = $this->qs->extractSubsets("SELECT * FROM relatie WHERE status = 1");
    	$this->assertEquals(array("SELECT * FROM relatie WHERE status = 1"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }
    
    public function testExtractSubsets_SelectSubqueryInWhere()
    {
    	$set = $this->qs->extractSubsets("SELECT * FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$this->assertEquals(array("SELECT * FROM relatie WHERE id IN (#sub1) AND status = 1", "SELECT relatie_id FROM relatie_groep"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }
    
    public function testExtractSubsets_SelectSubqueryInJoin()
    {
    	$set = $this->qs->extractSubsets("SELECT * FROM relatie LEFT JOIN (SELECT relatie_id, COUNT(*) FROM contactpersoon) AS con_cnt ON relatie.id = con_cnt.relatie_id WHERE id IN (SELECT relatie_id FROM relatie_groep STRAIGHT JOIN (SELECT y, COUNT(x) FROM xy GROUP BY y) AS xy) AND status = 1");
    	$this->assertEquals(array("SELECT * FROM relatie LEFT JOIN (#sub1) AS con_cnt ON relatie.id = con_cnt.relatie_id WHERE id IN (#sub2) AND status = 1", "SELECT relatie_id, COUNT(*) FROM contactpersoon", "SELECT relatie_id FROM relatie_groep STRAIGHT JOIN (#sub3) AS xy", "SELECT y, COUNT(x) FROM xy GROUP BY y"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }

    public function testExtractSubsets_Insert()
    {
    	$set = $this->qs->extractSubsets("INSERT INTO relatie_active SELECT * FROM relatie WHERE status = 1");
    	$this->assertEquals(array("INSERT INTO relatie_active #sub1", "SELECT * FROM relatie WHERE status = 1"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }

    public function testExtractSubsets_InsertSubqueryInWhere()
    {
    	$set = $this->qs->extractSubsets("INSERT INTO relatie_active SELECT * FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$this->assertEquals(array("INSERT INTO relatie_active #sub1", "SELECT * FROM relatie WHERE id IN (#sub2) AND status = 1", "SELECT relatie_id FROM relatie_groep"), array_map(array(__CLASS__, 'cleanQuery'), $set));
    }
    
    public function testExtractSplit_Select()
    {
		$part_sets = $this->qs->extractSplit("SELECT id, description FROM `test`");
    	$this->assertEquals(array(array(0=>'SELECT', 'columns'=>'id, description', 'from'=>'`test`', 'where'=>'', 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>'')), array_map(create_function('$parts', 'return array_map("trim", $parts);'), $part_sets));
    }
    
    public function testExtractSplit_SelectSubValues()
    {
    	$part_sets = $this->qs->extractSplit("SELECT id, description, VALUES(SELECT id, desc FROM subt WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs FROM `test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' ORDER BY abx.dd");
    	$this->assertEquals(array(array(0=>'SELECT', 'columns'=>"id, description, VALUES(#sub1) AS subs", 'from'=>"`test` INNER JOIN (#sub2) AS abc", 'where'=>"abc.x IN (1,2,3,6,7) AND qq!='(SELECT)'", 'group by'=>'', 'having'=>'', 'order by'=>'abx.dd', 'limit'=>'', 100=>''), array(0=>"SELECT", 'columns'=> "id, desc", 'from'=>"subt", 'where'=>"status='1'", 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>"CASCADE ON PARENT id = relatie_id"), array(0=>"SELECT", 'columns'=>"*", 'from'=>"abc", 'where'=>"i = 1", 'group by'=>"x", 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>'')), array_map(create_function('$parts', 'return array_map("trim", $parts);'), $part_sets));
    }

    public function testExtractTree()
    {
		$set = $this->qs->ExtractTree("SELECT * FROM relatie WHERE status = 1");
    	$this->assertEquals(array("SELECT * FROM relatie WHERE status = 1"), $set);
    }

    public function testExtractTree_SubValues()
    {
    	$set = $this->qs->ExtractTree("SELECT id, description, VALUES (SELECT categorie_id FROM relatie_categorie CASCADE ON relatie_id = relatie.id) AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$set[0] = self::cleanQuery($set[0]);
    	for ($i=1; $i<sizeof($set); $i++) $set[$i][1] = self::cleanQuery($set[$i][1]);
    	$this->assertEquals(array("SELECT id, description, relatie.id AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1", array('cat', "SELECT categorie_id, relatie_id AS `tree:join` FROM relatie_categorie WHERE relatie_id IN (?) ORDER BY relatie_id", DB::FETCH_VALUE, true)), $set);
    }

    public function testExtractTree_SubRows()
    {
    	$set = $this->qs->ExtractTree("SELECT id, description, ROWS(SELECT categorie_id, opmerking FROM relatie_categorie WHERE categorie_id != 2 CASCADE ON relatie_id = relatie.id) AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$set[0] = self::cleanQuery($set[0]);
    	for ($i=1; $i<sizeof($set); $i++) $set[$i][1] = self::cleanQuery($set[$i][1]);
    	$this->assertEquals(array("SELECT id, description, relatie.id AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1", array('cat', "SELECT categorie_id, opmerking, relatie_id AS `tree:join` FROM relatie_categorie WHERE (categorie_id != 2) AND relatie_id IN (?) ORDER BY relatie_id", DB::FETCH_ORDERED, true)), $set);
    }
    
    public function testExtractTree_FakeSubValues()
    {
    	$set = $this->qs->ExtractTree("SELECT id, description, 'VALUES (SELECT it)' AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$set[0] = self::cleanQuery($set[0]);
    	$this->assertEquals(array("SELECT id, description, 'VALUES (SELECT it)' AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1"), $set);
    }

    public function testExtractTree_WithBranch()
    {
    	$set = $this->qs->ExtractTree("SELECT categorie.id, categorie.titel, categorie.verkopen_op, ROWS(SELECT product.id, product.titel, product.omschrijving, ROWS(SELECT product.id, product.name FROM product INNER JOIN product_accessoire ON product.id = product_accessoire.product_id INNER JOIN product_accessoire_product ON product_accessoire.id = product_accessoire_product.product_accessoire_id CASCADE ON product_accessoire_product.product_id = product.id) AS accessoire FROM product WHERE product.accessoire = 0 CASCADE ON product.categorie_id = categorie.id) as product");
    	$set[0] = self::cleanQuery($set[0]);
    	for ($i=1; $i<sizeof($set); $i++) $set[$i][1] = self::cleanQuery($set[$i][1]);
    	$this->assertEquals(array("SELECT categorie.id, categorie.titel, categorie.verkopen_op, categorie.id AS product", array('product', "SELECT product.id, product.titel, product.omschrijving, ROWS(SELECT product.id, product.name FROM product INNER JOIN product_accessoire ON product.id = product_accessoire.product_id INNER JOIN product_accessoire_product ON product_accessoire.id = product_accessoire_product.product_accessoire_id CASCADE ON product_accessoire_product.product_id = product.id) AS accessoire, product.categorie_id AS `tree:join` FROM product WHERE (product.accessoire = 0) AND product.categorie_id IN (?) ORDER BY product.categorie_id", DB::FETCH_ORDERED, true)), $set);

        $subset = $this->qs->ExtractTree($set[1][1]);
        $subset[0] = self::cleanQuery($subset[0]);
    	for ($i=1; $i<sizeof($subset); $i++) $subset[$i][1] = self::cleanQuery($subset[$i][1]);
    	$this->assertEquals(array("SELECT product.id, product.titel, product.omschrijving, product.id AS accessoire, product.categorie_id AS `tree:join` FROM product WHERE (product.accessoire = 0) AND product.categorie_id IN (?) ORDER BY product.categorie_id", array('accessoire', "SELECT product.id, product.name, product_accessoire_product.product_id AS `tree:join` FROM product INNER JOIN product_accessoire ON product.id = product_accessoire.product_id INNER JOIN product_accessoire_product ON product_accessoire.id = product_accessoire_product.product_accessoire_id WHERE product_accessoire_product.product_id IN (?) ORDER BY product_accessoire_product.product_id", DB::FETCH_ORDERED, true)), $subset);
    }

    //--------

    
    public function testSelectStatement_AddColumn()
    {
    	$s = $this->statement("SELECT id, description FROM `test`");
    	$s->addColumn("abc");
		$this->assertEquals("SELECT id, description, `abc` FROM `test`", self::cleanQuery($s));
    }
    
    public function testSelectStatement_AddColumn_Prepend()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
    	$s->addColumn("abc", DB::PREPEND);
		$this->assertEquals("SELECT `abc`, id, description FROM `test`", self::cleanQuery($s));
    }
    
    public function testSelectStatement_AddColumn_Replace()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
    	$s->addColumn("abc", DB::REPLACE);
		$this->assertEquals("SELECT `abc` FROM `test`", self::cleanQuery($s));
    }

    public function testSelectStatement_AddTable()
    {
    	$s = $this->statement("SELECT id, description FROM `test` WHERE xy > 10");
    	$s->addTable("abc");
		$this->assertEquals("SELECT id, description FROM (`test`), `abc` WHERE xy > 10", self::cleanQuery($s));
    }

    public function testSelectStatement_AddTable_LeftJoin()
    {
    	$s = $this->statement("SELECT id, description FROM `test` WHERE xy > 10");
    	$s->addTable("abc", "LEFT JOIN", array("test.id", "abc.idTest"));
		$this->assertEquals("SELECT id, description FROM (`test`) LEFT JOIN `abc` ON `test`.`id` = `abc`.`idTest` WHERE xy > 10", self::cleanQuery($s));
    }

    public function testSelectStatement_AddTable_AsString()
    {
		$s = $this->statement("SELECT id, description FROM `test` LEFT JOIN x ON test.x_id = x.id");
    	$s->addTable("abc", "LEFT JOIN", "test.id = abc.idTest");
		$this->assertEquals("SELECT id, description FROM (`test` LEFT JOIN x ON test.x_id = x.id) LEFT JOIN `abc` ON `test`.`id` = `abc`.`idTest`", self::cleanQuery($s));
    }

    public function testSelectStatement_AddTable_StraightJoin()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
    	$s->addTable("abc", "STRAIGHT JOIN");
		$this->assertEquals("SELECT id, description FROM (`test`) STRAIGHT JOIN `abc`", self::cleanQuery($s));
    }

    public function testSelectStatement_AddTable_Replace()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
    	$s->addTable("abc", null, null, DB::REPLACE);
		$this->assertEquals("SELECT id, description FROM `abc`", self::cleanQuery($s));
    }

    public function testSelectStatement_AddTable_Prepend()
    {
		$s = $this->statement("SELECT id, description FROM `test` LEFT JOIN x ON test.x_id = x.id");
    	$s->addTable("abc", 'LEFT JOIN', "test.id = abc.idTest", DB::PREPEND);
		$this->assertEquals("SELECT id, description FROM `abc` LEFT JOIN (`test` LEFT JOIN x ON test.x_id = x.id) ON `test`.`id` = `abc`.`idTest`", self::cleanQuery($s));
    }
        
    public function testSelectStatement_Where_Simple()
    {
    	$s = $this->statement("SELECT id, description FROM `test`");
    	$s->where("status = 1");
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`status` = 1)", self::cleanQuery($s));
    }
        
    public function testSelectStatement_Where()
    {
		$s = $this->statement("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->where("status = 1");
    	$this->assertEquals("SELECT id, description FROM `test` WHERE (id > 10) AND (`status` = 1) GROUP BY type_id HAVING SUM(qty) > 10", self::cleanQuery($s));
    }
        
    public function testSelectStatement_Where_Prepend()
    {
		$s = $this->statement("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->where("status = 1", DB::PREPEND);
    	$this->assertEquals("SELECT id, description FROM `test` WHERE (`status` = 1) AND (id > 10) GROUP BY type_id HAVING SUM(qty) > 10", self::cleanQuery($s));
    }
        
    public function testSelectStatement_Where_Replace()
    {
		$s = $this->statement("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->where("status = 1", DB::REPLACE);
    	$s->where("xyz = 1");
    	$this->assertEquals("SELECT id, description FROM `test` WHERE (`status` = 1) AND (`xyz` = 1) GROUP BY type_id HAVING SUM(qty) > 10", self::cleanQuery($s));
    }
        
    public function testSelectStatement_Having()
    {
    	$s = $this->statement("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->where("status = 1", DB::HAVING);
    	$this->assertEquals("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING (SUM(qty) > 10) AND (`status` = 1)", self::cleanQuery($s));
    }

    public function testSelectStatement_GroupBy_Simple()
    {
    	$s = $this->statement("SELECT id, description FROM `test`");
    	$s->groupBy("parent_id");
		$this->assertEquals("SELECT id, description FROM `test` GROUP BY `parent_id`", self::cleanQuery($s));
    }

    public function testSelectStatement_GroupBy()
    {   
    	$s = $this->statement("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->groupBy("parent_id");
		$this->assertEquals("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id, `parent_id` HAVING SUM(qty) > 10", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_OrderBy_Simple()
    {
    	$s = $this->statement("SELECT id, description FROM `test`");
    	$s->orderBy("parent_id");
		$this->assertEquals("SELECT id, description FROM `test` ORDER BY `parent_id`", self::cleanQuery($s));
    }
    
    public function testSelectStatement_OrderBy()
    {
		$s = $this->statement("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10 ORDER BY xyz");
    	$s->orderBy("parent_id");
		$this->assertEquals("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10 ORDER BY `parent_id`, xyz", self::cleanQuery($s));
    }
    
    public function testSelectStatement_OrderBy_Append()
    {
		$s = $this->statement("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10 ORDER BY xyz");
    	$s->orderBy("parent_id", DB::APPEND);
		$this->assertEquals("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10 ORDER BY xyz, `parent_id`", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_Equals()
    {
    	$s = $this->statement("SELECT id, description FROM `test`");
    	$s->addCriteria("status", 1);
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`status` = 1)", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_GreatEq()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
		$s->addCriteria('id', 1, '>=');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`id` >= 1)", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_Or()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
    	$s->addCriteria(array('xyz', 'abc'), 10);
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`xyz` = 10 OR `abc` = 10)", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_In()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
		$s->addCriteria('xyz', array('a', 'b', 'c'));
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`xyz` IN (\"a\", \"b\", \"c\"))", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_Between()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
		$s->addCriteria('xyz', array(10, 12), 'BETWEEN');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`xyz` BETWEEN 10 AND 12)", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_BetweenXAndNull()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
		$s->addCriteria('xyz', array(10, null), 'BETWEEN');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`xyz` >= 10)", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_BetweenNullAndX()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
		$s->addCriteria('xyz', array(null, 12), 'BETWEEN');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`xyz` <= 12)", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_LikeWildcard()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
		$s->addCriteria('description', 'bea', 'LIKE%');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`description` LIKE \"bea%\")", self::cleanQuery($s));
    }   
    
    public function testSelectStatement_AddCriteria_WildcardLikeWildcard()
    {
		$s = $this->statement("SELECT id, description FROM `test`");
		$s->addCriteria('description', array('bean', 'arnold'), '%LIKE%');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (`description` LIKE \"%bean%\" OR `description` LIKE \"%arnold%\")", self::cleanQuery($s));
    } 

    public function testSelectStatement_Limit()
    {
    	$s = $this->statement("SELECT id, description FROM `test`");
    	$s->limit(10);
		$this->assertEquals("SELECT id, description FROM `test` LIMIT 10", self::cleanQuery($s));
    } 

    public function testSelectStatement_Limit_Replace()
    {
		$s = $this->statement("SELECT id, description FROM `test` LIMIT 12");
    	$s->limit(50, 30);
		$this->assertEquals("SELECT id, description FROM `test` LIMIT 50 OFFSET 30", self::cleanQuery($s));
    } 

    public function testSelectStatement_Limit_String()
    {
		$s = $this->statement("SELECT id, description FROM `test` LIMIT 12");
    	$s->limit("50 OFFSET 30");
		$this->assertEquals("SELECT id, description FROM `test` LIMIT 50 OFFSET 30", self::cleanQuery($s));
    }	
    
    //--------

    
    public function testInsertStatement_AddColumns()
    {
    	$s = $this->statement("INSERT INTO `test` SET description='abc', type_id=10");
    	$s->addColumn("abc=12");
		$this->assertEquals("INSERT INTO `test` SET description='abc', type_id=10, `abc`=12", self::cleanQuery($s));    	
    }

    public function testInsertStatement_AddValues_String()
    {
    	$s = $this->statement("INSERT INTO `test` VALUES (NULL, 'abc', 10)");
    	$s->addValues('DEFAULT, "xyz", 12');
		$this->assertEquals("INSERT INTO `test` VALUES (NULL, 'abc', 10), (DEFAULT, \"xyz\", 12)", self::cleanQuery($s));    	
    }

    public function testInsertStatement_AddValues_Array()
    {
		$s = $this->statement("INSERT INTO `test` VALUES (NULL, 'abc', 10)");
    	$s->addValues(array(null, 'xyz', 12));
		$this->assertEquals("INSERT INTO `test` VALUES (NULL, 'abc', 10), (DEFAULT, \"xyz\", 12)", self::cleanQuery($s));    	
    }
    

    public function testInsertSelectStatement_AddColumns()
    {
    	$s = $this->statement("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc");
    	$s->addColumn("xyz", 0, 1);
		$this->assertEquals("INSERT INTO `test` SELECT DEFAULT, description, type_id, `xyz` FROM abc", self::cleanQuery($s));    	
    }    
    
    public function testInsertSelectStatement_AddCriteria()
    {
    	$s = $this->statement("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc");
    	$s->addCriteria("status", 1);
		$this->assertEquals("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc WHERE (`status` = 1)", self::cleanQuery($s));
    }    
    
    public function testInsertSelectStatement_AddCriteria_Like()
    {
		$s = $this->statement("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc WHERE status = 1");
    	$s->addCriteria('description', 'qqq', 'LIKE%');
		$this->assertEquals("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc WHERE (status = 1) AND (`description` LIKE \"qqq%\")", self::cleanQuery($s));
    }
    
    //--------

    
    public function testUpdateStatement_AddColumns_Simple()
    {
    	$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
    	$s->addColumn("abc=12");
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10, `abc`=12", self::cleanQuery($s));
    }
        	
    public function testUpdateStatement_AddColumns()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10 WHERE xyz=10");
    	$s->addColumn("abc=12");
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10, `abc`=12 WHERE xyz=10", self::cleanQuery($s));    	
    }
        	
    public function testUpdateStatement_AddColumns_Replace()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10 WHERE xyz=10");
    	$s->addColumn("abc=12", DB::REPLACE);
		$this->assertEquals("UPDATE `test` SET `abc`=12 WHERE xyz=10", self::cleanQuery($s));    	
    }

    public function testUpdateStatement_AddTable()
    {
    	$s = $this->statement("UPDATE `test` SET description='abc', type_id=10 WHERE xy > 10");
    	$s->addTable("abc", "LEFT JOIN", array("test.id", "abc.idTest"));
		$this->assertEquals("UPDATE (`test`) LEFT JOIN `abc` ON `test`.`id` = `abc`.`idTest` SET description='abc', type_id=10 WHERE xy > 10", self::cleanQuery($s));
    }

    public function testUpdateStatement_AddTable_String()
    {
		$s = $this->statement("UPDATE `test` LEFT JOIN x ON test.x_id = x.id SET description='abc', type_id=10");
    	$s->addTable("abc", "LEFT JOIN", "test.id = abc.idTest");
		$this->assertEquals("UPDATE (`test` LEFT JOIN x ON test.x_id = x.id) LEFT JOIN `abc` ON `test`.`id` = `abc`.`idTest` SET description='abc', type_id=10", self::cleanQuery($s));
    }

    public function testUpdateStatement_AddTable_StraightJoin()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
    	$s->addTable("abc", "STRAIGHT JOIN");
		$this->assertEquals("UPDATE (`test`) STRAIGHT JOIN `abc` SET description='abc', type_id=10", self::cleanQuery($s));
    }

    public function testUpdateStatement_AddTable_Replace()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
    	$s->addTable("abc", null, null, DB::REPLACE);
		$this->assertEquals("UPDATE `abc` SET description='abc', type_id=10", self::cleanQuery($s));
    }

    public function testUpdateStatement_AddTable_Prepend()
    {
		$s = $this->statement("UPDATE `test` LEFT JOIN x ON test.x_id = x.id SET description='abc', type_id=10");
    	$s->addTable("abc", 'LEFT JOIN', "test.id = abc.idTest", DB::PREPEND);
		$this->assertEquals("UPDATE `abc` LEFT JOIN (`test` LEFT JOIN x ON test.x_id = x.id) ON `test`.`id` = `abc`.`idTest` SET description='abc', type_id=10", self::cleanQuery($s));
    }
    
    public function testUpdateStatement_Where_Simple()
    {
    	$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
    	$s->where("status = 1");
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (`status` = 1)", self::cleanQuery($s));
    }
    
    public function testUpdateStatement_Where()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10 WHERE id > 10");
    	$s->where("status = 1");
    	$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (id > 10) AND (`status` = 1)", self::cleanQuery($s));
    }
    
    public function testUpdateStatement_Where_Prepend()
    {
    	$s = $this->statement("UPDATE `test` SET description='abc', type_id=10 WHERE id > 10");
    	$s->where("status = 1", DB::PREPEND);
    	$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (`status` = 1) AND (id > 10)", self::cleanQuery($s));
    }
    
    public function testUpdateStatement_Where_Replace()
    {
    	$s = $this->statement("UPDATE `test` SET description='abc', type_id=10 WHERE id > 10");
    	$s->where("status = 1", DB::REPLACE);
    	$s->where("xyz = 1");
    	$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (`status` = 1) AND (`xyz` = 1)", self::cleanQuery($s));
    }

    public function testUpdateStatement_AddCriteria()
    {
    	$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
    	$s->addCriteria("status", 1);
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (`status` = 1)", self::cleanQuery($s));
    }

    public function testUpdateStatement_AddCriteria_Or()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
    	$s->addCriteria(array('xyz', 'abc'), 10);
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (`xyz` = 10 OR `abc` = 10)", self::cleanQuery($s));
    }

    public function testUpdateStatement_AddCriteria_Between()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
		$s->addCriteria('xyz', array(10, 12), 'BETWEEN');
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (`xyz` BETWEEN 10 AND 12)", self::cleanQuery($s));
    }

    public function testUpdateStatement_AddCriteria_LikeWildcard()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
		$s->addCriteria('description', 'bea', 'LIKE%');
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (`description` LIKE \"bea%\")", self::cleanQuery($s));
    }
    
    public function testUpdateStatement_Limit()
    {
    	$s = $this->statement("UPDATE `test` SET description='abc', type_id=10");
    	$s->limit(10);
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 LIMIT 10", self::cleanQuery($s));
    }
    
    public function testUpdateStatement_Limit_Replace()
    {
		$s = $this->statement("UPDATE `test` SET description='abc', type_id=10 LIMIT 12");
    	$s->limit(50, 30);
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 LIMIT 50 OFFSET 30", self::cleanQuery($s));
    }
    
    //--------

    
    public function testDeleteStatement_AddColumn()
    {
    	$s = $this->statement("DELETE FROM `test`");
    	$s->addColumn("test.*");
		$this->assertEquals("DELETE `test`.* FROM `test`", self::cleanQuery($s));
    }    

    public function testDeleteStatement_AddTable()
    {
    	$s = $this->statement("DELETE FROM `test`");
    	$s->addTable("abc", "LEFT JOIN", array("test.id", "abc.idTest"));
		$this->assertEquals("DELETE FROM (`test`) LEFT JOIN `abc` ON `test`.`id` = `abc`.`idTest`", self::cleanQuery($s));
    }    

    public function testDeleteStatement_AddTable_String()
    {
		$s = $this->statement("DELETE FROM `test` LEFT JOIN x ON test.x_id = x.id");
    	$s->addTable("abc", "LEFT JOIN", "test.id = abc.idTest");
		$this->assertEquals("DELETE FROM (`test` LEFT JOIN x ON test.x_id = x.id) LEFT JOIN `abc` ON `test`.`id` = `abc`.`idTest`", self::cleanQuery($s));
    }    

    public function testDeleteStatement_AddTable_StraightJoin()
    {
		$s = $this->statement("DELETE FROM `test`");
    	$s->addTable("abc", "STRAIGHT JOIN");
		$this->assertEquals("DELETE FROM (`test`) STRAIGHT JOIN `abc`", self::cleanQuery($s));
    }    

    public function testDeleteStatement_AddTable_Replace()
    {
		$s = $this->statement("DELETE FROM `test`");
    	$s->addTable("abc", null, null, DB::REPLACE);
		$this->assertEquals("DELETE FROM `abc`", self::cleanQuery($s));
    }    

    public function testDeleteStatement_AddTable_Prepend()
    {
		$s = $this->statement("DELETE FROM `test` LEFT JOIN x ON test.x_id = x.id");
    	$s->addTable("abc", 'LEFT JOIN', "test.id = abc.idTest", DB::PREPEND);
		$this->assertEquals("DELETE FROM `abc` LEFT JOIN (`test` LEFT JOIN x ON test.x_id = x.id) ON `test`.`id` = `abc`.`idTest`", self::cleanQuery($s));
    }
    
    public function testDeleteStatement_Where_Simple()
    {
    	$s = $this->statement("DELETE FROM `test`");
    	$s->where("status = 1");
		$this->assertEquals("DELETE FROM `test` WHERE (`status` = 1)", self::cleanQuery($s));
    }
    
    public function testDeleteStatement_Where()
    {
		$s = $this->statement("DELETE FROM `test` WHERE id > 10");
    	$s->where("status = 1");
    	$this->assertEquals("DELETE FROM `test` WHERE (id > 10) AND (`status` = 1)", self::cleanQuery($s));
    }
    
    public function testDeleteStatement_Where_Prepend()
    {
    	$s = $this->statement("DELETE FROM `test` WHERE id > 10");
    	$s->where("status = 1", DB::PREPEND);
    	$this->assertEquals("DELETE FROM `test` WHERE (`status` = 1) AND (id > 10)", self::cleanQuery($s));
    }
    
    public function testDeleteStatement_Where_Replace()
    {
    	$s = $this->statement("DELETE FROM `test` WHERE id > 10");
    	$s->where("status = 1", DB::REPLACE);
    	$s->where("xyz = 1");
    	$this->assertEquals("DELETE FROM `test` WHERE (`status` = 1) AND (`xyz` = 1)", self::cleanQuery($s));
    }

    public function testDeleteStatement_AddCriteria()
    {
    	$s = $this->statement("DELETE FROM `test`");
    	$s->addCriteria("status", 1);
		$this->assertEquals("DELETE FROM `test` WHERE (`status` = 1)", self::cleanQuery($s));
    }

    public function testDeleteStatement_AddCriteria_Or()
    {
		$s = $this->statement("DELETE FROM `test`");
		$s->addCriteria(array('xyz', 'abc'), 10);
		$this->assertEquals("DELETE FROM `test` WHERE (`xyz` = 10 OR `abc` = 10)", self::cleanQuery($s));
    }

    public function testDeleteStatement_AddCriteria_Between()
    {
		$s = $this->statement("DELETE FROM `test`");
		$s->addCriteria('xyz', array(10, 12), 'BETWEEN');
		$this->assertEquals("DELETE FROM `test` WHERE (`xyz` BETWEEN 10 AND 12)", self::cleanQuery($s));
    }

    public function testDeleteStatement_AddCriteria_LikeWildcard()
    {
		$s = $this->statement("DELETE FROM `test`");
		$s->addCriteria('description', 'bea', 'LIKE%');
		$this->assertEquals("DELETE FROM `test` WHERE (`description` LIKE \"bea%\")", self::cleanQuery($s));
    }
    
    public function testDeleteStatement_Limit()
    {
    	$s = $this->statement("DELETE FROM `test`");
    	$s->limit(10);
		$this->assertEquals("DELETE FROM `test` LIMIT 10", self::cleanQuery($s));
    }
    
    public function testDeleteStatement_Limit_Replace()
    {
		$s = $this->statement("DELETE FROM `test` LIMIT 12");
    	$s->limit(50, 30);
		$this->assertEquals("DELETE FROM `test` LIMIT 50 OFFSET 30", self::cleanQuery($s));
    }
}
