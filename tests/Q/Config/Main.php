<?php
require_once __DIR__ . '/../init.inc';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

abstract class Test_Config_Main extends PHPUnit_Framework_TestCase
{
	/**
	 * Run test from php
	 */
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(new PHPUnit_Framework_TestSuite(__CLASS__));
    }

    /**
     * Get path with config files.
     *
     * @return string
     */
    public function getPath()
    {
        return __DIR__ . '/settings';
    }
    
    /**
     * Test adding settings
     *
     * @param Config $config
     */
    function setgetTest($config)
    {
    	$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('a'=>'original')), $config->get());
    	
    	$config->set('grp2', array('b'=>'changed'));
    	$config->set('grp3', array('rew'=>'MY VALUE', 'qq'=>2));
    	
    	$this->assertEquals(array('grp1'=>array('q'=>'abc', 'b'=>27), 'grp2'=>array('b'=>'changed'), 'grp3'=>array('rew'=>'MY VALUE', 'qq'=>2)), $config->get());
    	$this->assertEquals(array('rew'=>'MY VALUE', 'qq'=>2), $config->get('grp3'));

    	$config->set(array('grp3', 'qq'), 19);
    	$this->assertEquals(array('rew'=>'MY VALUE', 'qq'=>19), $config->get('grp3'));
    }
}

?>