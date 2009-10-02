<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';

/**
 * Transform text into HTML
 *
 * Options:
 *   tab  Use it to set the tab
 * 
 * @package Transform
 */
class Transform_text2HTML extends Transform
{	
	/**
	 * Use it to set the tab
	 */
	public $tab = "&nbsp;&nbsp;&nbsp;";
	
    /**
     * Convert emails. If false will just display the emails without converting them.
     */
    public $convertEmail = true;
	
    /**
     * Convert links. If false will just display the links without converting them.
     */
    public $convertLink = true;
        
    /**
     * Start the transformation and return the result.
     *
     * @param mixed $data  Path to the file or the string that will be transformed 
     * @return mixed
     */
    public function process($data = null)
    {           
        if ($this->chainInput) $data = $this->chainInput->process($data);

        if(!is_string($data) && !($data instanceof Fs_Node)) throw new Transform_Exception("Unable to start text transformation: Incorrect data provided");
        
        if ($data instanceof Fs_Node) $data = $data->getContents();
        if (empty($data)) throw new Transform_Exception("Unable to start text file transformation: empty data");
        
        $html = nl2br(htmlspecialchars($data)); //replace special characters with HTML entities and replace line breaks with <br />		
        // urls
        if ($this->convertLink === true) $html = preg_replace('/\b(((https?|ftp|file):\/\/)|((https?|ftp|file):\/\/www\.)|(www\.))[-A-Z0-9+&@#\/%\?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]/i', '<a href="\0">\0</a>', $html);
        // emails
        if ($this->convertEmail === true) $html = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', '<a href="mailto:\0">\0</a>', $html);
        //tabs
        $html = preg_replace('/[\t]/', $this->tab, $html);
        
        return $html;
    }
}
