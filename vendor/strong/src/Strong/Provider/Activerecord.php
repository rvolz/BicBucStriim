<?php
/**
 * Strong Authentication Library
 *
 * User authentication and authorization library
 *
 * @license     MIT Licence
 * @category    Libraries
 * @author      Andrew Smith
 * @link        http://www.silentworks.co.uk
 * @copyright   Copyright (c) 2012, Andrew Smith.
 * @version     1.0.0
 */

namespace Strong\Provider;

class Activerecord extends \Strong\Provider
{
    /**
     * User login check based on provider
     *
     * @return boolean
     */
    public function loggedIn()
    {
        return (isset($_SESSION['auth_user']) && !empty($_SESSION['auth_user']));
    }

    /**
     * To authenticate user based on username or email
     * and password
     *
     * @param string $usernameOrEmail
     * @param string $password
     * @param bool $remember
     * @return array
     */
    public function login($usernameOrEmail, $password, $remember = false)
    {
        if (! is_string($usernameOrEmail)) {
            return false;
        }

        $user = \User::find_by_username_or_email($usernameOrEmail, $usernameOrEmail);

        if (($user->email === $usernameOrEmail || $user->username === $usernameOrEmail)
            && $user->password === $password) {
            return $this->completeLogin($user);
        }

        return false;
    }

    /**
     * Login and store user details in Session
     *
     * @param object $user
     * @return boolean
     */
    protected function completeLogin($user)
    {
        $users = \User::find($user->id);
        $users->logins = $user->logins + 1;
        $users->last_login = time();
        $users->save();

        $userInfo = array(
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'logged_in' => true
        );

        return parent::completeLogin($userInfo);
    }
}
