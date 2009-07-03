<?
/**
 * Standard class for export
 *
 * @author 		Mark van der Laan
 * @since 		PHP 5
 * @copyright 	2008 Bean IT
 */

class Action_Export extends Action
{
	/**
	 * Calling controller
	 * @var Controller
	 */
	protected $ctl;
	
	/**
	 * Exporter
	 *
	 * @var Exporter
	 */
	protected $exporter;

	/**
	 * DB Table object
	 *
	 * @var DB_Table
	 */
	protected $table;
	
	/**
	 * Constructor
	 *
	 * @param 	Data	$data 	Request data
	 */
	public function __construct($table, $data)
	{
		$this->table = DB::i()->table($table);
		$exportdef = $data->exportdef;
		if (empty($exportdef)) throw new Action_Arguments_Exception("Cannot export file: Unknown exportdef.");
		
		$this->data = $data;
		
		$this->exporter = new Exporter($exportdef);
		if (empty($this->exporter->properties)) throw new Action_Arguments_Exception("Cannot export file: Exportdef has no properties");

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
	 * Generate file
	 * 
	 * @return ActionResult
	 */
	protected function _execute()
	{	
		QuickBuild_SelectForm::setStoreGroup($this->data->_c_menuitem);
		$selectform = QuickBuild::createFromData('selectform', $this->table, $this->exporter->query);
		if ($selectform) $selectform->updateQuery();

		$content = $this->exporter->generate();
		$filename = $this->exporter->createFilename();
		$ctype = get_ctype(file_extension($filename));
		
		return new ActionResult_Download($content, $filename);
	}	
	
}