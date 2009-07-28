<?php
namespace Q;

/**
 * Interface to make sure a class can act as a user for Q\Auth.
 * 
 * @package Auth
 */
interface Auth_User
{
    /**
     * Get the user id
     * @return int|string
     */
    public function getId();
    
    /**
     * Get the username
     * @return string
     */
    public function getUsername();

    /**
     * Get the hashed password
     * @return string
     */
    public function getPassword();
    
    /**
     * Check if user is still active
     * @return boolean
     */
    public function isActive();

    /**
     * Get timestamp when password expires
     * @return int
     */
    public function getExpires();
    
    /**
     * Get all the groups the user is in
     * @return array
     */
    public function getGroups();
    
    
    /**
     * Check if user is in specific group(s)
     * 
     * @param string $group  Group name, multiple groups may be supplied as array
     * @param Multiple groups may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in one of the groups
     */
    public function authz($group);

    /**
     * Check if user has one of the specified groups
     * 
     * @param string $groups  group; multiple groups may be supplied as array
     * @param Multiple groups may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in any of the $groups
     */
    public function authzAny($group);
}
