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

class ObjectContainer extends \Strong\Provider
{
    /**
     * @var object
     */
    protected $user;

    /**
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->config = array_merge($this->settings, $this->config);

        if (!isset($this->config['user.class']) || ! is_object($this->config['user.class'])) {
            throw new \InvalidArgumentException('You must add valid User Class object');
        }

        $this->user = $this->config['user.class'];
    }

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

        if (($this->user->email === $usernameOrEmail || $this->user->username === $usernameOrEmail)
            && $this->hashVerify($password, $this->user->password)) {
            return $this->completeLogin($this->user);
        }
    }

    /**
     * Login and store user details in Session
     *
     * @param object $user
     * @return boolean
     */
    protected function completeLogin($user)
    {
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
