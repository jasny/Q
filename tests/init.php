<?php

require_once 'PHPUnit/Framework.php';
require_once 'Mockery/Loader.php';
require_once 'Hamcrest/hamcrest.php';
$loader = new \Mockery\Loader;
$loader->register();

set_include_path(dirname(__DIR__).'/library' . ':' . __DIR__ . ':' . get_include_path());
