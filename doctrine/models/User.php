<?php

/**
 * User
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5845 2009-06-09 07:36:57Z jwage $
 */
class User extends BaseUser
{

    public function setUp()
    {
        parent::setUp();
        $this->hasMutator('password', 'passwordEncrypt');

    }
    
    /**
    * Encrypt password
    * 
    * @param string $value
    */
    public function passwordEncrypt($value)
    {
        $this->_set('password', User_Encrypt::encrypt($value));
    }
    
    public function isRole($role)
    {
        return ($this->role & $role === $role);
    }    
}