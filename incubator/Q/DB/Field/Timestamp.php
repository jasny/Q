<?php
namespace Q;

require_once "Q/DB/Field/Info.php";

/**
 * Contains definition of a db table field as for a timestamp column.
 *
 * @package DB
 */
class DB_Field_Timestamp extends DB_Field_Info
{
	/**
	 * Class constructor
	 *
	 * @param string $generateAction  The action on which the value should be generated: 'insert', 'update' or NULL (both)
	 * @param mixed  $properties      Array with field properties
	 */
	function __construct($generateAction, $properties=array())
	{
		if (!isset($properties['datatype'])) $properties['datatype'] = 'datetime';
		parent::__construct($generateAction, $properties);
	}

	/**
	 * Return timestamp
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	function generate($value=null)
	{
		return isset($this->_properties['type']) && $this->_properties['type'] == 'int' ? time() : strftime('%Y-%d-%m %H:%M:%S');
	}
}
?>