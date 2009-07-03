<?

/**
 * Interface for ActionResult objects
 * ActionResult objects are returned by actions. The action is triggered by the controller 
 *
 * @author 		Dani�l Oosterhuis
 * @since 		PHP 5
 * @copyright 	Bean IT - 2008
 */

abstract class ActionResult
{
	
	/**
	 * Present the result
	 * 
	 * @return ActionResult
	 */
	abstract public function execute();
	
}

class ActionResult_Exception extends Exception {}
class ActionResult_Unknown_Exception extends ActionResult_Exception {}
class Illegal_ActionResult_Exception extends ActionResult_Exception {}
?>