<?php

class User {

    public $logins = 0;
    public $last_login = 0;

    public static $checkLogins = 0;
    public static $checkLast_login = 0;

    public static function find_by_username_or_email($value) {
        if ($value === null)
            return null;
        return (object) array(
            'id' => 1,
            'username' => 'admin',
            'email' => 'admin',
            'password' => 'pass',
            'logins' => 1,
        );
    }

    public static function find($id) {
        return new self();
    }

    public function save() {
        self::$checkLogins += $this->logins;
        self::$checkLast_login += $this->last_login;
    }

}