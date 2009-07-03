<?
/**
 * Standard class for loading static view
 *
 * @author 		Daniel Oosterhuis
 * @since 		PHP 5
 * @copyright 	2008 Bean IT
 */

class Action_Static extends Action {
	
	protected $data;
	protected $page;

	/**
	 * Laadt de actie
	 *
	 * @param 	string 		$table		Which table definition
	 * @param 	Data 		$data		
	 * @param 	string 		$where
	 */
	public function __construct($page, $data)
	{
		$this->page = $page;
		$this->data = $data;
	}
	
	/**
	 * Nota status bijwerken, e-mails verzenden
	 * 
	 * @return ActionResult
	 */
	protected function _execute()
	{
		return new ActionResult_View($this->page, $this->data);
	}
		
}