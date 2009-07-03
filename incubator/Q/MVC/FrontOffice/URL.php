<?
/**
 * The FrontOffice_URL handles all requests from URL
 *
 * @author 		Daniel Oosterhuis
 * @copyright 	Bean IT 2008
 * @since 		PHP 5
 * 
 */
class FrontOffice_URL extends FrontOffice {


	/**
	 * Initialize the FrontOffice - Read from URL the xml item/action and userdata
	 * 
	 * @return FrontOffice	object
	 */
	public function __construct() {
		// read module, controller, action, XMLMenu item and everything else from $_REQUEST var
		$this->_requestVars = new Data();
		
		// capture get
		if ($_GET['_c_menuitem']) 	$this->_menuItem = urldecode($_GET['_c_menuitem']);
		if ($_GET['_c_menuaction']) $this->_menuAction = urldecode($_GET['_c_menuaction']);
		
		foreach ($_GET as $k=>$v) {
			$k = urldecode($k);
			$this->_requestVars->{$k} = urldecode($v);
		}	
		
		// capture post	
		foreach ($_POST as $k=>$v) $this->_requestVars->{$k} = $v;

	}
	
}

if (class_exists('ClassConfig', false)) ClassConfig_extractBin('FrontOffice::URL');
?>