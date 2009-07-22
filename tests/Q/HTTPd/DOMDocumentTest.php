<?php
use Q\HTTPd_DOMDocument, Q\HTTPd_DOMAttr, Q\HTTPd_DOMElement, Q\HTTPd_DOMComment;

require_once 'TestHelper.php';

require_once 'Q/HTTPd/DOMDocument.php';

/**
 * HTTPd_DOMDocument test case.
 */
class HTTPd_DOMDocumentTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var HTTPd_DOMDocument
     */
    private $dom;
    
    /**
     * Temporary directory
     * @var string
     */
    private $tmpdir;

    /**
     * Test configuration.
     * If you change this, also change function doLoadTest()
     * 
     * @var string
     */
    protected $conf = <<<CONF
ErrorDocument 403 "/not-welcome.html"
<Location /status>
  ServerStatus On
  
  <Limit GET HEAD>
    Allow from localhost
  </Limit>
</Location>

ErrorLog '/var/log/apache2/error.log'
CONF;
    
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->dom = new HTTPd_DOMDocument();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->dom = null;
        
        if (file_exists($this->tmpdir)) {
            foreach (scandir($this->tmpdir) as $file) {
                if ($file == '.' || $file == '..') continue;
                unlink($file);
            }
            rmdir($this->tmpdir);
        }
        
        parent::tearDown();
    }

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->tmpdir = dirname(dirname(__DIR__)) . '/tmp/' . __CLASS__;
    }
    
    /**
     * Tests HTTPd_DOMDocument->createAttribute()
     */
    public function testCreateAttribute()
    {
        $node = $this->dom->createAttribute("a3");
        
        $this->assertType('Q\HTTPd_DOMAttr', $node);
        $this->assertEquals($node->nodeName, 'a3');
    }

    /**
     * Tests HTTPd_DOMDocument->createArgument()
     */
    public function testCreateArgument()
    {
        $node = $this->dom->createArgument(3);
        
        $this->assertType('Q\HTTPd_DOMAttr', $node);
        $this->assertEquals($node->nodeName, 'a3');
        $this->assertEquals($node->nodeValue, '', 'node value');
    }
    
    /**
     * Tests HTTPd_DOMDocument->createArgument() with value
     */
    public function testCreateArgument_WithValue()
    {
        $node = $this->dom->createArgument(2, '/var/www');
        
        $this->assertType('Q\HTTPd_DOMAttr', $node);
        $this->assertEquals($node->nodeName, 'a2');
        $this->assertEquals($node->nodeValue, '/var/www');
    }
    
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->createDirective()
     */
    public function testCreateDirective()
    {
        $node = $this->dom->createDirective("NoSuchDirective");
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertFalse($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, 'NoSuchDirective');
        
        $this->assertFalse($node->hasAttributes(), 'has attributes');
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->createDirective() with one argument
     */
    public function testCreateDirective_WithOneArg()
    {
        $node = $this->dom->createDirective("RewriteEngine", 'ON');
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertFalse($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, 'RewriteEngine');
        
        $this->assertTrue($node->hasAttributes(), 'has attributes');
        $this->assertEquals($node->getArgument(1), 'ON');
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->createDirective() with arguments
     */
    public function testCreateDirective_WithArgs()
    {
        $node = $this->dom->createDirective("Redirect", '503', '/forum');
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertFalse($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, 'Redirect');
        
        $this->assertTrue($node->hasAttributes(), 'has attributes');
        $this->assertEquals($node->getArgument(1), '503');
        $this->assertEquals($node->getArgument(2), '/forum');
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->createDirective() with arguments as array
     */
    public function testCreateDirective_WithArgsArray()
    {
        $node = $this->dom->createDirective("Redirect", array('503' , '/forum'));
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertFalse($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, 'Redirect');
        
        $this->assertTrue($node->hasAttributes(), 'has attributes');
        $this->assertEquals($node->getArgument(1), '503');
        $this->assertEquals($node->getArgument(2), '/forum');
    }

    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->createSection()
     */
    public function testCreateSection()
    {
        $node = $this->dom->createSection("NoSuchSection");
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertTrue($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, 'NoSuchSection');
        
        $this->assertFalse($node->hasAttributes(), 'has attributes');
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->createSection() with one argument
     */
    public function testCreateSection_WithOneArg()
    {
        $node = $this->dom->createSection("Directory", '/var/www');
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertTrue($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, 'Directory');
        
        $this->assertTrue($node->hasAttributes(), 'has attributes');
        $this->assertEquals($node->getArgument(1), '/var/www');
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->createSection() with arguments
     */
    public function testCreateSection_WithArgs()
    {
        $node = $this->dom->createSection("Limit", 'POST', 'PUT');
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertTrue($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, 'Limit');
        
        $this->assertTrue($node->hasAttributes(), 'has attributes');
        $this->assertEquals($node->getArgument(1), 'POST');
        $this->assertEquals($node->getArgument(2), 'PUT');
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->createSection() with arguments as array
     */
    public function testCreateSection_WithArgsArray()
    {
        $node = $this->dom->createSection("Limit", array('POST' , 'PUT'));
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertTrue($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, 'Limit');
        
        $this->assertTrue($node->hasAttributes(), 'has attributes');
        $this->assertEquals($node->getArgument(1), 'POST');
        $this->assertEquals($node->getArgument(2), 'PUT');
    }

    /**
     * Tests HTTPd_DOMDocument->createComment()
     */
    public function testCreateComment()
    {
        $node = $this->dom->createComment("Very important comment");
        
        $this->assertType('Q\HTTPd_DOMComment', $node);
        $this->assertEquals("Very important comment", $node->nodeValue);
    }
    
    
    /**
     * Tests HTTPd_DOMDocument->getElementsByTagName()
     */
    public function testGetElementsByTagName()
    {
        // TODO Auto-generated HTTPd_DOMDocumentTest->testGetElementsByTagName()
        $this->markTestIncomplete("getElementsByTagName test not implemented");
        $this->dom->getElementsByTagName(/* parameters */);
    }
    
    
    /**
     * Test based on the test configuration.
     */
    protected function doLoadTest()
    {
        $root = $this->dom->documentElement;        
        $this->assertType('Q\\HTTPd_DOMElement', $root, 'root node');
        $this->assertTrue($root->isSection(), "root node is section");
        $this->assertEquals('_', $root->nodeName);
        
        $node = $root->firstChild;
        $this->assertType('DOMText', $node, 'root newline');
        $this->assertEquals("\n", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, 'ErrorDocument');
        $this->assertEquals('ErrorDocument', $node->nodeName);
        $this->assertFalse($node->isSection(), "ErrorDocument is section");
        $this->assertEquals(403, $node->getArgument(1));
        $this->assertEquals('/not-welcome.html', $node->getArgument(2));
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, '<Location>');
        $this->assertEquals('Location', $node->nodeName);
        $this->assertTrue($node->isSection(), "<Location> is section");
        $this->assertEquals('/status', $node->getArgument(1));
        
        $node = $node->firstChild;
        $this->assertType('DOMText', $node, '<Location> newline');
        $this->assertEquals("\n", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, 'ServerStatus indent');
        $this->assertEquals("  ", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, 'ServerStatus');
        $this->assertEquals('ServerStatus', $node->nodeName);
        $this->assertEquals('On', $node->getArgument(1));
        
        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, 'ServerStatus blank');
        $this->assertEquals("\n  ", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, '<Limit> indent');
        $this->assertEquals("  ", $node->nodeValue);
                
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, '<Limit>');
        $this->assertEquals('Limit', $node->nodeName);
        $this->assertEquals('GET', $node->getArgument(1));
        $this->assertEquals('HEAD', $node->getArgument(2));
        
        $node = $node->firstChild;
        $this->assertType('DOMText', $node, '<Limit> newline');
        
        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, 'Allow indent');
        $this->assertEquals("    ", $node->nodeValue);
                
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, 'Allow');
        $this->assertEquals('Allow', $node->nodeName);
        $this->assertEquals('from', $node->getArgument(1));
        $this->assertEquals('localhost', $node->getArgument(2));
        
        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, '</Limit> indent');
        $this->assertEquals("  ", $node->nodeValue);

        $this->assertNull($node->nextSibling, "Next sibbling of <Limit>, after Allow");

        $node = $node->parentNode;
        $this->assertNull($node->nextSibling, "Next sibbling of <Location>, after <Limit>");
        
        $node = $node->parentNode->nextSibling;
        $this->assertType('DOMText', $node, '</Location> blank');
        $this->assertEquals("\n", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, 'ErrorLog');
        $this->assertEquals('ErrorLog', $node->nodeName);
        $this->assertEquals('/var/log/apache2/error.log', $node->getArgument(1));

        $this->assertNull($node->nextSibling, "Next sibbling of document, after ErrorLog");
    }
    
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString()
     */
    public function testLoadString()
    {
        $this->dom->loadString($this->conf);
        $this->doLoadTest();
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->load()
     */
    public function testLoad()
    {
        $file = $this->tmpdir . "/" . __FUNCTION__ . ".conf";
        file_put_contents($file, $this->conf);
        
        $this->dom->load($file);
        $this->doLoadTest();
    }
    
    
    /**
     * Tests HTTPd_DOMDocument->save()
     */
    public function testSave()
    {
        // TODO Auto-generated HTTPd_DOMDocumentTest->testSave()
        $this->markTestIncomplete("save test not implemented");
        $this->dom->save(/* parameters */);
    }

    /**
     * Tests HTTPd_DOMDocument->saveString()
     */
    public function testSaveString()
    {
        // TODO Auto-generated HTTPd_DOMDocumentTest->testSave()
        $this->markTestIncomplete("save test not implemented");
        $this->dom->saveString(/* parameters */);
    }

    
    /**
     * Tests HTTPd_DOMDocument->createDocumentFragment()
     */
    public function testCreateDocumentFragment()
    {
        // TODO Auto-generated HTTPd_DOMDocumentTest->testCreateDocumentFragment()
        $this->markTestIncomplete("createDocumentFragment test not implemented");
        $this->dom->validate(/* parameters */);
    }
    
    /**
     * Tests HTTPd_DOMDocument->validate()
     */
    public function testValidate()
    {
        // TODO Auto-generated HTTPd_DOMDocumentTest->testValidate()
        $this->markTestIncomplete("validate test not implemented");
        $this->dom->validate(/* parameters */);
    }

    
    // ===== Test cases for inherited methods =====

    /**
     * Tests HTTPd_DOMDocument->createElement()
     */
    public function testCreateElement()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->createElement("test");
    }

    /**
     * Tests HTTPd_DOMDocument->createTextNode()
     */
    public function testCreateTextNode()
    {
        $node = $this->dom->createTextNode("test");
        $this->assertType('DOMText', $node);
        $this->assertEquals("test", $node->nodeValue);
    }

    /**
     * Tests HTTPd_DOMDocument->createCDATASection()
     */
    public function testCreateCDATASection()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->createCDATASection("test");
    }

    /**
     * Tests HTTPd_DOMDocument->createProcessingInstruction()
     */
    public function testCreateProcessingInstruction()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->createProcessingInstruction("test", "test");
    }

    /**
     * Tests HTTPd_DOMDocument->createEntityReference()
     */
    public function testCreateEntityReference()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->createEntityReference("test");
    }

    /**
     * Tests HTTPd_DOMDocument->importNode()
     */
    public function testImportNode()
    {
        $node = $this->dom->importNode(new HTTPd_DOMAttr('test'));
        $this->assertSame($this->dom, $node->ownerDocument);
    }

    /**
     * Tests HTTPd_DOMDocument->createElementNS()
     */
    public function testCreateElementNS()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->createElementNS("test");
    }

    /**
     * Tests HTTPd_DOMDocument->createAttributeNS()
     */
    public function testCreateAttributeNS()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->createAttributeNS("test");
    }

    /**
     * Tests HTTPd_DOMDocument->normalizeDocument()
     */
    public function testNormalizeDocument()
    {
        // TODO Auto-generated HTTPd_DOMDocumentTest->testNormalizeDocument()
        $this->markTestIncomplete("normalizeDocument test not implemented");
        $this->dom->normalizeDocument(/* parameters */);
    }

    /**
     * Tests HTTPd_DOMDocument->loadXML()
     */
    public function testLoadXML()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->loadXML("<h1>Some XML</h1>");
    }

    /**
     * Tests HTTPd_DOMDocument->saveXML()
     */
    public function testSaveXML()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->saveXML();
    }

    /**
     * Tests HTTPd_DOMDocument->xinclude()
     */
    public function testXinclude()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->xinclude();
    
    }

    /**
     * Tests HTTPd_DOMDocument->loadHTML()
     */
    public function testLoadHTML()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->loadHTML("<h1>Some HTML</h1>");
    }

    /**
     * Tests HTTPd_DOMDocument->loadHTMLFile()
     */
    public function testLoadHTMLFile()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->loadHTMLFile("/dev/null");
    }

    /**
     * Tests HTTPd_DOMDocument->saveHTML()
     */
    public function testSaveHTML()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->saveHTML();
    }

    /**
     * Tests HTTPd_DOMDocument->saveHTMLFile()
     */
    public function testSaveHTMLFile()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->saveHTMLFile("/dev/null");
    }

    /**
     * Tests HTTPd_DOMDocument->schemaValidate()
     */
    public function testSchemaValidate()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->schemaValidate("/dev/null");
    }

    /**
     * Tests HTTPd_DOMDocument->schemaValidateSource()
     */
    public function testSchemaValidateSource()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->schemaValidateSource("<xsd:schema>Not really</xsd:schema>");
    }

    /**
     * Tests HTTPd_DOMDocument->relaxNGValidate()
     */
    public function testRelaxNGValidate()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->relaxNGValidate("/dev/null");
    }

    /**
     * Tests HTTPd_DOMDocument->relaxNGValidateSource()
     */
    public function testRelaxNGValidateSource()
    {
        $this->setExpectedException('DOMException', null, DOM_NOT_SUPPORTED_ERR);
        $this->dom->relaxNGValidateSource("Relax");
    }

    /**
     * Tests HTTPd_DOMDocument->hasAttributes()
     */
    public function testHasAttributes()
    {
        $this->assertFalse($this->dom->hasAttributes());
    }
}
