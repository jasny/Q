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
ErrorDocument 403 "<h1>Not allowed<h1><p>You are not welcome</p>"
<Location /data>
  RewriteEngine On
  RewriteRule ^(.*)$ http://localhost:5984/$1 [P, QSA]
  
  <Limit GET HEAD>
    Require valid-user
  </Limit>
</Location>

# Default error log
ErrorLog /var/log/apache2/error.log
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
        
        // Clean up temp dir
        if (file_exists($this->tmpdir)) {
            foreach (scandir($this->tmpdir) as $file) {
                if ($file == '.' || $file == '..') continue;
                unlink($this->tmpdir . '/' . $file);
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
     * Get path to file in temporary directory
     * 
     * @param string $filename
     * @return string
     */
    protected function tmpfile($filename)
    {
        if (!file_exists($this->tmpdir)) mkdir($this->tmpdir);
        return $this->tmpdir . '/' . $filename;
    }
    
    
    /**
     * Tests HTTPd_DOMDocument constructor
     */
    public function test__construct()
    {
        $node = $this->dom->documentElement;
        
        $this->assertType('Q\HTTPd_DOMElement', $node);
        $this->assertTrue($node->isSection(), 'is section');
        $this->assertEquals($node->nodeName, '_');
        $this->assertFalse($node->hasAttributes(), 'has attributes');
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
     * Tests HTTPd_DOMDocument->getElementsByTagName()
     */
    public function testGetElementsByTagName()
    {
        // TODO Auto-generated HTTPd_DOMDocumentTest->testGetElementsByTagName()
        $this->markTestIncomplete("getElementsByTagName test not implemented");
        $this->dom->getElementsByTagName(/* parameters */);
    }
    
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with an empty string
     */
    public function testLoadString_Empty()
    {
        $this->dom->loadString("");

        $root = $this->dom->documentElement;
        $this->assertType('Q\HTTPd_DOMElement', $root);
        $this->assertEquals($root->nodeName, '_');
        $this->assertTrue($root->isSection(), "root is section");

        $node = $root->firstChild;
        $this->assertType('DOMText', $node, 'root newline');
        $this->assertEquals("\n", $node->nodeValue);
        
        $this->assertNull($node->nextSibling, "Child of root, after newline");
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with a single comment
     */
    public function testLoadString_Comment()
    {
        $this->dom->loadString("# This is a test");

        $node = $this->dom->documentElement->firstChild;
        $this->assertType('DOMText', $node, 'root newline');
        $this->assertEquals("\n", $node->nodeValue);

        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMComment', $node);
        $this->assertEquals(' This is a test', $node->nodeValue);
                        
        $this->assertNull($node->nextSibling, "Child of root, after comment");
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with a single directive
     */
    public function testLoadString_Directive()
    {
        $this->dom->loadString("ErrorLog /var/log/apache2/error.log");

        $node = $this->dom->documentElement->firstChild;
        $this->assertType('DOMText', $node, 'root newline');
        $this->assertEquals("\n", $node->nodeValue);

        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node);
        $this->assertEquals('ErrorLog', $node->nodeName);
        $this->assertFalse($node->isSection(), "ErrorLog is section");
        $this->assertEquals('/var/log/apache2/error.log', $node->getArgument(1));
                        
        $this->assertNull($node->nextSibling, "Child of root, after ErrorLog");
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with a single section
     */
    public function testLoadString_Section()
    {
        $this->dom->loadString("<Location /status>\n</Location>");

        $node = $this->dom->documentElement->firstChild;
        $this->assertType('DOMText', $node, 'root newline');
        $this->assertEquals("\n", $node->nodeValue);

        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node);
        $this->assertEquals('Location', $node->nodeName);
        $this->assertTrue($node->isSection(), "<Location> is section");
        $this->assertEquals('/status', $node->getArgument(1));
                
        $this->assertNull($node->nextSibling, "Child of root, after newline");
    }
    
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with a comment spreading on multiple lines
     */
    public function testLoadString_Comment_Multiline()
    {
        $this->dom->loadString("# This is a test\\\nOn multiple lines");

        $node = $this->dom->documentElement->firstChild;
        $this->assertType('DOMText', $node, 'root newline');
        $this->assertEquals("\n", $node->nodeValue);

        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMComment', $node);
        $this->assertEquals(" This is a test\nOn multiple lines", $node->nodeValue);
                        
        $this->assertNull($node->nextSibling, "Child of root, after comment");
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with a directive spreading on multiple lines
     */
    public function testLoadString_Directive_Multiline()
    {
        $this->dom->loadString("Redirect \\\n302 /goolge\\\nhttp://www.google.com");

        $node = $this->dom->documentElement->firstChild->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node);
        $this->assertEquals('Redirect', $node->nodeName);
        $this->assertEquals(302, $node->getArgument(1));
        $this->assertEquals('/goolge', $node->getArgument(2));
        $this->assertEquals('http://www.google.com', $node->getArgument(3));
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with a section spreading on multiple lines
     */
    public function testLoadString_Section_Multiline()
    {
        $this->dom->loadString("<Limit\\\nGET SET\\\nPUT>\n</Limit>");

        $node = $this->dom->documentElement->firstChild->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node);
        $this->assertEquals('Limit', $node->nodeName);
        $this->assertEquals('GET', $node->getArgument(1));
        $this->assertEquals('SET', $node->getArgument(2));
        $this->assertEquals('PUT', $node->getArgument(3));
        
        $this->assertNull($node->nextSibling, "Child of root, after newline");
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with an argument spreading on multiple lines
     */
    public function testLoadString_Argument_Multiline()
    {
        $this->dom->loadString("ErrorDocument 503 \"<h1>Unavailable</h1>\\\nSorry the site is down\"");

        $node = $this->dom->documentElement->firstChild->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node);
        $this->assertEquals('ErrorDocument', $node->nodeName);
        $this->assertEquals(503, $node->getArgument(1));
        $this->assertEquals("<h1>Unavailable</h1>\nSorry the site down", $node->getArgument(2));
        
        $this->assertNull($node->nextSibling, "Child of root, after comment");
    }
    
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with an incorrect close tag
     */
    public function testLoadString_IncorrectCloseTag()
    {
        $this->setExpectedException("DOMException", "Syntax error on line 2: Expected </Location> but saw </Typo>", DOM_SYNTAX_ERR);
        $this->dom->loadString("<Location /status>\n</Typo>");
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with an unexpected close tag
     */
    public function testLoadString_UnexpectedCloseTag()
    {
        $this->setExpectedException("DOMException", "Syntax error on line 1: </Location> without matching <Location> section", DOM_SYNTAX_ERR);
        $this->dom->loadString("</Location>");
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with an unclosed section
     */
    public function testLoadString_NoCloseTag()
    {
        $this->setExpectedException("DOMException", "Syntax error: <Location> was not closed.", DOM_SYNTAX_ERR);
        $this->dom->loadString("<Location /status>");
    }

    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with a syntax error
     */
    public function testLoadString_SyntaxError()
    {
        $this->setExpectedException("DOMException", "Syntax error on line 1: Invalid command '<Location /status'.", DOM_SYNTAX_ERR);
        $this->dom->loadString("<Location /status\n</Location>");
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
        $this->assertEquals('<h1>Not allowed<h1><p>You are not welcome</p>', $node->getArgument(2));
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, '<Location>');
        $this->assertEquals('Location', $node->nodeName);
        $this->assertTrue($node->isSection(), "<Location> is section");
        $this->assertEquals('/data', $node->getArgument(1));
        
        $node = $node->firstChild;
        $this->assertType('DOMText', $node, '<Location> newline');
        $this->assertEquals("\n", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, 'RewriteEngine indent');
        $this->assertEquals("  ", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, 'RewriteEngine');
        $this->assertEquals('RewriteEngine', $node->nodeName);
        $this->assertEquals('On', $node->getArgument(1));

        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, 'RewriteEngine indent');
        $this->assertEquals("  ", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, 'RewriteRule');
        $this->assertEquals('RewriteRule', $node->nodeName);
        $this->assertEquals('^(.*)$', $node->getArgument(1));
        $this->assertEquals('http://localhost:5984/$1', $node->getArgument(2));
        $this->assertEquals('[P, QSA]', $node->getArgument(3));
        
        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, 'RewriteRule blank');
        $this->assertEquals("  \n", $node->nodeValue);
        
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
        $this->assertType('Q\\HTTPd_DOMElement', $node, 'Require');
        $this->assertEquals('Require', $node->nodeName);
        $this->assertEquals('valid-user', $node->getArgument(1));
        
        $node = $node->nextSibling;
        $this->assertType('DOMText', $node, '</Limit> indent');
        $this->assertEquals("  ", $node->nodeValue);

        $this->assertNull($node->nextSibling, "Next sibbling after Require in <Limit>");

        $node = $node->parentNode;
        $this->assertNull($node->nextSibling, "Next sibbling after <Limit> in <Location>");
        
        $node = $node->parentNode->nextSibling;
        $this->assertType('DOMText', $node, '</Location> blank');
        $this->assertEquals("\n", $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMComment', $node);
        $this->assertEquals(' Default error log', $node->nodeValue);
        
        $node = $node->nextSibling;
        $this->assertType('Q\\HTTPd_DOMElement', $node, 'ErrorLog');
        $this->assertEquals('ErrorLog', $node->nodeName);
        $this->assertEquals('/var/log/apache2/error.log', $node->getArgument(1));

        $this->assertNull($node->nextSibling, "Next sibbling after ErrorLog");
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() with test configuration
     */
    public function testLoadString()
    {
        $this->dom->loadString($this->conf);
        $this->doLoadTest();
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->loadString() replacing the existing nodes
     */
    public function testLoadString_ReplaceExisting()
    {
        $this->dom->loadString("ErrorLog /var/log/apache2/error.log");
        $node = $this->dom->documentElement->firstChild->nextSibling;
        $this->assertEquals("ErrorLog", $node->nodeName);
        $this->assertEquals("/var/log/apache2/error.log", $node->getArgument(1));
        
        $this->dom->loadString("RewriteEngine On");
        $node = $this->dom->documentElement->firstChild->nextSibling;
        $this->assertEquals("RewriteEngine", $node->nodeName);
        $this->assertEquals("On", $node->getArgument(1));
    }
    
    /**
     * Tests HTTPd_HTTPd_DOMDocument->load() with test configuration
     */
    public function testLoad()
    {
        $file = $this->tmpfile(__FUNCTION__ . ".conf");
        file_put_contents($file, $this->conf);
        
        $this->dom->load($file);
        $this->doLoadTest();
    }
    
    
    /**
     * Tests HTTPd_DOMDocument->saveString()
     */
    public function testSaveString()
    {
        $this->dom->loadString($this->conf);
        $conf = $this->dom->saveString();
        $this->assertEquals($this->conf, $conf);
    }

    /**
     * Tests HTTPd_DOMDocument->save()
     */
    public function testSave()
    {
        $file = $this->tmpfile(__FUNCTION__ . ".conf");

        $this->dom->loadString($this->conf);
        $conf = $this->dom->save($file);
        $this->assertEquals($this->conf, file_get_contents($file));
    }

    /**
     * Tests HTTPd_DOMDocument->save(), saving back to original file
     */
    public function testSave_ToOriginal()
    {
        $file = $this->tmpfile(__FUNCTION__ . ".conf");
        file_put_contents($file, $this->conf);
        
        $this->dom->load($file);
        $conf = $this->dom->save();
        $this->assertEquals($this->conf, file_get_contents($file));
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
     * Tests HTTPd_DOMDocument->validate()
     */
    public function testValidate()
    {
        $this->assertTrue($this->dom->validate());
    }

    /**
     * Tests HTTPd_DOMDocument->hasAttributes()
     */
    public function testHasAttributes()
    {
        $this->assertFalse($this->dom->hasAttributes());
    }
}
