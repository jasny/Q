<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/ExpectedException.php';

require_once 'Q/InputException.php';
require_once 'Q/NotFoundException.php';
require_once 'Q/InvalidMethodException.php';
require_once 'Q/NotImplementedException.php';


/**
 * A common exception which can be mapped to an HTTP response status.
 * 
 * @package Exception
 */
interface CommonException
{}
