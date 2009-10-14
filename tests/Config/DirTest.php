<?php
use Q\Config, Q\Config_Dir;

require_once 'TestHelper.php';
require_once 'Q/Config/Dir.php';
require_once 'Config/Mock/Unserialize.php';

/**
 * Test for Config_Dir
 */
class Config_DirTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->dir = sys_get_temp_dir() . '/q-config_dirtest-' . md5(uniqid());
        mkdir($this->dir);
        
        Q\Transform::$drivers['from-mock'] = 'Config_Mock_Unserialize';
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        rmdir($this->dir);
        Config_Mock_Unserialize:$created = array();
        unset(Q\Transform::$drivers['from-mock']);
    }
    
    /**
     * Check the results valid for most Config::with() tests
     */
    public function checkWithResult($config)
    {
        $this->assertType('Q\Config_Dir', $config);
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('mock', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Config_Mock_Unserialize', $refl_tr->getValue($config));        
    }
    
    /**
     * Check the results valid for most Config::with() tests that have a transformer
     */
    public function checkWithTrResult($config)
    {
        $this->assertType('Q\Config_Dir', $config);
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('yaml', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Config_Mock_Unserialize', $refl_tr->getValue($config));        
    }
    
    /**
     * Tests Config::with(): full (standard) DSN
     */
    public function testWith()
    {
        $config = Config::with("dir:ext=mock;path={$this->dir}");
        $this->checkWithResult($config);
    }    

    /**
     * Tests Config::with() : where driver; argument[0] is mock and argument['path']
     */
    public function testWith_Arg0IsExt()
    {
        $config = Config::with("dir:mock;path={$this->dir}");
        $this->checkWithResult($config);   
    }

    /**
     * Tests Config::with() : where driver, argument[0] is path and argument['ext']
     */
    public function testWith_Arg0IsPath()
    {
        $config = Config::with("dir:{$this->dir};ext=mock");
        $this->checkWithResult($config);   
    }
    
    /**
     * Tests Config::with() : where driver, argument[0] is ext:path
     */
    public function testWith_Arg0IsExtPath()
    {
        $config = Config::with("dir:mock:{$this->dir}");
        $this->checkWithResult($config);   
    }
        
    /**
     * Tests Config::with(): where driver is extension and argument[0] is path
     */
    public function testWith_DriverIsExt_Arg0IsPath()
    {
        $config = Config::with("mock:{$this->dir}");
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where driver is path
     */
    public function testWith_DriverIsPath()
    {
        $config = Config::with($this->dir);
        
        $this->assertType('Q\Config_Dir', $config);

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));

        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertEquals(null, $refl_tr->getValue($config));        
                
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals(null, (string)$refl_ext->getValue($config));
    }

    /**
     * Tests Config::with() : where dsn is driver:mock and options['path']
     */
    public function testWith_DsnIsDirAndExtOptPath()
    {
        $config = Config::with("dir:mock", array('path'=>$this->dir));
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where dsn is driver:mock and options[0] is path
     */
    public function testWith_DsnIsDirAndExtOpt0Path()
    {
        $config = Config::with("dir:mock", array($this->dir));
        $this->assertType('Q\Config_Dir', $config);
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('mock', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals(null, (string)$refl_path->getValue($config));
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Config_Mock_Unserialize', $refl_tr->getValue($config));
    }
    
    
    /**
     * Tests Config::with() : where dsn is driver:path and options['ext']
     */
    public function testWith_DsnIsDriverAndPathOptExt()
    {
        $config = Config::with("dir:{$this->dir}", array('ext'=>'mock'));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options['path']
     */
    public function testWith_DsnIsExtOptPath()
    {
        $config = Config::with('mock', array('path'=>$this->dir));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options[0] is path
     */
    public function testWith_DsnIsExtOpt0Path()
    {
        $config = Config::with('mock', array($this->dir));
        $this->checkWithResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is path and options['ext']
     */
    public function testWith_DsnIsPathOptExt()
    {
        $config = Config::with($this->dir, array('ext'=>'mock'));
        $this->checkWithResult($config);
    }

    /**
     * Tests Config::with() : where driver; argument[0] is yaml, argument['path'] and argument['transformer']
     */
    public function testWith_Arg0IsExtAndArgTr()
    {
        $config = Config::with("dir:yaml;path={$this->dir};transformer=from-mock");
        $this->checkWithTrResult($config);   
    }

    /**
     * Tests Config::with() : where driver, argument[0] is path, argument['ext'] and argument['transformer']
     */
    public function testWith_Arg0IsPathAndArgTr()
    {
        $config = Config::with("dir:{$this->dir};ext=yaml;transformer=from-mock");
        $this->checkWithTrResult($config);   
    }
    
    /**
     * Tests Config::with() : where driver, argument[0] is ext:path and argument['transformer']
     */
    public function testWith_Arg0IsExtPathAndArgTr()
    {
        $config = Config::with("dir:yaml:{$this->dir};transformer=from-mock");
        $this->checkWithTrResult($config);   
    }
        
    /**
     * Tests Config::with(): where driver is extension, argument[0] is path and argument['transformer']
     */
    public function testWith_DriverIsExt_Arg0IsPathAndArgTr()
    {
        $config = Config::with("yaml:{$this->dir};transformer=from-mock");
        $this->checkWithTrResult($config);
    }

    /**
     * Tests Config::with() : where dsn is driver:mock, options['path'] and options['transformer']
     */
    public function testWith_DsnIsDirAndExtOptPathArgTr()
    {
        $config = Config::with("dir:Yaml", array('path'=>$this->dir, 'transformer'=> new Config_Mock_Unserialize()));
        $this->checkWithTrResult($config);
    }

    /**
     * Tests Config::with() : where dsn is driver:mock and options[0] is path and options['transformer']
     */
    public function testWith_DsnIsDirAndExtOpt0PathArgTr()
    {
        $config = Config::with("dir:yaml", array($this->dir, 'transformer'=>new Config_Mock_Unserialize()));
        $this->checkWithTrResult($config);
        
    }
    
    
    /**
     * Tests Config::with() : where dsn is driver:path and options['ext'] and options['transformer']
     */
    public function testWith_DsnIsDriverAndPathOptExtArgTr()
    {
        $config = Config::with("dir:{$this->dir}", array('ext'=>'yaml', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options['path'] and options['transformer']
     */
    public function testWith_DsnIsExtOptPathArgTr()
    {
        $config = Config::with('yaml', array('path'=>$this->dir, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is ext and options[0] is path and options['transformer']
     */
    public function testWith_DsnIsExtOpt0PathArgTr()
    {
        $config = Config::with('yaml', array($this->dir, 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    /**
     * Tests Config::with() : where dsn is path and options['ext'] and options['transformer']
     */
    public function testWith_DsnIsPathOptExtArgTr()
    {
        $config = Config::with($this->dir, array('ext'=>'mock', 'transformer'=>'from-mock'));
        $this->checkWithTrResult($config);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    /**
     * Tests Config::with() : config options scalar with driver, path and transformer
     */
/*
    public function testConfig_With_Scalar_DriverAndTransform()
    {        
        $config = Config::with('dir:'.$this->dir.';transformer=from-mock;');
        
        $this->assertType('Q\Config_Dir', $config);
        
        $refl_tr = new \ReflectionProperty($config, '_transformer');
        $refl_tr->setAccessible(true);
        $this->assertType('Q\Transform_Unserialize_Yaml', $refl_tr->getValue($config));        
        
        $refl_ext = new \ReflectionProperty($config, '_ext');
        $refl_ext->setAccessible(true);
        $this->assertEquals('yaml', $refl_ext->getValue($config));

        $refl_path = new \ReflectionProperty($config, '_path');
        $refl_path->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl_path->getValue($config));
    }
*/    
    /**
     * Tests Config_Dir : config options array with path and ext
     */
    public function testConfigDir()
    {
        $config = new Config_Dir($this->dir);
        
        $this->assertType('Q\Config_Dir', $config);

        $refl = new \ReflectionProperty($config, '_path');
        $refl->setAccessible(true);
        $this->assertEquals($this->dir, (string)$refl->getValue($config));
    }
    
}
