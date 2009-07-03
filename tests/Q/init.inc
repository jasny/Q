<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
	$basepath = realpath(dirname(dirname(__FILE__)));
	$script = realpath(($_SERVER['PHP_SELF'][0] === '/' ? '' : $_SERVER["PWD"] . '/') . $_SERVER['PHP_SELF']);
	$class = preg_replace(array('~/~', '/\.php$/'), array('_', ''), substr($script, strlen($basepath)+1));
	
	define('PHPUnit_MAIN_METHOD', "$class::main");
	chdir($basepath);
}

set_include_path(dirname(__DIR__) . "/src" . PATH_SEPARATOR . get_include_path());

ini_set("error_prepend_string", null);
ini_set("error_append_string", null);

require_once('PHPUnit/Framework/TestCase.php');
require_once('PHPUnit/TextUI/TestRunner.php');
?>