<?php
use Q\Config, Q\Fs;

require_once 'TestHelper.php';
require_once 'Q/Config.php';
require_once 'Q/Fs.php';

/**
 * Test factory method
 */
class Config_Test extends \PHPUnit_Framework_TestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->file = sys_get_temp_dir() . '/q-config_test-' . md5(uniqid());
        if (!file_put_contents($this->file, '<?xml version="1.0" encoding="UTF-8"?>
<settings>
    <grp1>
        <q>abc</q>
        <b>27</b>
    </grp1>
    <grp2>
        <a>original</a>
    </grp2>
</settings>')) $this->markTestSkipped("Could not write to '{$this->file}'.");
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->cleanup($this->file);
    }

    /**
     * Remove tmp files (recursively)
     * 
     * @param string $path
     */
    protected static function cleanup($path)
    {
        foreach (array('', '.orig', '.x', '.y') as $suffix) {
            if (is_dir($path . $suffix) && !is_link($path . $suffix)) {
                static::cleanup($path . $suffix . '/' . basename($path));
                if (!rmdir($path . $suffix)) throw new Exception("Cleanup failed");
            } elseif (file_exists($path . $suffix) || is_link($path . $suffix)) {
                unlink($path . $suffix);
            }
        }
    } 
    
    
    public function testDriverOnly()
    {
        $config = Config::with('xml:'.$this->file);

        $refl = new \ReflectionProperty($config, '_driver');
        $refl->setAccessible(true);
        $driver = $refl->getValue($config);
        
        $this->assertType('Q\Config_File', $config);
        $this->assertEquals('xml', $driver);        
    }

    public function testPath()
    {
        $config = Config::with('xml:'.$this->file);

        $refl = new \ReflectionProperty($config, '_path');
        $refl->setAccessible(true);
        $path = $refl->getValue($config);

        $this->assertType('Q\Config_File', $config);
        $this->assertEquals($this->file, (string)$path);
    }

    public function testOptions()
    {
        $config = Config::with('xml:' . $this->file . ';abc=22', array('xyz'=>'test'));
        $this->assertType('Q\Config_File', $config);
        
        $refl = new \ReflectionProperty($config, '_options');
        $refl->setAccessible(true);
        $options = $refl->getValue($config);
        
        $this->assertEquals($this->file, (string)$options['path']);
        $this->assertEquals(22, $options['abc']);
        $this->assertEquals('test', $options['xyz']);
    }
    
    public function testDefautlOptions()
    {
        Config::$defaultOptions['abc'] = 22;
        
        $config = Config::with('xml', array('path'=>$this->file));
        
        $this->assertType('Q\Config_File', $config);

        $refl = new \ReflectionProperty($config, '_options');
        $refl->setAccessible(true);
        $options = $refl->getValue($config);
        
        $this->assertEquals(22, $options['abc']);
    }
/*
    public function testInterface()
    {
        $this->assertType('Q\Config_Mock', Config::i());
        $this->assertFalse(Config::i()->exists());
        
        Config::i()->with('none');
        $this->assertType('Q\Config_None', Config::i());
        $this->assertTrue(Config::i()->exists());
    }

    public function testAlternativeInterface()
    {
        $this->assertType('Q\Config_Mock', Config::mytest());
        $this->assertFalse(Config::mytest()->exists());
        
        Config::mytest()->with('yaml:' . __DIR__ . '/settings/test.yaml');
        $this->assertType('Q\Config_Yaml', Config::mytest());
        $this->assertTrue(Config::mytest()->exists());
    }
*/
}
