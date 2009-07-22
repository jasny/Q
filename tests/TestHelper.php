<?php
set_include_path(dirname(__DIR__) . "/library" . PATH_SEPARATOR . get_include_path());

ini_set("error_prepend_string", null);
ini_set("error_append_string", null);

require_once('PHPUnit/Framework/TestCase.php');
