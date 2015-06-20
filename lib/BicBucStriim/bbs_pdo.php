<?php
/**
 * Strong Authentication Library
 *
 * User authentication and authorization library based on PDO.php
 *
 * @license     MIT Licence
 * @category    Libraries
 * @author      Rainer Volz
 * @link        http://textmulch.de
 * @copyright   Copyright (C) 2013, Rainer Volz
 * @version     1.0.0
 */

class BBSPDO extends \Strong\Provider
{
    /**
     * Initialize the PDO connection and merge user
     * config with defaults.
     *
     * > Changes for depenencies injection! (PDO Connection)
     *
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct($config)
    {
        parent::__construct($config);
        $this->config = array_merge($this->config, $config);

        if (!isset($this->config['pdo']) || !($this->config['pdo'] instanceof \PDO)) {
            throw new \InvalidArgumentException('You must add valid pdo connection object');
        }

        $this->pdo = $this->config['pdo'];
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
     * @param string $username
     * @param string $password
     * @param bool $remember
     * @return boolean
     */
    public function login($username, $password, $remember = false)
    {
        if (!is_string($username)) {
            return false;
        }

        $sql = "SELECT * FROM user WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(\PDO::FETCH_OBJ);

        if (is_object($user) && ($user->username === $username)
            && $this->hashVerify($password, $user->password)
        ) {
            return $this->completeLogin($user);
        }
        return false;
    }

    /**
     * Verify hashed password
     *
     * @param $password
     * @param $hash
     * @return bool
     */
    public function hashVerify($password, $hash)
    {
        return password_verify($password, $hash);
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
            'languages' => $user->languages,
            'tags' => $user->tags,
            'role' => $user->role,
            'logged_in' => true
        );

        return parent::completeLogin($userInfo);
    }

    /**
     * Password Hashing
     *
     * @param $password
     * @return \false|string
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
