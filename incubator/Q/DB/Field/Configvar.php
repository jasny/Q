<?php
namespace Q;

require_once "Q/DB/Field/Info.php";

/**
 * Contains definition of a db table field as for a systemtype column.
 *
 * @package DB
 */
class DB_Field_Configvar extends DB_Field_Info
{
	/**
	 * Class constructor
	 *
	 * @param string $generateAction  The action on which the value should be generated: 'insert', 'update' or NULL (both)
	 * @param mixed  $properties      Array with field properties
	 */
	function __construct($generateAction, $properties=array())
	{
		if (!isset($properties['datatype'])) $properties['datatype'] = 'text';
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
		$settings =& Config_i()->getSettings(null, false);
		return isset($this->_properties['configvar']) && isset($settings[$this->_properties['configvar']]) ? $settings[$this->_properties['configvar']] : null;
	}
}

if (class_exists('ClassConfig', false)) ClassConfig_extractBin('DB_Field_Configvar');
?>
