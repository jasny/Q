<?php
use Q\DB, Q\DB_MySQL_QuerySplitter, Q\DB_SQLStatement;

require_once __DIR__ . '/../../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/DB/MySQL/QuerySplitter.php';
require_once 'Q/DB/SQLStatement.php';

class Test_DB_MySQL_QuerySplitter extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    //--------

	/**
	 * Database connection
	 * @var DB_MySQL
	 */
	public $conn;    
    
	/**
	 * Query splitter
	 * @var DB_MySQL_QuerySplitter
	 */
	public $qs;
	
    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name=null, array $data=array(), $dataName='')
    {
        $this->qs = new DB_MySQL_QuerySplitter();        
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Create a query statement objects
     *
     * @param string $statment
     * @return DB_SQLStatement
     */
    public function prepare($statement)
    {
        $s = new DB_SQLStatement(null, $statement);
        $s->querySplitter = $this->qs;
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

    /**
     * Test parsing null into the query statement
     */
    public function testParse_Null()
    {
        $this->assertEquals('UPDATE phpunit_test SET description=NULL', $this->qs->parse('UPDATE phpunit_test SET description=?', array(null)));
    }
    
    /**
     * Test parsing a number / boolean into the query statement
     */
    public function testParse_Numeric()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE status=10', $this->qs->parse("SELECT * FROM phpunit_test WHERE status=?", array(10)));
        $this->assertEquals('SELECT * FROM phpunit_test WHERE status=33.7', $this->qs->parse("SELECT * FROM phpunit_test WHERE status=?", array(33.7)));

        $this->assertEquals('SELECT * FROM phpunit_test WHERE status=TRUE AND disabled=FALSE', $this->qs->parse("SELECT * FROM phpunit_test WHERE status=? AND disabled=?", array(true, false)));
    }

    /**
     * Test parsing a string into the query statement
     */
    public function testParse_String()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE status="ACTIVE"', $this->qs->parse('SELECT * FROM phpunit_test WHERE status=?', array('ACTIVE')));
        $this->assertEquals('SELECT id, "test" AS `desc` FROM phpunit_test WHERE status="ACTIVE"', $this->qs->parse('SELECT id, ? AS `desc` FROM phpunit_test WHERE status=?', array('test', 'ACTIVE')));

        $this->assertEquals('SELECT id, "?" AS `desc ?`, \'?\' AS x FROM phpunit_test WHERE status="ACTIVE"', $this->qs->parse('SELECT id, "?" AS `desc ?`, \'?\' AS x FROM phpunit_test WHERE status=?', array('ACTIVE', 'not me', 'not me', 'not me')));
        
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description="This is a \\"test\\""', $this->qs->parse('SELECT * FROM phpunit_test WHERE description=?', array('This is a "test"')));
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description="This is a \\"test\\"\\nWith another line"', $this->qs->parse('SELECT * FROM phpunit_test WHERE description=?', array('This is a "test"' . "\n" . 'With another line')));
    }

    /**
     * Test parsing a boolean value into the query statement
     */
    public function testParse_Array()
    {
        $this->assertEquals('SELECT * FROM phpunit_test WHERE description IN ("test", 10, FALSE, "another test")', $this->qs->parse('SELECT * FROM phpunit_test WHERE description IN (?)', array(array("test", 10, FALSE, "another test"))));
    }

	//--------
    
	public function testSplitSelectSimple()
    {
		$parts = $this->qs->split("SELECT id, description FROM `test`");
		$this->assertEquals(array(0=>'SELECT', 'columns'=>'id, description', 'from'=>'`test`', 'where'=>'', 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>''), array_map('trim', $parts));
    }

    public function testSplitSelectAdvanced()
    {
		$parts = $this->qs->split("SELECT DISTINCTROW id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing FROM `test` INNER JOIN abc ON test.id = abc.id WHERE test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE' GROUP BY my_dd HAVING COUNT(1+3+xyz) < 100 LIMIT 15, 30 FOR UPDATE");
    	$this->assertEquals(array(0=>'SELECT DISTINCTROW', 'columns'=>"id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing", 'from'=>"`test` INNER JOIN abc ON test.id = abc.id", 'where'=>"test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE'", 'group by'=>"my_dd", 'having'=>"COUNT(1+3+xyz) < 100", 'order by'=>'', 'limit'=>"15, 30", 100=>"FOR UPDATE"), array_map('trim', $parts));
    }
    
    public function testSplitSelectSubquery()
    {
		$parts = $this->qs->split("SELECT id, description, VALUES(SELECT id, desc FROM subt WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs FROM `test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' ORDER BY abx.dd");
    	$this->assertEquals(array(0=>'SELECT', 'columns'=>"id, description, VALUES(SELECT id, desc FROM subt WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs", 'from'=>"`test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc", 'where'=>"abc.x IN (1,2,3,6,7) AND qq!='(SELECT)'", 'group by'=>'', 'having'=>'', 'order by'=>'abx.dd', 'limit'=>'', 100=>''), array_map('trim', $parts));
    }

    public function testSplitSelectSubqueryMadness()
    {
		$parts = $this->qs->split("SELECT id, description, VALUES(SELECT id, desc FROM subt1 INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON subt1.id = subt2.p_id WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs FROM `test` INNER JOIN (SELECT * FROM abc INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON abc.id = subt2.p_id WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' AND x_id IN (SELECT id FROM x) ORDER BY abx.dd LIMIT 10");
    	$this->assertEquals(array(0=>'SELECT', 'columns'=>"id, description, VALUES(SELECT id, desc FROM subt1 INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON subt1.id = subt2.p_id WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs", 'from'=>"`test` INNER JOIN (SELECT * FROM abc INNER JOIN (SELECT id, p_id, desc FROM subt2 INNER JOIN (SELECT id, p_id, myfunct(a, b, c) FROM subt3 WHERE x = 10) AS subt3 ON subt2.id = subt3.p_id) AS subt2 ON abc.id = subt2.p_id WHERE i = 1 GROUP BY x) AS abc", 'where'=>"abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' AND x_id IN (SELECT id FROM x)", 'group by'=>'', 'having'=>'', 'order by'=>'abx.dd', 'limit'=>'10', 100=>''), array_map('trim', $parts));
    }

	public function testSplitSelectSemicolon()
    {
		$parts = $this->qs->split("SELECT id, description FROM `test`; Please ignore this");
    	$this->assertEquals(array(0=>'SELECT', 'columns'=>'id, description', 'from'=>'`test`', 'where'=>'', 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>''), array_map('trim', $parts));
    }
    
    
	public function testJoinSelectSimple()
    {
		$sql = $this->qs->join(array(0=>'SELECT', 'columns'=>'id, description', 'from'=>'`test`', 'where'=>'', 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>''));
    	$this->assertEquals("SELECT id, description FROM `test`", $sql);
    }

    public function testJoinSelectAdvanced()
    {
		$sql = $this->qs->join(array(0=>'SELECT DISTINCTROW', 'columns'=>"id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing", 'from'=>"`test` INNER JOIN abc ON test.id = abc.id", 'where'=>"test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE'", 'group by'=>"my_dd", 'having'=>"COUNT(1+3+xyz) < 100", 'order by'=>'', 'limit'=>"15, 30", 100=>"FOR UPDATE"));
    	$this->assertEquals("SELECT DISTINCTROW id, description, CONCAT(name, ' from ', city) AS `tman`, ` ORDER BY` as `order`, \"\" AS nothing FROM `test` INNER JOIN abc ON test.id = abc.id WHERE test.x = 'SELECT A FROM B WHERE C ORDER BY D GROUP BY E HAVING X PROCEDURE Y LOCK IN SHARE MODE' GROUP BY my_dd HAVING COUNT(1+3+xyz) < 100 LIMIT 15, 30 FOR UPDATE", $sql);
    }
    
    public function testJoinSelectSubquery()
    {
		$sql = $this->qs->join(array(0=>'SELECT', 'columns'=>"id, description", 'from'=>"`test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc", 'where'=>"abc.x IN (1,2,3,6,7) AND qq!='(SELECT)'", 'group by'=>'', 'having'=>'', 'order by'=>'abx.dd', 'limit'=>'', 100=>''));
    	$this->assertEquals("SELECT id, description FROM `test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' ORDER BY abx.dd", $sql);
    }
    
    
    //--------
    
    
    public function testSplitInsertValuesSimple()
    {
		$parts = $this->qs->split("INSERT INTO `test` VALUES (NULL, 'abc')");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>'', 'values'=>"(NULL, 'abc')", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

	public function testSplitReplaceValuesSimple()
    {
		$parts = $this->qs->split("REPLACE INTO `test` VALUES (NULL, 'abc')");
    	$this->assertEquals(array(0=>'REPLACE', 'into'=>'`test`', 'columns'=>'', 'values'=>"(NULL, 'abc')", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

	public function testSplitInsertValuesColumns()
    {
		$parts = $this->qs->split("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10)");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>"(`id`, description, `values`)", 'values'=>"(NULL, 'abc', 10)", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

	public function testSplitInsertValuesMultiple()
    {
		$parts = $this->qs->split("INSERT INTO `test` (`id`, description, `values`) VALUES (NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>"(`id`, description, `values`)", 'values'=>"(NULL, 'abc', 10), (NULL, 'bb', 20), (NULL, 'cde', 30)", 'on duplicate key update'=>''), array_map('trim', $parts));
    }
    
    
	public function testSplitInsertSetSimple()
    {
		$parts = $this->qs->split("INSERT INTO `test` SET `id`=NULL, description = 'abc'");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'set'=>"`id`=NULL, description = 'abc'", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

    
	public function testSplitInsertSelectSimple()
    {
		$parts = $this->qs->split("INSERT INTO `test` SELECT NULL, name FROM xyz");
    	$this->assertEquals(array(0=>'INSERT', 'into'=>'`test`', 'columns'=>'', 'query'=>"SELECT NULL, name FROM xyz", 'on duplicate key update'=>''), array_map('trim', $parts));
    }

	public function testSplitInsertSelectSubquery()
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


	public function testSplitUpdateSimple()
    {
		$parts = $this->qs->split("UPDATE `test` SET status='ACTIVE' WHERE id=10");
    	$this->assertEquals(array(0=>'UPDATE', 'tables'=>'`test`', 'set'=>"status='ACTIVE'", 'where'=>'id=10', 'limit'=>''), array_map('trim', $parts));
    }

	public function testSplitUpdateAdvanced()
    {
		$parts = $this->qs->split("UPDATE `test` LEFT JOIN atst ON `test`.id = atst.test_id SET fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE' WHERE id = 10");
    	$this->assertEquals(array(0=>'UPDATE', 'tables'=>'`test` LEFT JOIN atst ON `test`.id = atst.test_id', 'set'=>"fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE'", 'where'=>'id = 10', 'limit'=>''), array_map('trim', $parts));
    }
    
	public function testSplitUpdateSubquery()
    {
		$parts = $this->qs->split("UPDATE `test` LEFT JOIN (SELECT test_id, a, f, count(*) AS cnt FROM atst) AS atst ON `test`.id = atst.test_id SET fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE' WHERE id IN (SELECT id FROM whatever LIMIT 100)");
    	$this->assertEquals(array(0=>'UPDATE', 'tables'=>'`test` LEFT JOIN (SELECT test_id, a, f, count(*) AS cnt FROM atst) AS atst ON `test`.id = atst.test_id', 'set'=>"fld1=DEFAULT, afld = CONCAT(a, f, ' (SELECT TRANSPORT)'), status='ACTIVE'", 'where'=>'id IN (SELECT id FROM whatever LIMIT 100)', 'limit'=>''), array_map('trim', $parts));
    }    

    
    //--------

    
	public function testSplitDeleteSimple()
    {
		$parts = $this->qs->split("DELETE FROM `test` WHERE id=10");
    	$this->assertEquals(array(0=>'DELETE', 'columns'=>'', 'from'=>'`test`', 'where'=>'id=10', 'order by'=>'', 'limit'=>''), array_map('trim', $parts));
    }
        
	public function testSplitDeleteAdvanced()
    {
		$parts = $this->qs->split("DELETE `test`.* FROM `test` INNER JOIN `dude where is my car`.`import` ON dude_import ON `test`.ref = dude_import.ref WHERE dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10 ORDER BY xyz");
    	$this->assertEquals(array(0=>'DELETE', 'columns'=>'`test`.*', 'from'=>'`test` INNER JOIN `dude where is my car`.`import` ON dude_import ON `test`.ref = dude_import.ref', 'where'=>"dude_import.sql NOT LIKE '% on duplicate key update' AND status = 10", 'order by'=>'xyz', 'limit'=>''), array_map('trim', $parts));
    }

	public function testSplitDeleteSubquery()
    {
		$parts = $this->qs->split("DELETE `test`.* FROM `test` INNER JOIN (SELECT * FROM dude_import GROUP BY x_id WHERE status = 'OK' HAVING COUNT(*) > 1) AS dude_import ON `test`.ref = dude_import.ref WHERE status = 10");
    	$this->assertEquals(array(0=>'DELETE', 'columns'=>'`test`.*', 'from'=>"`test` INNER JOIN (SELECT * FROM dude_import GROUP BY x_id WHERE status = 'OK' HAVING COUNT(*) > 1) AS dude_import ON `test`.ref = dude_import.ref", 'where'=>"status = 10", 'order by'=>'', 'limit'=>''), array_map('trim', $parts));
    }

    
    //--------

    
    public function testSplitColumnsSimple()
    {
		$columns = $this->qs->splitColumns("abc, xyz, test");
    	$this->assertEquals(array("abc", "xyz", "test"), array_map('trim', $columns));
    }

    public function testSplitColumnsAdvanced()
    {
		$columns = $this->qs->splitColumns("abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), test, 10+3 AS `bb`, 'Ho, Hi' AS HoHi, 22");
    	$this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "test", "10+3 AS `bb`", "'Ho, Hi' AS HoHi", "22"), array_map('trim', $columns));
    }
	
    public function testSplitColumnsSelect()
    {
		$columns = $this->qs->splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), test, 10+3 AS `bb`, 'Ho, Hi' AS HoHi, 22 FROM test INNER JOIN contact WHERE a='X FROM Y'");
    	$this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "test", "10+3 AS `bb`", "'Ho, Hi' AS HoHi", "22"), array_map('trim', $columns));
    }

    public function testSplitColumnsSelectSubquery()
    {
		$columns = $this->qs->splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')), x IN (SELECT id FROM xy) AS subq FROM test");
    	$this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))", "x IN (SELECT id FROM xy) AS subq"), array_map('trim', $columns));
    }

    public function testSplitColumnsSelectSubFrom()
    {
		$columns = $this->qs->splitColumns("SELECT abc, CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q')) FROM test INNER JOIN (SELECT id, desc FROM xy) AS subq ON test.id = subq.id");
    	$this->assertEquals(array("abc", "CONCAT('abc', 'der', 10+22, IFNULL(`qq`, 'Q'))"), array_map('trim', $columns));
    }

    public function testSplitColumnsSelectRealLifeExample()
    {
		$columns = $this->qs->splitColumns("SELECT relation.id, IF( name = '', CONVERT( concat_name(last_name, suffix, first_name, '')USING latin1 ) , name ) AS fullname FROM relation LEFT JOIN relation_person_type ON relation.id = relation_person_type.relation_id LEFT JOIN person_type ON person_type.id = relation_person_type.person_type_id WHERE person_type_id =5 ORDER BY fullname");
    	$this->assertEquals(array("relation.id", "IF( name = '', CONVERT( concat_name(last_name, suffix, first_name, '')USING latin1 ) , name ) AS fullname"), array_map('trim', $columns));
    }

    public function testSplitColumns_SplitFieldname()
    {
		$columns = $this->qs->splitColumns("abc AS qqq, xyz, MYFUNC(x AS y, bla) AS tst, mytable.`field1`, mytable.`field2` AS ad, mytable.field3 + 10", true);
    	$this->assertEquals(array(array("", "abc", "qqq"), array("", "xyz", ""), array("", "MYFUNC(x AS y, bla)", "tst"), array("mytable", "field1", ""), array("mytable", "field2", "ad"), array("", "mytable.field3 + 10", "")), $columns);
    }    
    
    public function testSplitColumns_Assoc()
    {
		$columns = $this->qs->splitColumns("abc AS qqq, xyz, MYFUNC(x AS y, bla) AS tst, mytable.`field1`, adb.mytable.`field2` AS ad, mytable.field3 + 10", false, true);
    	$this->assertEquals(array("qqq"=>"abc", "xyz"=>"xyz", "tst"=>"MYFUNC(x AS y, bla)", 'field1'=>"mytable.`field1`", 'ad'=>"adb.mytable.`field2`", 'mytable.field3 + 10'=>"mytable.field3 + 10"), array_map('trim', $columns));
    }

    // -------
   
    public function testSplitTableSimple()
    {
		$tables = $this->qs->splitTables("abc, xyz, test");
    	$this->assertEquals(array("abc", "xyz", "test"), array_map('trim', $tables));
    }

    public function testSplitTableDBAlias()
    {
		$tables = $this->qs->splitTables("abc `a`, `xyz`, mysql.test AS tt");
    	$this->assertEquals(array("abc `a`", "`xyz`", "mysql.test AS tt"), array_map('trim', $tables));
    }

    public function testSplitTableDBAlias_splitTablename()
    {
		$tables = $this->qs->splitTables("abc `a`, `xyz`, mysql.test AS tt", true);
    	$this->assertEquals(array(array(null, "abc", "a"), array(null, "xyz", null), array("mysql", "test", "tt")), $tables);
    }
    
    public function testSplitTableJoin()
    {
		$tables = $this->qs->splitTables("abc `a` INNER JOIN ufd.zzz AS `xyz` ON abc.id = xyz.abc_id LEFT JOIN def ON abc.x IN (SELECT abc FROM `xyz_link`) AND abc.y = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf, qwerty");
    	$this->assertEquals(array("abc `a`", "ufd.zzz AS `xyz`", "def", "tuf", "qwerty"), array_map('trim', $tables));
    }

    public function testSplitTableSubjoin()
    {
		$tables = $this->qs->splitTables("abc `a` INNER JOIN (ufd.zzz AS `xyz` LEFT JOIN def ON abc.x IN (SELECT abc FROM `xyz_link`) AND abc.y = def.id, qwerty) ON abc.id = MYFUNCT(10, 12, xyz.abc_id) STRAIGHT_JOIN tuf");
    	$this->assertEquals(array("ufd.zzz AS `xyz`", "def", "qwerty", "abc `a`", "tuf"), array_map('trim', $tables));
    }

    //--------
    
    
    public function testExtractSelect()
    {
    	$set = $this->qs->extractSubsets("SELECT * FROM relatie WHERE status = 1");
    	$this->assertEquals(array("SELECT * FROM relatie WHERE status = 1"), $set);
    }
    
    public function testExtractSelectSubqueryInWhere()
    {
    	$set = $this->qs->extractSubsets("SELECT * FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$this->assertEquals(array("SELECT * FROM relatie WHERE id IN (#sub1) AND status = 1", "SELECT relatie_id FROM relatie_groep"), $set);
    }
    
    public function testExtractSelectSubqueryInJoin()
    {
    	$set = $this->qs->extractSubsets("SELECT * FROM relatie LEFT JOIN (SELECT relatie_id, COUNT(*) FROM contactpersoon) AS con_cnt ON relatie.id = con_cnt.relatie_id WHERE id IN (SELECT relatie_id FROM relatie_groep STRAIGHT JOIN (SELECT y, COUNT(x) FROM xy GROUP BY y) AS xy) AND status = 1");
    	$this->assertEquals(array("SELECT * FROM relatie LEFT JOIN (#sub1) AS con_cnt ON relatie.id = con_cnt.relatie_id WHERE id IN (#sub2) AND status = 1", "SELECT relatie_id, COUNT(*) FROM contactpersoon", "SELECT relatie_id FROM relatie_groep STRAIGHT JOIN (#sub3) AS xy", "SELECT y, COUNT(x) FROM xy GROUP BY y"), $set);
    }

    public function testExtractInsert()
    {
    	$set = $this->qs->extractSubsets("INSERT INTO relatie_active SELECT * FROM relatie WHERE status = 1");
    	$this->assertEquals(array("INSERT INTO relatie_active  #sub1", "SELECT * FROM relatie WHERE status = 1"), $set);
    }

    public function testExtractInsertSubqueryInWhere()
    {
    	$set = $this->qs->extractSubsets("INSERT INTO relatie_active SELECT * FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$this->assertEquals(array("INSERT INTO relatie_active  #sub1", "SELECT * FROM relatie WHERE id IN (#sub2) AND status = 1", "SELECT relatie_id FROM relatie_groep"), $set);
    }
    
    public function testExtractSplitSelect()
    {
		$part_sets = $this->qs->extractSplit("SELECT id, description FROM `test`");
    	$this->assertEquals(array(array(0=>'SELECT', 'columns'=>'id, description', 'from'=>'`test`', 'where'=>'', 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>'')), array_map(create_function('$parts', 'return array_map("trim", $parts);'), $part_sets));
    }
    
    public function testExtractSplitSelectSubValues()
    {
    	$part_sets = $this->qs->extractSplit("SELECT id, description, VALUES(SELECT id, desc FROM subt WHERE status='1' CASCADE ON PARENT id = relatie_id) AS subs FROM `test` INNER JOIN (SELECT * FROM abc WHERE i = 1 GROUP BY x) AS abc WHERE abc.x IN (1,2,3,6,7) AND qq!='(SELECT)' ORDER BY abx.dd");
    	$this->assertEquals(array(array(0=>'SELECT', 'columns'=>"id, description, VALUES(#sub1) AS subs", 'from'=>"`test` INNER JOIN (#sub2) AS abc", 'where'=>"abc.x IN (1,2,3,6,7) AND qq!='(SELECT)'", 'group by'=>'', 'having'=>'', 'order by'=>'abx.dd', 'limit'=>'', 100=>''), array(0=>"SELECT", 'columns'=> "id, desc", 'from'=>"subt", 'where'=>"status='1'", 'group by'=>'', 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>"CASCADE ON PARENT id = relatie_id"), array(0=>"SELECT", 'columns'=>"*", 'from'=>"abc", 'where'=>"i = 1", 'group by'=>"x", 'having'=>'', 'order by'=>'', 'limit'=>'', 100=>'')), array_map(create_function('$parts', 'return array_map("trim", $parts);'), $part_sets));
    }

    public function testExtractTree()
    {
		$set = $this->qs->ExtractTree("SELECT * FROM relatie WHERE status = 1");
    	$this->assertEquals(array("SELECT * FROM relatie WHERE status = 1"), $set);
    }

    public function testExtractTreeSubValues()
    {
    	$set = $this->qs->ExtractTree("SELECT id, description, VALUES (SELECT categorie_id FROM relatie_categorie CASCADE ON relatie_id = relatie.id) AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$set[0] = self::cleanQuery($set[0]);
    	for ($i=1; $i<sizeof($set); $i++) $set[$i][1] = self::cleanQuery($set[$i][1]);
    	$this->assertEquals(array("SELECT id, description, relatie.id AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1", array('cat', "SELECT categorie_id, relatie_id AS `tree:join` FROM relatie_categorie WHERE relatie_id IN (?) ORDER BY relatie_id", DB::FETCH_VALUE, true)), $set);
    }

    public function testExtractTreeSubRows()
    {
    	$set = $this->qs->ExtractTree("SELECT id, description, ROWS(SELECT categorie_id, opmerking FROM relatie_categorie WHERE categorie_id != 2 CASCADE ON relatie_id = relatie.id) AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$set[0] = self::cleanQuery($set[0]);
    	for ($i=1; $i<sizeof($set); $i++) $set[$i][1] = self::cleanQuery($set[$i][1]);
    	$this->assertEquals(array("SELECT id, description, relatie.id AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1", array('cat', "SELECT categorie_id, opmerking, relatie_id AS `tree:join` FROM relatie_categorie WHERE (categorie_id != 2) AND relatie_id IN (?) ORDER BY relatie_id", DB::FETCH_ORDERED, true)), $set);
    }
    
    public function testExtractTreeFakeSubValues()
    {
    	$set = $this->qs->ExtractTree("SELECT id, description, 'VALUES (SELECT it)' AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1");
    	$set[0] = self::cleanQuery($set[0]);
    	$this->assertEquals(array("SELECT id, description, 'VALUES (SELECT it)' AS cat, xyz FROM relatie WHERE id IN (SELECT relatie_id FROM relatie_groep) AND status = 1"), $set);
    }

    public function testExtractTreeWithBranch()
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

    
    public function testStatementSelectAddColumn()
    {
    	$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addColumn("abc");
		$this->assertEquals("SELECT id, description, abc FROM `test`", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementSelectAddColumnAppend()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addColumn("abc", DB::ADD_PREPEND);
		$this->assertEquals("SELECT abc, id, description FROM `test`", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementSelectAddColumnReplace()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addColumn("abc", DB::ADD_REPLACE);
		$this->assertEquals("SELECT abc FROM `test`", self::cleanQuery($s->getStatement()));
    }

    public function testStatementSelectAddTable()
    {
    	$s = $this->prepare("SELECT id, description FROM `test` WHERE xy > 10");
    	$s->addTable("abc", "test.id = abc.test_id");
		$this->assertEquals("SELECT id, description FROM (`test`) LEFT JOIN abc ON test.id = abc.test_id WHERE xy > 10", self::cleanQuery($s->getStatement()));
    }

    public function testStatementSelectAddTableAsString()
    {
		$s = $this->prepare("SELECT id, description FROM `test` LEFT JOIN x ON test.x_id = x.id");
    	$s->addTable("abc ON test.id = abc.test_id");
		$this->assertEquals("SELECT id, description FROM (`test` LEFT JOIN x ON test.x_id = x.id) LEFT JOIN abc ON test.id = abc.test_id", self::cleanQuery($s->getStatement()));
    }

    public function testStatementSelectAddTableStraightJoin()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addTable("abc", null, "STRAIGHT JOIN");
		$this->assertEquals("SELECT id, description FROM (`test`) STRAIGHT JOIN abc", self::cleanQuery($s->getStatement()));
    }

    public function testStatementSelectAddTableReplace()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addTable("abc", null, null, DB::ADD_REPLACE);
		$this->assertEquals("SELECT id, description FROM abc", self::cleanQuery($s->getStatement()));
    }

    public function testStatementSelectAddTablePrepend()
    {
		$s = $this->prepare("SELECT id, description FROM `test` LEFT JOIN x ON test.x_id = x.id");
    	$s->addTable("abc", "test.id = abc.test_id", 'LEFT JOIN', DB::ADD_PREPEND);
		$this->assertEquals("SELECT id, description FROM abc LEFT JOIN (`test` LEFT JOIN x ON test.x_id = x.id) ON test.id = abc.test_id", self::cleanQuery($s->getStatement()));
    }
        
    public function testStatementSelectAddWhereSimple()
    {
    	$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addWhere("status = 1");
		$this->assertEquals("SELECT id, description FROM `test` WHERE (status = 1)", self::cleanQuery($s->getStatement()));
    }
        
    public function testStatementSelectAddWhere()
    {
		$s = $this->prepare("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->addWhere("status = 1");
    	$this->assertEquals("SELECT id, description FROM `test` WHERE (id > 10) AND (status = 1) GROUP BY type_id HAVING SUM(qty) > 10", self::cleanQuery($s->getStatement()));
    }
        
    public function testStatementSelectAddWherePrepend()
    {
		$s = $this->prepare("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->addWhere("status = 1", DB::ADD_PREPEND);
    	$this->assertEquals("SELECT id, description FROM `test` WHERE (status = 1) AND (id > 10) GROUP BY type_id HAVING SUM(qty) > 10", self::cleanQuery($s->getStatement()));
    }
        
    public function testStatementSelectAddWhereReplace()
    {
		$s = $this->prepare("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->addWhere("status = 1", DB::ADD_REPLACE);
    	$s->addWhere("xyz = 1");
    	$this->assertEquals("SELECT id, description FROM `test` WHERE (status = 1) AND (xyz = 1) GROUP BY type_id HAVING SUM(qty) > 10", self::cleanQuery($s->getStatement()));
    }
        
    public function testStatementSelectAddHaving()
    {
    	$s = $this->prepare("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->addWhere("status = 1", DB::ADD_HAVING);
    	$this->assertEquals("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING (SUM(qty) > 10) AND (status = 1)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementSelectAddGroupBySimple()
    {
    	$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addGroupBy("parent_id");
		$this->assertEquals("SELECT id, description FROM `test` GROUP BY parent_id", self::cleanQuery($s->getStatement()));
    }

    public function testStatementSelectAddGroupBy()
    {   
    	$s = $this->prepare("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10");
    	$s->addGroupBy("parent_id");
		$this->assertEquals("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id, parent_id HAVING SUM(qty) > 10", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddOrderBySimple()
    {
    	$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addOrderBy("parent_id");
		$this->assertEquals("SELECT id, description FROM `test` ORDER BY parent_id", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementSelectAddOrderBy()
    {
		$s = $this->prepare("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10 ORDER BY xyz");
    	$s->addOrderBy("parent_id");
		$this->assertEquals("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10 ORDER BY parent_id, xyz", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementSelectAddOrderByAppend()
    {
		$s = $this->prepare("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10 ORDER BY xyz");
    	$s->addOrderBy("parent_id", DB::ADD_APPEND);
		$this->assertEquals("SELECT id, description FROM `test` WHERE id > 10 GROUP BY type_id HAVING SUM(qty) > 10 ORDER BY xyz, parent_id", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaEquals()
    {
    	$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addCriteria("status", 1);
		$this->assertEquals("SELECT id, description FROM `test` WHERE (status = 1)", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaGreatEq()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
		$s->addCriteria(0, 1, '>=');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (id >= 1)", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaOr()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->addCriteria(array('xyz', 'abc'), 10);
		$this->assertEquals("SELECT id, description FROM `test` WHERE (xyz = 10 OR abc = 10)", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaIn()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
		$s->addCriteria('xyz', array('a', 'b', 'c'));
		$this->assertEquals("SELECT id, description FROM `test` WHERE (xyz IN (\"a\", \"b\", \"c\"))", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaBetween()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
		$s->addCriteria('xyz', array(10, 12), 'BETWEEN');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (xyz BETWEEN 10 AND 12)", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaBetweenXAndNull()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
		$s->addCriteria('xyz', array(10, null), 'BETWEEN');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (xyz >= 10)", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaBetweenNullAndX()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
		$s->addCriteria('xyz', array(null, 12), 'BETWEEN');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (xyz <= 12)", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaLikeWildcard()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
		$s->addCriteria(1, 'bea', 'LIKE%');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (description LIKE \"bea%\")", self::cleanQuery($s->getStatement()));
    }   
    
    public function testStatementSelectAddCriteriaWildcardLikeWildcard()
    {
		$s = $this->prepare("SELECT id, description FROM `test`");
		$s->addCriteria(1, array('bean', 'arnold'), '%LIKE%');
		$this->assertEquals("SELECT id, description FROM `test` WHERE (description LIKE \"%bean%\" OR description LIKE \"%arnold%\")", self::cleanQuery($s->getStatement()));
    } 

    public function testStatementSelectSetLimit()
    {
    	$s = $this->prepare("SELECT id, description FROM `test`");
    	$s->setLimit(10);
		$this->assertEquals("SELECT id, description FROM `test` LIMIT 10", self::cleanQuery($s->getStatement()));
    } 

    public function testStatementSelectSetLimitReplace()
    {
		$s = $this->prepare("SELECT id, description FROM `test` LIMIT 12");
    	$s->setLimit(50, 30);
		$this->assertEquals("SELECT id, description FROM `test` LIMIT 50 OFFSET 30", self::cleanQuery($s->getStatement()));
    } 

    public function testStatementSelectSetLimitString()
    {
		$s = $this->prepare("SELECT id, description FROM `test` LIMIT 12");
    	$s->setLimit("50 OFFSET 30");
		$this->assertEquals("SELECT id, description FROM `test` LIMIT 50 OFFSET 30", self::cleanQuery($s->getStatement()));
    }	
    
    
    //--------

    
    public function testStatementInsertAddColumns()
    {
    	$s = $this->prepare("INSERT INTO `test` SET description='abc', type_id=10");
    	$s->addColumn("abc=12");
		$this->assertEquals("INSERT INTO `test` SET description='abc', type_id=10, abc=12", self::cleanQuery($s->getStatement()));    	
    }

    public function testStatementInsertAddValuesString()
    {
    	$s = $this->prepare("INSERT INTO `test` VALUES (NULL, 'abc', 10)");
    	$s->addValues("DEFAULT, \"xyz\", 12");
		$this->assertEquals("INSERT INTO `test` VALUES (NULL, 'abc', 10), (DEFAULT, \"xyz\", 12)", self::cleanQuery($s->getStatement()));    	
    }

    public function testStatementInsertAddValuesArray()
    {
		$s = $this->prepare("INSERT INTO `test` VALUES (NULL, 'abc', 10)");
    	$s->addValues(array(null, 'xyz', 12));
		$this->assertEquals("INSERT INTO `test` VALUES (NULL, 'abc', 10), (DEFAULT, \"xyz\", 12)", self::cleanQuery($s->getStatement()));    	
    }
    

    public function testStatementInsertSelectAddColumns()
    {
    	$s = $this->prepare("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc");
    	$s->addColumn("xyz", 0, 1);
		$this->assertEquals("INSERT INTO `test` SELECT DEFAULT, description, type_id, xyz FROM abc", self::cleanQuery($s->getStatement()));    	
    }    
    
    public function testStatementInsertSelectAddCriteria()
    {
    	$s = $this->prepare("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc");
    	$s->addCriteria("status", 1);
		$this->assertEquals("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc WHERE (status = 1)", self::cleanQuery($s->getStatement()));
    }    
    
    public function testStatementInsertSelectAddCriteriaLike()
    {
		$s = $this->prepare("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc WHERE status = 1");
    	$s->addCriteria(1, 'qqq', 'LIKE%');
		$this->assertEquals("INSERT INTO `test` SELECT DEFAULT, description, type_id FROM abc WHERE (status = 1) AND (description LIKE \"qqq%\")", self::cleanQuery($s->getStatement()));
    }
    
    //--------

    
    public function testStatementUpdateAddColumnsSimple()
    {
    	$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
    	$s->addColumn("abc=12");
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10, abc=12", self::cleanQuery($s->getStatement()));
    }
        	
    public function testStatementUpdateAddColumns()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10 WHERE xyz=10");
    	$s->addColumn("abc=12");
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10, abc=12 WHERE xyz=10", self::cleanQuery($s->getStatement()));    	
    }
        	
    public function testStatementUpdateAddColumnsReplace()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10 WHERE xyz=10");
    	$s->addColumn("abc=12", DB::ADD_REPLACE);
		$this->assertEquals("UPDATE `test` SET abc=12 WHERE xyz=10", self::cleanQuery($s->getStatement()));    	
    }

    public function testStatementUpdateAddTable()
    {
    	$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10 WHERE xy > 10");
    	$s->addTable("abc", "test.id = abc.test_id");
		$this->assertEquals("UPDATE (`test`) LEFT JOIN abc ON test.id = abc.test_id SET description='abc', type_id=10 WHERE xy > 10", self::cleanQuery($s->getStatement()));
    }

    public function testStatementUpdateAddTableString()
    {
		$s = $this->prepare("UPDATE `test` LEFT JOIN x ON test.x_id = x.id SET description='abc', type_id=10");
    	$s->addTable("abc ON test.id = abc.test_id");
		$this->assertEquals("UPDATE (`test` LEFT JOIN x ON test.x_id = x.id) LEFT JOIN abc ON test.id = abc.test_id SET description='abc', type_id=10", self::cleanQuery($s->getStatement()));
    }

    public function testStatementUpdateAddTableStraight()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
    	$s->addTable("abc", null, "STRAIGHT JOIN");
		$this->assertEquals("UPDATE (`test`) STRAIGHT JOIN abc SET description='abc', type_id=10", self::cleanQuery($s->getStatement()));
    }

    public function testStatementUpdateAddTableReplace()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
    	$s->addTable("abc", null, null, DB::ADD_REPLACE);
		$this->assertEquals("UPDATE abc SET description='abc', type_id=10", self::cleanQuery($s->getStatement()));
    }

    public function testStatementUpdateAddTablePrepend()
    {
		$s = $this->prepare("UPDATE `test` LEFT JOIN x ON test.x_id = x.id SET description='abc', type_id=10");
    	$s->addTable("abc", "test.id = abc.test_id", 'LEFT JOIN', DB::ADD_PREPEND);
		$this->assertEquals("UPDATE abc LEFT JOIN (`test` LEFT JOIN x ON test.x_id = x.id) ON test.id = abc.test_id SET description='abc', type_id=10", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementUpdateAddWhereSimple()
    {
    	$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
    	$s->addWhere("status = 1");
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (status = 1)", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementUpdateAddWhere()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10 WHERE id > 10");
    	$s->addWhere("status = 1");
    	$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (id > 10) AND (status = 1)", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementUpdateAddWherePrepend()
    {
    	$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10 WHERE id > 10");
    	$s->addWhere("status = 1", DB::ADD_PREPEND);
    	$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (status = 1) AND (id > 10)", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementUpdateAddWhereReplace()
    {
    	$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10 WHERE id > 10");
    	$s->addWhere("status = 1", DB::ADD_REPLACE);
    	$s->addWhere("xyz = 1");
    	$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (status = 1) AND (xyz = 1)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementUpdateAddCriteria()
    {
    	$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
    	$s->addCriteria("status", 1);
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (status = 1)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementUpdateAddCriteriaOr()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
    	$s->addCriteria(array('xyz', 'abc'), 10);
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (xyz = 10 OR abc = 10)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementUpdateAddCriteriaBetween()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
		$s->addCriteria('xyz', array(10, 12), 'BETWEEN');
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (xyz BETWEEN 10 AND 12)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementUpdateAddCriteriaLikeWildcard()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
		$s->addCriteria('description', 'bea', 'LIKE%');
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 WHERE (description LIKE \"bea%\")", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementUpdateSetLimit()
    {
    	$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10");
    	$s->setLimit(10);
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 LIMIT 10", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementUpdateSetLimitReplace()
    {
		$s = $this->prepare("UPDATE `test` SET description='abc', type_id=10 LIMIT 12");
    	$s->setLimit(50, 30);
		$this->assertEquals("UPDATE `test` SET description='abc', type_id=10 LIMIT 50 OFFSET 30", self::cleanQuery($s->getStatement()));
    }
    
    
    //--------

    
    public function testStatementDeleteAddColumn()
    {
    	$s = $this->prepare("DELETE FROM `test`");
    	$s->addColumn("test.*");
		$this->assertEquals("DELETE test.* FROM `test`", self::cleanQuery($s->getStatement()));
    }    

    public function testStatementDeleteAddTable()
    {
    	$s = $this->prepare("DELETE FROM `test`");
    	$s->addTable("abc", "test.id = abc.test_id");
		$this->assertEquals("DELETE FROM (`test`) LEFT JOIN abc ON test.id = abc.test_id", self::cleanQuery($s->getStatement()));
    }    

    public function testStatementDeleteAddTableString()
    {
		$s = $this->prepare("DELETE FROM `test` LEFT JOIN x ON test.x_id = x.id");
    	$s->addTable("abc ON test.id = abc.test_id");
		$this->assertEquals("DELETE FROM (`test` LEFT JOIN x ON test.x_id = x.id) LEFT JOIN abc ON test.id = abc.test_id", self::cleanQuery($s->getStatement()));
    }    

    public function testStatementDeleteAddTableStraight()
    {
		$s = $this->prepare("DELETE FROM `test`");
    	$s->addTable("abc", null, "STRAIGHT JOIN");
		$this->assertEquals("DELETE FROM (`test`) STRAIGHT JOIN abc", self::cleanQuery($s->getStatement()));
    }    

    public function testStatementDeleteAddTableReplace()
    {
		$s = $this->prepare("DELETE FROM `test`");
    	$s->addTable("abc", null, null, DB::ADD_REPLACE);
		$this->assertEquals("DELETE FROM abc", self::cleanQuery($s->getStatement()));
    }    

    public function testStatementDeleteAddTablePrepend()
    {
		$s = $this->prepare("DELETE FROM `test` LEFT JOIN x ON test.x_id = x.id");
    	$s->addTable("abc", "test.id = abc.test_id", 'LEFT JOIN', DB::ADD_PREPEND);
		$this->assertEquals("DELETE FROM abc LEFT JOIN (`test` LEFT JOIN x ON test.x_id = x.id) ON test.id = abc.test_id", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementDeleteAddWhereSimple()
    {
    	$s = $this->prepare("DELETE FROM `test`");
    	$s->addWhere("status = 1");
		$this->assertEquals("DELETE FROM `test` WHERE (status = 1)", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementDeleteAddWhere()
    {
		$s = $this->prepare("DELETE FROM `test` WHERE id > 10");
    	$s->addWhere("status = 1");
    	$this->assertEquals("DELETE FROM `test` WHERE (id > 10) AND (status = 1)", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementDeleteAddWherePrepend()
    {
    	$s = $this->prepare("DELETE FROM `test` WHERE id > 10");
    	$s->addWhere("status = 1", DB::ADD_PREPEND);
    	$this->assertEquals("DELETE FROM `test` WHERE (status = 1) AND (id > 10)", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementDeleteAddWhereReplace()
    {
    	$s = $this->prepare("DELETE FROM `test` WHERE id > 10");
    	$s->addWhere("status = 1", DB::ADD_REPLACE);
    	$s->addWhere("xyz = 1");
    	$this->assertEquals("DELETE FROM `test` WHERE (status = 1) AND (xyz = 1)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementDeleteAddCriteria()
    {
    	$s = $this->prepare("DELETE FROM `test`");
    	$s->addCriteria("status", 1);
		$this->assertEquals("DELETE FROM `test` WHERE (status = 1)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementDeleteAddCriteriaOr()
    {
		$s = $this->prepare("DELETE FROM `test`");
		$s->addCriteria(array('xyz', 'abc'), 10);
		$this->assertEquals("DELETE FROM `test` WHERE (xyz = 10 OR abc = 10)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementDeleteAddCriteriaBetween()
    {
		$s = $this->prepare("DELETE FROM `test`");
		$s->addCriteria('xyz', array(10, 12), 'BETWEEN');
		$this->assertEquals("DELETE FROM `test` WHERE (xyz BETWEEN 10 AND 12)", self::cleanQuery($s->getStatement()));
    }

    public function testStatementDeleteAddCriteriaLikeWildcard()
    {
		$s = $this->prepare("DELETE FROM `test`");
		$s->addCriteria('description', 'bea', 'LIKE%');
		$this->assertEquals("DELETE FROM `test` WHERE (description LIKE \"bea%\")", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementDeleteSetLimit()
    {
    	$s = $this->prepare("DELETE FROM `test`");
    	$s->setLimit(10);
		$this->assertEquals("DELETE FROM `test` LIMIT 10", self::cleanQuery($s->getStatement()));
    }
    
    public function testStatementDeleteSetLimitReplace()
    {
		$s = $this->prepare("DELETE FROM `test` LIMIT 12");
    	$s->setLimit(50, 30);
		$this->assertEquals("DELETE FROM `test` LIMIT 50 OFFSET 30", self::cleanQuery($s->getStatement()));
    }
}

if (PHPUnit_MAIN_METHOD == 'Test_DB_MySQL_QuerySplitter::main') Test_DB_MySQL_QuerySplitter::main();
?>
