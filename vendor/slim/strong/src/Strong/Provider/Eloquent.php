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
 * @copyright   Copyright (c) 2013, Andrew Smith.
 * @version     1.0.0
 */

namespace Strong\Provider;

class Eloquent extends \Strong\Provider
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
     * @return boolean
     */
    public function login($usernameOrEmail, $password, $remember = false)
    {
        if (! is_string($usernameOrEmail)) {
            return false;
        }

        $user = \User::where('username', $usernameOrEmail)
            ->orWhere('email', $usernameOrEmail);

        if (($user->email === $usernameOrEmail || $user->username === $usernameOrEmail)
            && $this->hashVerify($password, $user->password)) {
            return $this->completeLogin($user);
        }
    }

    /**
     * Login and store user details in Session
     *
     * @param \User $user
     * @return array
     */
    protected function completeLogin($user)
    {
        $users = $user;
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

    /**
     * @param $password
     * @return \false|string
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * @param $password
     * @param $hash
     * @return bool
     */
    public function hashVerify($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
