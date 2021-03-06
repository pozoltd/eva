<?php

/**
 * 2017-05-16 21:45:33
 */
namespace Eva\ORMs;

use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\User\UserInterface;


class User extends \Eva\Db\ORM implements UserInterface {

    function getFieldMap() {
        global $TBL_META;
        return array_merge(array(
            'title' => 'title', 
			'password' => 'password', 
			'password_' => 'extra1',
			'name' => 'name',
			'email' => 'email', 
			'image' => 'image', 
			'folder' => 'extra3', 
			'description' => 'description', 
			'resetToken' => 'extra2', 
			'resetDate' => 'date1', 
        ), array_combine(array_keys($TBL_META), array_keys($TBL_META)), array(
            'id' => 'id',
            'track' => 'track',
        ));
    }

    function getTable() {
        return '__contents';
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function eraseCredentials()
    {
        return $this;
    }

    public function getUsername()
    {
        return $this->title;
    }

    public function getRoles()
    {
        return array('ROLE_ADMIN');
    }

    public function getSalt()
    {
        return '';
    }

    public function save()
    {
        if ($this->password_) {
            $encoder = new MessageDigestPasswordEncoder();
            $this->password = $encoder->encodePassword($this->password_, '');
            $this->password_ = null;
        }
        parent::save();
    }

}