<?php
namespace Q;

require_once 'Q/Exception.php';
require_once 'Q/CommonException.php';
require_once 'Q/ExpectedException.php';

/**
 * Exception when a controller or an item is not found
 * 
 * @package Exception
 */
class NotFoundException extends Exception implements CommonException, ExpectedException
{
    /**
     * Class constructor
     * 
     * @param string $message
     * @param int $code
     */
    public function __construct($message="Item does not exist", $code=405)
    {
        parent::__construct($message, $code);
    }
}
