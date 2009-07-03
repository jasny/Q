<?
/**
 * Standard class for Delete operations
 *
 * @author 		Daniel Oosterhuis
 * @since 		PHP 5
 * @copyright 	2008 Bean IT
 */

class Action_Delete extends Action {
	
	protected $data;

	/**
	 * Constructor
	 *
	 * @param 	string	$table 	Table definition
	 * @param 	Data	$data 	Request data
	 */
	public function __construct($table, $data)
	{
		if ($data->__is_record) $this->record = $data;
		else {
			if (!$data->id) throw new Action_Arguments_Exception('Param id required, not given');
			$this->data = $data;
			$this->record = DB::i()->table($table)->load($data->id);
		}
		if (!$this->record) throw new Action_Exception('Record not found');
	}
	
	/**
	 * Check of de gevraagde actie is toegestaan voor de record
	 *
	 * @return boolean
	 */
	protected function _auth()
	{
		return true;
	}
	
	/**
	 * Ask for confirmation, then delete the record, redirect client
	 * 
	 * @return ActionResult
	 */
	protected function _execute()
	{
		$form = QuickBuild::createFromData('form', $this->record);
		
		// if the delete is confirmed, redirect to overview
		if ($this->data->confirm === 'delete') {			
			// delete the record
			$this->record->getBaseTable()->delete($this->record->getId());
			return new ActionResult_Redirect(XMLMenu::curitem());
		}

		return new ActionResult_View('View/Delete.php', $this->record);
	}	
	
}