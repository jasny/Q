<?php
use Q\Transform;

require_once 'TestHelper.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Transform.php';

/**
 * Test factory method
 */
class Transform_Test extends \PHPUnit_Framework_TestCase
{
    /**
     * Test driver xsl
     */
    public function testDriver_SimpleDSN()
    {
        $transform = Transform::with('xsl');
        $this->assertType('Q\Transform_XSL', $transform);
    }
    
    /**
     * Test driver json with dsn
     */
    public function testDriver_SimpleOptions()
    {
        $transform = Transform::with('unserialize-json:assoc=false');
        $this->assertType('Q\Transform_Unserialize_Json', $transform);
        $this->assertFalse($transform->assoc);
    }
    
    /**
     * Test driver with dsn and multiple options
     */
    public function testDriver_Options()
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'Q-');
        file_put_contents($tmpfile, "<body>
  Hello i'm ###name###. I was very cool @ ###a###.
</body>");

        $transform = Transform::with('unserialize-json:file='.$tmpfile.';marker=###%s###;');
        $this->assertType('Q\Transform_Unserialize_Json', $transform);
        $this->assertEquals('###%s###', $transform->marker);
        $this->assertEquals("<body>
  Hello i'm ###name###. I was very cool @ ###a###.
</body>", file_get_contents($transform->file));
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
    
    /**
     * Test Transform::to()
     */
    public function testTo()
    {
        $transform = Transform::to('xml');
        $this->assertType('Q\Transform_Serialize_XML', $transform);
    }
    
    /**
     * Test Transform::to()
     */
    public function testTo_Options()
    {
        $transform = Transform::to('php:castObjectToString=true');
        $this->assertType('Q\Transform_Serialize_PHP', $transform);
        $this->assertTrue($transform->castObjectToString);
    }
    
    /**
     * Test Transform::from()
     */
    public function testFrom()
    {
        $transform = Transform::from('ini');
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
    }
    /**
     * Test Transform::from()
     */
    public function testFrom_Options()
    {
        $transform = Transform::from('ini:test1=testarea1;test2=testarea2');
        $this->assertType('Q\Transform_Unserialize_Ini', $transform);
        $this->assertEquals('testarea1', $transform->test1);
        $this->assertEquals('testarea2', $transform->test2);
    }
}

