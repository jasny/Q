<?php
use Q\Transform;

require_once 'Q/Transform.php';

/**
 * Mock object for unserialize transformer used in Config unit tests.
 * 
 * @ignore
 */
class Config_Mock_Unserialize extends Transform
{
    /**
     * Created transform mock objects
     * @var array
     */
    static public $created = array();
    
    /**
     * Input data process
     * @var mixed
     */
    public $in;
    
    /**
     * Return data
     * @var array
     */
    public $out = array(
      'db' => array(
        'host'   => 'localhost',
        'dbname' => 'test',
        'user'   => 'myuser',
        'pwd'    => 'mypwd'
      ),
      'output' => 'xml',
      'input'  => 'json'
    );
    
    /**
     * Default extension for file with serialized data.
     * @var string
     */
    public $ext = 'mock';
    
    /**
     * Class constructor
     */
    public function __construct($options=array())
    {
        self::$created[] = $this;
    }
    
    /**
     * Transform
     * 
     * @param mixed $data
     * @return array
     */
    public function process($data)
    {
        $this->in = $data;
        return $this->out;
    }
}