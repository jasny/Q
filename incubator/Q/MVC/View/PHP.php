<?php

/**
 * Handler for using PHP files as view.
 * 
 * @package MVC
 */
class View_PHP
{
	/**
	 * Path to view files
	 * @var string
	 */
	public $path;	
	
	/**
	 * Class contructor
	 *
	 * @param Controller $ctl
	 * @param string     $path
	 */
	function __construct($ctl, $path)
	{
		$this->ctl = $ctl;
		$this->path = $path;
	}
	
	/**
	 * Output view
	 *
	 * @param string $view
	 * @param array  $data
	 */
	public function show($view, $data)
	{
		if (strpos($view, '/') !== false || $view === '.' || $view === '..'|| $view === '~') throw new SecurityException("Illegal view name '$view'.");
		
		$__file = "{$this->path}/$view";
		
		if (!(($fp = fopen($__file)) && fclose($fp))) throw new Exception("Unable to load view '$view' from '$::_file'");
		
		extract($data);
		$ctl = $this->ctl;
		include($__file);
	}
}
?>