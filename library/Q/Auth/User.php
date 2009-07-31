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
     * Get the full name of the user
     * @return string
     */
    public function getFullname();
    
    /**
     * Get e-mail address of user.
     * 
     * @return string
     */
    public function getEmail();

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
     * Get all the roles the user is in
     * @return array
     */
    public function getRoles();
    
    
    /**
     * Check if user is in specific role(s)
     * 
     * @param string $role  Role name, multiple roles may be supplied as array
     * @param Multiple roles may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in one of the roles
     */
    public function authz($role);

    /**
     * Check if user has one of the specified roles
     * 
     * @param string $roles  role; multiple roles may be supplied as array
     * @param Multiple roles may be supplied as additional arguments
     * 
     * @throws Authz_Exception if the user is not in any of the $roles
     */
    public function authzAny($role);
}
