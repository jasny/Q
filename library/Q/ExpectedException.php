<?php
namespace Q;

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
