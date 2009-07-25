<?php
namespace Q;

require_once 'Q/misc.php';
require_once 'Q/HTTPd/DOMAttr.php';
require_once 'Q/HTTPd/DOMComment.php';
require_once 'Q/HTTPd/DOMElement.php';

/**
 * Represents an entire NCSA HTTPd configuration document; serves as the root of the document tree.
 * NCSA HTTPd configuration is used by the Apache HTTP server.
 * 
 * Don't use any namespace functionality.
 * 
 * @package HTTPd
 */
class HTTPd_DOMDocument extends \DOMDocument
{
	/**
	 * Class constructor
	 * 
	 * @return unknown_type
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->registerNodeClass('DOMAttr', 'Q\HTTPd_DOMAttr');
		$this->registerNodeClass('DOMElement', 'Q\HTTPd_DOMElement');
		$this->registerNodeClass('DOMComment', 'Q\HTTPd_DOMComment');
		
		$this->appendChild($this->createSection('_'));
	}

	
	/**
	 * This function creates a new instance of class Q\HTTPd_DOMAttr.
	 * This node will not show up in the document unless it is inserted with (e.g.) DOMNode->appendChild(). 
	 * 
	 * @param int    $position
	 * @param string $value  Attribute value
	 * @return HTTPd_DOMAttr
	 */	
	public function createArgument($position, $value=null)
	{
		$node = parent::createAttribute("a{$position}");
		$node->nodeValue = $value;
		return $node;
	}
	
	/**
	 * This function creates a new instance of class Q\HTTPd_DOMElement.
	 * This node will not show up in the document unless it is inserted with (e.g.) DOMNode->appendChild(). 
	 * 
	 * @param string $name
	 * @param string $value  Attribute value; may also be an array.
	 * @param Additional values may be passed for multiple attributes.
	 * @return HTTPd_DOMElement
	 */	
	public function createDirective($name, $value=null)
	{
		if (func_num_args() > 2) {
			$value = func_get_args();
			array_shift($value);
		}
		
		$node = parent::createElement($name);
		foreach ((array)$value as $v) $node->addArgument($v);
		return $node;
	}

