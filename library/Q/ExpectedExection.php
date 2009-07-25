<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * Interface to indicate that the exception is not caused by a system error.
 * This should be used if something fails because of incorrect user input, for instance when validation fails.
 * 
 * ExpectedExceptions should be allowed to be uncaught and should be correctly handled by the error handler.
 * 
 * @package Exception
 */
interface ExpectedException
{}

/**
 * Exception for invalid user data
 */
class InputException extends Exception implements ExpectedException
{}

/**
 * Exception when an item is not found
 */
class NotFoundException extends Exception implements ExpectedException
{}

/**
 * Exception when a method can't be called
 */
class InvalidMethodException extends Exception implements ExpectedException
{}
