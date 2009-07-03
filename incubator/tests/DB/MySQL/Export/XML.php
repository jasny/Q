<?php
	//--------

	/**
	 * Helper function: Remove extra spaces from xml
	 *
	 * @param  string $xml
	 * @return string
	 */
    static function cleanXML($xml)
    {
    	return preg_replace('/(<\/\w+|\/)>\s+(?=<)|>\s+(?=<[^\/])/', '', $xml);
    }

    
	/**
     * Test query and get result as XML with columns as standard view
     */
    public function testResultGetXMLStandard()
    {
    	$expect = '<resultset><row><field name="id">1</field><field name="key">one</field><field name="description">first row</field><field name="status">ACTIVE</field></row><row><field name="id">2</field><field name="key">two</field><field name="description">next row</field><field name="status">ACTIVE</field></row></resultset>';
    	
    	$result = $this->conn->query("SELECT * FROM phpunit_test WHERE status='ACTIVE'");
    	$xml = $result->getXML(array('view'=>'standard'));
    	
    	$this->assertEquals($expect, self::cleanXML($xml));
    }
    
    /**
     * Test query and get result as XML with columns as attributes view
     */
    public function testResultGetXML()
    {
    	$expect = '<resultset><row id="1" key="one" description="first row" status="ACTIVE" /><row id="2" key="two" description="next row" status="ACTIVE" /></resultset>';
    	
    	$result = $this->conn->query("SELECT * FROM phpunit_test WHERE status='ACTIVE'");
    	$xml = $result->getXML(array('view'=>'attributes'));
    	
    	$this->assertEquals($expect, self::cleanXML($xml));
    }

	/**
     * Test query and get result as XML with columns as forest view
     */
    public function testResultGetXMLForest()
    {
    	$expect = '<people><person><id>1</id><key>one</key><description>first row</description><status>ACTIVE</status></person><person><id>2</id><key>two</key><description>next row</description><status>ACTIVE</status></person></people>';
    	
    	$result = $this->conn->query("SELECT * FROM phpunit_test WHERE status='ACTIVE'");
    	$xml = $result->getXML(array('view'=>'forest', 'root'=>'people', 'row'=>'person'));
    	
    	$this->assertEquals($expect, self::cleanXML($xml));
    }
    
    /**
     * Test query and return XML
     */
    public function testQueryXML()
    {
    	$expect = '<resultset><row><id>1</id><key>one</key><description>first row</description><status>ACTIVE</status></row><row><id>2</id><key>two</key><description>next row</description><status>ACTIVE</status></row></resultset>';
    	
    	$xml = $this->conn->query($this->getStatement('ACTIVE'))->getXML();
    	$this->assertEquals($expect, self::cleanXML($xml));
    }

?>