<?
/**
 * Base class for handler for view loading.
 * 
 * @package MVC
 */
abstract class View 
{
	/**
	 * Calling controller
	 * @var Controller
	 */
	protected $ctl;

	
	/**
	 * Class contructor
	 *
	 * @param Controller $ctl
	 */
	function __construct($ctl)
	{
		$this->ctl = $ctl;
	}

	/**
	 * Output view
	 *
	 * @param string $view
	 * @param array  $data
	 */
	abstract public function show($view, $data);
}

?>