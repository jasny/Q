<?php
use Q\Fs, Q\Fs_Dir;

require_once 'Q/Fs/Dir.php';
require_once 'Config/Mock/File.php';

/**
 * Mock object for Fs_Dir used in Config unit tests.
 * 
 * @ignore
 */
class Config_Mock_Dir extends Fs_Dir
{
    /**
     * Current file
     * @var unknown_type
     */
    protected $cur =0;
    
    /**
     * Files
     */
    public $content = array(
      'file1.mock'=>0,
      'dir1'=>array("file3.mock"=>0, "file4.mock"=>0),
      'file2.mock'=>0,
    );
    
    /**
     * Class constructor
     * 
     * @param string $path
     * @param array  $content
     */
    public function __construct($path, $content=null)
    {
        parent::__construct($path);
        if (isset($content)) $this->content = $content;
if ($path == '/tmp/q-config_dirtest-31e4649e807c8cadf222ee2428fee1c1/dir1') {
    var_dump($this->content);exit;
}
        if (!is_dir($this->_path)) throw new PHPUnit_Framework_SkippedTestError("Can't use Config_Mock_Dir object: '{$this->_path} does not exist");
        
        foreach ($this->content as $file=>$sub) {
            if ($sub == 0) touch("{$this->_path}/$file");
              else mkdir("{$this->_path}/$file");
        }
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        foreach ($this->content as $file=>$sub) {
            if ($sub == 0) unlink("{$this->_path}/$file");
              else rmdir("{$this->_path}/$file");
        }
    }
        
    /**
     * Interator; Returns the current file object.
     * 
     * @return Fs_Node
     */
    public function current()
    {
        $files = array_keys($this->content);
        $file = $files[$this->cur];
//        if ($this->content[$file] != 0) {echo "\n{$this->_path}/$file\n";var_dump($this->content[$file]); exit;}
        if ($this->content[$file] != 0) return new Config_Mock_Dir("{$this->_path}/$file", $this->content[$file]);
        return new Config_Mock_File("{$this->_path}/$file");
    }
    
    /**
     * Interator; Returns the current filename.
     * 
     * @return string
     */
    public function key()
    {
        $files = array_keys($this->content);
        return $files[$this->cur];
    }
    
    /**
     * Interator; Move forward to next item.
     */
    public function next()
    {
        $this->cur++;
    }
    
    /**
     * Interator; Rewind to the first item.
     */
    public function rewind()
    {
        $this->cur = 0;
    }
    
    /**
     * Interator; Check if there is a current item after calls to rewind() or next(). 
     */
    public function valid()
    {
        return $this->cur < count($this->content);
    }
}
