<?php

namespace Strong\Provider;

class Mock extends \Strong\Provider {

    public static $logged = false;

    public function hashPassword($password) {
        return $password;
    }

    public function loggedIn() {
        return self::$logged;
    }

    public function login($usernameOrEmail, $password, $remember = false) {
        self::$logged = ($usernameOrEmail === $password);
        return ($usernameOrEmail === $password);
    }

    public function logout($destroy = false) {
        self::$logged = false;
    }

}