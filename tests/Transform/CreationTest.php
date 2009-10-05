<?php
use Q\Transform;

require_once 'TestHelper.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Transform.php';

/**
 * Test factory method
 */
class Transform_CreationTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    /**
     * Test driver xsl
     */
    public function testDriverXSL()
    {
        $transform = Transform::with('xsl');
        $this->assertType('Q\Transform_XSL', $transform);
    }
    
    /**
     * Test driver replace
     */
    public function testDriverReplace()
    {
        $transform = Transform::with('replace');
        $this->assertType('Q\Transform_Replace', $transform);
    }
    
    /**
     * Test driver php
     */
    public function testDriverPHP()
    {
        $transform = Transform::with('php');
        $this->assertType('Q\Transform_PHP', $transform);
    }

    /**
     * Test driver Text2HTML
     */
    public function testDriverText2HTML()
    {
        $transform = Transform::with('text2html');
        $this->assertType('Q\Transform_Text2HTML', $transform);
    }
    
    /**
     * Test driver HTML2Text
     */
    public function testDriverHTML2Text()
    {
        $transform = Transform::with('html2text');
        $this->assertType('Q\Transform_HTML2Text', $transform);
    }
    
    /**
     * Test driver unserialize_xml
     */
    public function testDriverUnserializeXML()
    {
        $transform = Transform::with('unserialize-xml');
        $this->assertType('Q\Transform_Unserialize_XML', $transform);
    }
    /**
     * Test driver serialize_xml
     */
    public function testDriverSerializeXML()
    {
        $transform = Transform::with('serialize-xml');
        $this->assertType('Q\Transform_Serialize_XML', $transform);
    }

    /**
     * Test driver unserialize_json
     */
    public function testDriverUnserializeJson()
    {
        $transform = Transform::with('unserialize-json');
        $this->assertType('Q\Transform_Unserialize_Json', $transform);
    }
    
    /**
     * Test driver serialize_json
     */
    public function testDriverSerializeJson()
    {
        $transform = Transform::with('serialize-json');
        $this->assertType('Q\Transform_Serialize_Json', $transform);
    }
    
    /**
     * Test driver unserialize_php
     */
    public function testDriverUnserializePHP()
    {
        $transform = Transform::with('unserialize-php');
        $this->assertType('Q\Transform_Unserialize_PHP', $transform);
    }

    /**
     * Test driver serialize_php
     */
    public function testDriverSerializePHP()
    {
        $transform = Transform::with('serialize-php');
        $this->assertType('Q\Transform_Serialize_PHP', $transform);
    }
    
    /**
     * Test driver unserialize_yaml
     */
    public function testDriverUnserializeYaml()
    {
        $transform = Transform::with('unserialize-yaml');
        $this->assertType('Q\Transform_Unserialize_Yaml', $transform);
    }
    
    /**
     * Test driver serialize_yaml
     */
    public function testDriverSerializeYaml()
    {
        $transform = Transform::with('serialize-yaml');
        $this->assertType('Q\Transform_Serialize_Yaml', $transform);
    }

    /**
     * Test driver unserialize_ini
     */
    public function testDriverUnserializeIni()
    {
        $transform = Transform::with('unserialize-ini');
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
    }
    
    /**
     * Test driver serialize_ini
     */
    public function testDriverSerializeIni()
    {
        $transform = Transform::with('serialize-ini');
        $this->assertType('Q\Transform_Serialize_Ini', $transform);
    }
    
    public function testOptions()
    {
        $transform = Transform::with('xsl', array('test' => 'TESTAREA'));
        $this->assertType('Q\Transform_XSL', $transform);
        
        $refl = new ReflectionProperty($transform, 'test');
        $refl->setAccessible(true);
        $test = $refl->getValue($transform);
        $this->assertEquals('TESTAREA', $test);
    }
    
}

