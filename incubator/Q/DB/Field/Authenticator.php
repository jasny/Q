<?php
namespace Q;

require_once "Q/DB/Field/Info.php";

/**
 * Contains definition of a db table field as for a user_id column.
 * Autofills value with user id of Authenticator.
 *
 * @package DB
 */
class DB_Field_Authenticator extends DB_Field_Info
{
	/**
	 * Class constructor
	 *
	 * @param string $generateAction  The action on which the value should be generated: 'insert', 'update' or NULL (both)
	 * @param mixed  $properties      Array with field properties
	 */
	function __construct($generateAction, $properties=array())
	{
		if (!class_exists('Authenticator')) throw new DB_Exception("Unable to create an authenticator field: Class Authenticator is not loaded.");
		
		if (!isset($properties['datatype'])) $properties['datatype'] = 'lookupkey';
		if (!isset($properties['source'])) $properties['source'] = Authenticator::i()->userQuery;

		parent::__construct($generateAction, $properties);
	}

	/**
	 * Return user id of Authernticator
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	function generate($value=null)
	{
		return Authenticator::i()->userId();
	}
}

?>