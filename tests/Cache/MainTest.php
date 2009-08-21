<?php
use Q\Cache;

require_once 'TestHelper.php';
require_once 'Q/Cache/Var.php';

/**
 * Cache test case.
 */
class Cache_MainTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @var Cache
	 */
	protected $Cache;
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Cache = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Cache->has()
	 */
	public function testHas()
	{
        $this->assertFalse($this->Cache->has('test'));
        $this->assertFalse($this->Cache->has('test'), 'second time');
    }

	/**
	 * Tests Cache->set()
	 */
	public function testSet()
	{
	    if ($this->Cache->has('test')) $this->markSkipped("Initial has() failed");
	    
	    $this->Cache->set('test', "abcdef");
	    $this->assertTrue($this->Cache->has('test'));
	}
	
	/**
	 * Tests Cache->get()
	 */
	public function testGet()
	{
	    $this->assertNull($this->Cache->get('test'));
	    
	    $this->Cache->set('test', "abcdef");
	    $this->assertEquals("abcdef", $this->Cache->get('test'));
	    
	    $this->Cache->set('apple', array('red', 'tree', 'juice'));
	    $this->assertEquals("abcdef", $this->Cache->get('test'), 'After setting apple');
	    $this->assertEquals(array('red', 'tree', 'juice'), $this->Cache->get('apple'));
	}

	/**
	 * Tests Cache->remove()
	 */
	public function testRemove()
	{
	    $this->Cache->set('test', "abcdef");
	    $this->Cache->set('apple', array('red', 'tree', 'juice'));
	    if (!$this->Cache->has('test')) $this->markTestSkipped("Setting of the data failed");
	    
	    $this->Cache->remove('test');
	    $this->assertFalse($this->Cache->has('test'), 'test');
	    $this->assertTrue($this->Cache->has('apple'), 'apple');
	}

	/**
	 * Tests Cache->clean(), clean all settings
	 */
	public function testClean_All()
	{
	    $this->Cache->set('test', "abcdef");
	    $this->Cache->set('apple', array('red', 'tree', 'juice'));
	    if (!$this->Cache->has('test')) $this->markTestSkipped("Setting of the data failed");
	    
	    $this->Cache->clean(Cache::ALL);
	    $this->assertFalse($this->Cache->has('test'), 'test');
	    $this->assertFalse($this->Cache->has('apple'), 'apple');
	}
}
