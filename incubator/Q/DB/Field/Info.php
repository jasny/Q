<?php
namespace Q;

require_once "Q/DB/Field.php";

/**
 * Abstract class for info fields (as timestamp or user_id).
 * Info fields can not be edited and are automaticly updated on create or edit.
 *
 * @package DB
 */
abstract class DB_Field_Info extends DB_Field
{
	/**
	 * The action on which the value should be generated: 'insert', 'update' or 'both'
	 * @var string
	 */
	protected $_generateAction = 'both';
	
	/**
	 * Return the action name on which the value should be generated
	 * @return  string
	 */
	protected function getGenerateAction()
	{
		return $this->_generateAction;
	}

	
	/**
	 * Class constructor
	 *
	 * @param string $generateAction  The action on which the value should be generated: 'insert', 'update' or 'both'
	 * @param mixed  $properties      Array with field properties
	 */
	function __construct($generateAction, $properties=array())
	{
		if (isset($generateAction)) $this->_generateAction = $generateAction;
		if (!isset($properties['no_edit'])) $properties['no_edit'] = true;
		parent::__construct($properties);
	}
	
	/**
	 * Cast a value according to the field type property or generate value for insert/update
	 *
	 * @param  mixed   $value
	 * @param  string  $action  'insert', 'update' or NULL
	 * @return mixed
	 */
	function castValue($value, $action=null)
	{
		if (isset($action) && ($this->_generateAction === 'both' || $this->_generateAction === $action)) return $this->generate($value);
		return parent::castValue($value, $action);
	} 
	
	/**
	 * Generate value for insert/update
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	abstract function generate($value=null);
}

?>