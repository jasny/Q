<?php
namespace Q;

/**
 * Interface to make sure a class can act as a user for Q\Auth.
 * The object should have public properties for id, username, password, active, expires and groups. 
 * 
 * @package Auth
 */
interface Auth_User
{
    /**
     * Check if user is in specific group(s)
     * 
     * @param string $group  Group name, multiple groups may be supplied as array
     * @param Multiple groups may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in one of the groups
     */
    public function authz($group);
}
