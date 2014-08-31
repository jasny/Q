<?php
namespace Q;

require_once 'Q/Exception.php';

/**
 * An execption when a database action fails.
 * For example, failed to execute a query or setting up a connection.
 * 
 * @package DB
 */
class DB_Exception extends Exception
{}