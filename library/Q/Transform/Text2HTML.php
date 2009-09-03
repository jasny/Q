<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/Transform.php';

/**
 * Transform text into HTML
 *
 * @package Transform
 */
class Transform_text2HTML extends Transform
{	
	/**
	 * Use this to replace the tab character
	 */
	public $tab = "&nbsp;&nbsp;&nbsp;";
	
    /**
     * Start the transformation and return the result.
     *
     * @param string $data  Path to the file or the string that will be transformed 
     * @return mixed
     */
    public function process($data = null)
    {           
        if ($this->chainNext) $data = $this->chainNext->process($data);

        if(!is_string($data)) throw new Exception("Unable to start text transformation: Passed data is not a string");
//        if (!isset($data) || !file_exists ($data) || !is_file($data)) throw new Exception ("Unable to start the text file transformation : File '" . $data . "' does not exist, is not accessable (check permissions) or is not a regular file.");
        
        if (is_file($data)) {
            $data = file_get_contents($data);
            if (!$data) throw new Exception("Unable to start text file transformation: can't read file {$data}");
        }        
		$html = nl2br(htmlspecialchars($data)); //replace special characters with HTML entities and replace line breaks with <br />		
        // urls
        $html = preg_replace('/\b(((https?|ftp|file):\/\/)|((https?|ftp|file):\/\/www\.)|(www\.))[-A-Z0-9+&@#\/%\?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]/i', '<a href="\0">\0</a>', $html);
        // emails
        $html = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', '<a href="mailto:\0">\0</a>', $html);
        //tabs
        $html = preg_replace('/[\t]/', $this->tab, $html);
        
        return $html;
    }
    
	/**
	 * Start the transformation and display the result.
	 *
	 * @param array $data Array to transform to xml
	 * @return mixed
	 */
	public function output($data=null)
	{
        if ($this->chainNext) $data = $this->chainNext->process($data);
		
        echo $this->process($data);
	}

	/**
	 * Start the transformation and save the result into a file
	 *
	 * @param string $filename File name
	 * @param array  $data     Array to transform to xml
	 * @return mixed
	 */
	function save($filename, $data=null)
	{
        if ($this->chainNext) $data = $this->chainNext->process($data);
      
        if(!file_put_contents($filename, $this->process($data))) throw new Exception("Unable to create file {$filename}");  
	}
}
