<?
/**
 * Generic class for detail pages (add/update/show)
 * 
 * @author 		Daniel Oosterhuis
 * @copyright 	2008 Bean IT
 *
 */
class Action_Detail extends Action {
	
	/**
	 * Data from FrontOffice
	 *
	 * @var Data
	 */
	protected $data;

	/**
	 * The view to load for showing the item
	 *
	 * @var string
	 */
	public $showView = 'View/Show.php';
	
	/**
	 * The view to load for updating the item
	 *
	 * @var string
	 */
	public $updateView = 'View/Update.php';
	
	
	/**
	 * Laadt de actie
	 *
	 * @param 	string	$table 	Which table definition
	 * @param 	Data	$data 	Request data or record data (in case we are checking for authorization in the overview)
	 */
	public function __construct($table, $data)
	{
		// __is_record is een cheat om snelheid te winnen voor grote overviews. 
		// De authorizer kan hierdoor echter geblokkeerde acties vrijgeven in het overzicht. 
		// Bij het klikken op de actie zal deze echter alsnog geblokkeerd worden
		if ($data->__is_record) $this->record = $data; 
		else {
			if (!$data->id && $data->mode !== 'add') throw new Action_Arguments_Exception('Param id required, but not set');
			$this->data = $data;
			$this->record = DB::i()->table($table)->load($data->id); 
		}
		
		if (!$this->record) throw new Action_Exception('Record not found');

		// voor inline items nog de parent_id verwerken
		if (XMLMenu::getAttribute('display') == "inline") {
			$parent_table = XMLMenu::getAttribute('dbtable', null, '..');
			$link_field = $this->record->getBaseTable()->getFieldProperty("#foreign-table:$parent_table", 'name_db');
			if ($data->mode === 'add') {
				if(!$data->parent_id) trigger_error("Parameter parent_id is required, but not set");
				$this->record->seekField($link_field)->value = $data->parent_id;
			} else {
				$data->parent_id = $this->record->seekField($link_field)->value;
			}
		} 
	}
	
	/**
	 * Check of de gevraagde actie is toegestaan voor de nota
	 *
	 * @return 	boolean
	 */
	protected function _auth()
	{
		return true;
	}
	
	/**
	 * Nota status bijwerken, e-mails verzenden
	 * 
	 * @return boolean
	 */
	protected function _execute()
	{
		$form = QuickBuild::createFromData('form', $this->record);
		
		// if the form is submitted and validated, redirect to overview
		if ($form->isSubmitted() && $form->validate()) {
			$this->record->save(); // record updaten
			$redirect_to = XMLMenu::getAttribute('display') == "inline" ? XMLMenu::getAttribute('id', null, '..')."/show?id=".$this->data->parent_id : XMLMenu::curitem();
			return new ActionResult_Redirect($redirect_to);
		}
		
		// create QuickBuild form
		if ($this->data->mode === 'show')
			return new ActionResult_View($this->showView, $this->record);
		else
			return new ActionResult_View($this->updateView, $this->record);
				
	}
		
}

?>