	/**
	 * This function creates a new instance of class Q\HTTPd_DOMSection.
	 * This node will not show up in the document unless it is inserted with (e.g.) DOMNode->appendChild(). 
	 * 
	 * @param string $name
	 * @param string $value  Attribute value; may also be an array.
	 * @param Additional values may be passed for multiple attributes.
	 * @return HTTPd_DOMSection
	 */	
	public function createSection($name, $value=null)
	{
		if (func_num_args() > 2) {
			$value = func_get_args();
			array_shift($value);
		}
		
		$node = parent::createElement($name, "\n");
		foreach ((array)$value as $v) $node->addArgument($v);
		return $node;
	}

	
	/**
	 * Split arguments and add them to the node.
	 * 
	 * @param HTTPd_DOMElement $node
	 * @param string           $arglist  Unparsed arguments.
	 */
	protected function parseArguments(HTTPd_DOMElement $node, $arglist)
	{
		if (!preg_match_all('/\\[(?:[^"\'\]]++|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\')\\]|[^"\'\s]++|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'/s', str_replace("\\\n", "\n", $arglist), $matches, PREG_PATTERN_ORDER)) return;
		
		foreach ($matches[0] as $i=>$value) {
		    $node->addArgument(unquote($value));
		}
	}
	
	/**
	 * Load HTTPd configuration from a string.
	 * 
	 * @param string $contents  Contents of the configuration.
	 * @param string $filename
	 */
	public function loadConfiguration($contents, $filename=null)
	{
	    $of_file = isset($filename) ? " of $filename" : null;
	    
	    // Remove existing document
        parent::replaceChild($this->createSection('_'), $this->documentElement);

        // Parse
		$sets = array();
		if (!preg_match_all('%^(?P<indent>[ \t]*+)(?:(?P<comment>#(?:[^\r\n\\\\]++|\\\\\r?\n?)++)|<[ \t]*(?P<section>\w++)(?P<section_args>(?:\\\\\r?\n|[ \t]++)(?:[^>\'"\r\n\\\\]++|"(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\'|\\\\\r?\n?)++)>[ \t]*|</(?P<end_section>\w++)>[ \t]*|(?P<directive>\w++)(?P<directive_args>(?:[ \t]++|\\\\\r?\n)(?:[^\r\n\\\\]++|\\\\\r?\n?)++)|(?P<syntaxerr>\S[^\n]*))(?P<blank>\r?\n\s*)*$%ms', $contents, $sets, PREG_SET_ORDER)) return;
		
		$matches = null;
		$lineno = preg_match('/^([ \t\r]*\n)*/', $contents, $matches) ? substr_count($matches[0], "\n") : 0;
		unset($contents);

		// Create document
		if (isset($filename)) $this->documentURI = $filename;
		$section = $this->documentElement;
		
		$set = null;
		foreach ($sets as &$set) {
			$lineno++;
			$extra_lines = 0;
			
			if (!empty($set['indent'])) $section->appendChild($this->createTextNode($set['indent']));
			
			if (!empty($set['directive'])) {
				$node = $section->appendChild($this->createDirective($set['directive']));
				
				if (!empty($set['directive_args'])) {
				    $this->parseArguments($node, $set['directive_args']);
				    $extra_lines = substr_count($set['directive_args'], "\n");
				}
				
			} elseif (!empty($set['section'])) {
				$node = $section->appendChild($this->createSection($set['section']));
				if (!empty($set['section_args'])) {
				    $this->parseArguments($node, $set['section_args']);
				    $extra_lines = substr_count($set['section_args'], "\n");
				}
				$section = $node;
				
			} elseif (!empty($set['end_section'])) {
				if ($section->nodeName != $set['end_section']) {
					if ($section === $this->firstChild) throw new \DOMException("Syntax error on line {$lineno}{$of_file}: </{$set['end_section']}> without matching <{$set['end_section']}> section", DOM_SYNTAX_ERR);
					  else throw new \DOMException("Syntax error on line {$lineno}{$of_file}: Expected </{$section->nodeName}> but saw </{$set['end_section']}>", DOM_SYNTAX_ERR);
				}
				$section = $section->parentNode;
				
			} elseif (!empty($set['comment'])) {
				$node = $section->appendChild($this->createComment(str_replace("\\\n", "\n", substr($set['comment'], 1), $extra_lines)));

			} elseif (!empty($set['syntaxerr'])) {
				throw new \DOMException("Syntax error on line {$lineno}{$of_file}: Invalid command '{$set['syntaxerr']}'.", DOM_SYNTAX_ERR);
			}
			
			/*if (isset($node)) {
			    $node->uriDocument = $filename;
			    $node->_lineno = $lineno;
			}*/
			
			if (!empty($set['blank'])) $section->appendChild($this->createTextNode(preg_replace('/^\r?\n(.*)$/', "$1\n", $set['blank'])));
			$lineno += $extra_lines + (!empty($set['blank']) ? substr_count($set['blank'], "\n") : 0);
			
            unset($node);
        }
		
		if ($section !== $this->firstChild) throw new \DOMException("Syntax error: <{$section->nodeName}> was not closed.", DOM_SYNTAX_ERR);
	}
	
	/**
	 * Load HTTPd configuration from a file.
	 * If called statically, returns a HTTPd_DOMDocument. 
	 * 
	 * @param string $filename  The path to the configuration file.
	 * @param int    $options   Not used.
	 * @return HTTPd_DOMDocument
	 */
	public function load($filename, $options=0)
	{
		$contents = file_get_contents($filename);
		if ($contents === false) throw new Exception("Could not load configuration file '$filename'.");
	    
		$dom = isset($this) && $this instanceof self ? $this : new self();
		$dom->loadConfiguration($contents, $filename);
		
		return $dom;
	}
	
	/**
	 * Load HTTPd configuration from a string.
	 * If called statically, returns a HTTPd_DOMDocument. 
	 * 
	 * @param string $contents  Contents of the configuration.
	 * @return HTTPd_DOMDocument
	 */
	public function loadString($contents)
	{
		$dom = isset($this) && $this instanceof self ? $this : new self();
	    $dom->loadConfiguration($contents);
	    
	    return $dom;
	}
	
	
	/**
	 * Save configuration to file(s).
	 * 
	 * @param string $filename  Leave blank to save back to original file(s).
	 * @param int    $options   Not used.
	 * @return int
	 */
	public function save($filename=null, $options=0)
	{
	    if (!isset($filename)) $filename = $this->documentURI;
	    return file_put_contents($filename, (string)$this->documentElement);
    }

	/**
	 * Save specific section to file(s).
	 * 
	 * @param HTTPd_DOMSection $node
	 * @param string           $filename  Leave blank to save back to original file(s).
	 * @param int              $options   Not used.
	 * @return int
	 */
	public function saveSection(HTTPd_DOMSection $node, $filename=null, $options=0)
	{
	    if (!isset($filename)) {
	        trigger_error("Saving a section back to original file is not implemented yet.", E_USER_WARNING);
	        return 0;
	    }
	    
	    return file_put_contents($filename, (string)$node);
	}
	
	/**
	 * Return document or $node as string.
	 * 
	 * @param DOMNode $node     Use this parameter to output only a specific node without XML declaration rather than the entire document.
	 * @param int     $options  Not used.
	 * @return string
	 */
	public function saveString(\DOMNode $node=null, $options=0)
	{
	    if (!isset($node)) $node = $this->documentElement;
	    return (string)$node;
	}
	
	/**
	 * Cast to string
	 * 
	 * @return string
	 */
	public function __toString()
	{
	    return $this->saveString();
	}
	
	
	/**
	 * Validate configuration.
	 * Not implemted; always returns true.
	 * 
	 * @return boolean
	 */
	public function validate()
	{
        return true; 
	}
	
	// ====== Unsupported features =====
	
	/**
	 * @ignore 
	 */	
	public function createAttributeNS()
	{
	    throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore 
	 */
	public function createCDATASection()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}

	/**
	 * @ignore 
	 */
	public function createElement()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore 
	 */
	public function createElementNS() 
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore 
	 */
	public function createEntityReference()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore 
	 */
	public function createProcessingInstruction()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore 
	 */
	public function loadHTML()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore 
	 */
	public function loadHTMLFile()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore
	 */
	public function loadXML()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}

	/**
	 * @ignore
	 */
	public function saveHTML()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore
	 */
	public function saveHTMLFile()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore
	 */
	public function saveXML()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore
	 */
	public function relaxNGValidate()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore
	 */
	public function relaxNGValidateSource()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}

	/**
	 * @ignore
	 */
	public function schemaValidate()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore
	 */
	public function schemaValidateSource()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
	
	/**
	 * @ignore
	 */
	public function xinclude()
	{
		throw new \DOMException(__FUNCTION__ . " is not supported for HTTPd configuration.", DOM_NOT_SUPPORTED_ERR);
	}
}