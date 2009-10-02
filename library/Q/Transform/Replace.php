<?php
namespace Q;

require_once 'Q/Transform/Exception.php';
require_once 'Q/Transform.php';

/**
 * Place content in a template using the output handler.
 *
 * @package Transform
 */
class Transform_Replace extends Transform
{
    /**
     * Template PHP string or Fs_Node 
     * @var string
     */
    public $template;
	
	/**
	 * Marker to place data, %s for name.
	 * This is a pcre regular expression with ~ as delimiter.
	 * 
	 * @var string
	 */
	public $marker = '%%{%s}';
			
	/**
	 * Class constructor
	 * 
	 * @param array $options
	 */
	public function __construct($options=array())
	{
		if (isset($options[0])) $options['template'] = Fs::file($options[0]);
		  elseif (isset($options['file'])) $options['template'] = Fs::file($options['file']);
		unset($options[0], $options['file']);

		parent::__construct($options);
	}

    /**
     * Do the transformation and return the result.
     *
     * @param mixed $data  Data to transform
     * @return mixed
     */
   public function process($data=null) {
        if (empty($this->template) || !is_string($this->template) && !($this->template instanceof Fs_Node)) throw new Transform_Exception('Unable to start the replace process: No template available or wrong variable type');
                
   	    if ($this->chainInput) $data = $this->chainInput->process($data);
   	    
   	    if (!is_array($data)) throw new Transform_Exception("Unable to start the replace process : Incorect data type");
        $this->data = $data;
        
        if ($this->template instanceof Fs_Node) $content = $this->template->getContents();
          else $content = $this->template;
        
        foreach ($data as $key => $value) {
			$marker = sprintf($this->marker, $key);
			$content = preg_replace('/(' . preg_quote($marker, '/'). ')/', $value, $content);
        }
        return $content;
    }
}
