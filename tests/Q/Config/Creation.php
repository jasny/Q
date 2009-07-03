<?php
use Q\Config;

require_once __DIR__ . '/../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'Q/Config.php';

/**
 * Test factory method
 */
class Test_Config_Creation extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }
    
    public function testDriverOnly()
    {
        $config = Config::with('none');
        $this->assertType('Q\Config_None', $config);
    }

    public function testPath()
    {
        $config = Config::with('yaml:' . __DIR__ . '/settings/test.yaml');
        $this->assertType('Q\Config_Yaml', $config);
        
        $refl = new ReflectionProperty($config, '_options');
        $refl->setAccessible(true);
        $options = $refl->getValue($config);
        
        $this->assertEquals(__DIR__ . '/settings/test.yaml', $options['path']);
    }

    public function testSpecialPath()
    {
        $_ENV['MYDIR'] = __DIR__;
        $config = Config::with('yaml:{$MYDIR}/settings/test.yaml');
        $this->assertType('Q\Config_Yaml', $config);
        
        $refl = new ReflectionProperty($config, '_options');
        $refl->setAccessible(true);
        $options = $refl->getValue($config);
        
        $this->assertEquals(__DIR__ . '/settings/test.yaml', $options['path']);
    }

    public function testPathOnly()
    {
        $config = Config::with(__DIR__ . '/settings/test.yaml');
        $this->assertType('Q\Config_Yaml', $config);
        
        $refl = new ReflectionProperty($config, '_options');
        $refl->setAccessible(true);
        $options = $refl->getValue($config);
        
        $this->assertEquals(__DIR__ . '/settings/test.yaml', $options['path']);
    }
    
    public function testOptions()
    {
        $config = Config::with('yaml:' . __DIR__ . '/settings/test.yaml;abc=22', array('xyz'=>'test'));
        $this->assertType('Q\Config_Yaml', $config);
        
        $refl = new ReflectionProperty($config, '_options');
        $refl->setAccessible(true);
        $options = $refl->getValue($config);
        
        $this->assertEquals(__DIR__ . '/settings/test.yaml', $options['path']);
        $this->assertEquals(22, $options['abc']);
        $this->assertEquals('test', $options['xyz']);
    }
    
    public function testDefautlOptions()
    {
        Config::$defaultOptions['abc'] = 22;
        
        $config = Config::with('none');
        $this->assertType('Q\Config_None', $config);

        $refl = new ReflectionProperty($config, '_options');
        $refl->setAccessible(true);
        $options = $refl->getValue($config);
        
        $this->assertEquals(22, $options['abc']);
    }

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
}
?>