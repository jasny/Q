<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/ExpectedException.php';

require_once 'Q/NotModifiedException.php';
require_once 'Q/InputException.php';
require_once 'Q/NotFoundException.php';
require_once 'Q/InvalidMethodException.php';
require_once 'Q/NotImplementedException.php';


/**
 * A common exception which can be mapped to an HTTP response status.
 * 
 * Common exceptions should only be thrown from a controller, not from the model.
 * The model can't know if an error is caused by incorrect input of by a bug made by the developer.
 * 
 * @package Exception
 * 
 * @todo CommonException is a crappy name, rename it to RestException.
 */
interface CommonException
{}
