<?php
namespace Q;

require_once 'Q/DB/Exception.php';

/**
 * An execption when a database action fails.
 * For example, failed to execute a query or setting up a connection.
 * 
 * @package DB
 */
class DB_LimitException extends DB_Exception
{}