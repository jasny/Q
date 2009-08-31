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
 * An exception which can be mapped to an HTTP response status.
 * 
 * Rest exceptions should only be thrown from a controller, not from the model. The model can't know if an error
 * is caused by incorrect input of by a bug made by the developer. If you're using Rest exeptions in the model,
 * it shows that your not doing enough validation in the controller. 
 * 
 * @package Exception
 */
interface RestException
{}
