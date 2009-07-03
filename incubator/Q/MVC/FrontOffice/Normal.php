<?
/**
 * The FrontOffice_Normal can handle a request with the arguments in the constructor
 *
 * @author 		Daniel Oosterhuis
 * @copyright 	Bean IT 2008
 * @since 		PHP 5
 * 
 */
class FrontOffice_Normal extends FrontOffice {

	/**
	 * Initialize the FrontOffice - Read from URL the xml item/action and userdata
	 * 
	 * @param  string 	$item
	 * @param  string 	$action
	 * @param  mixed  	$data
	 * 
	 * @return FrontOffice	object
	 */
	public function __construct($item, $action=null, $data=null) {
		// read module, controller, action, XMLMenu item and everything else from $_REQUEST var
		$this->_requestVars = new Data();
		
		// capture get
		$this->_menuItem = $item;
		if ($action) $this->_menuAction = $action;
		
		if (is_array($data))
			foreach ($data as $k=>$v) $this->_requestVars->{$k} = $v;
	}
	
}

if (class_exists('ClassConfig', false)) ClassConfig_extractBin('FrontOffice::Normal');
?>