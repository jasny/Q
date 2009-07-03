<?
/**
 * Standard class for loading overviews
 *
 * @author 		Daniel Oosterhuis
 * @since 		PHP 5
 * @copyright 	2008 Bean IT
 */

class Action_Overview extends Action {
	
	protected $data;

	/**
	 * Laadt de actie
	 *
	 * @param 	string 		$table		Which table definition
	 * @param 	Data 		$data		
	 * @param 	string 		$where
	 */
	public function __construct($table, $data, $where=null)
	{
		$this->data = DB::i()->table($table)->getStatement('overview'); 
		if ($where) $this->data->addWhere($where);
	}
	
	/**
	 * Nota status bijwerken, e-mails verzenden
	 * 
	 * @return ActionResult
	 */
	protected function _execute()
	{
		return new ActionResult_View('View/Overview.php', $this->data);
	}
		
}