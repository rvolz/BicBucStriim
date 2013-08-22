<?php

namespace Strong;

class AbstractMock extends \Strong\Provider {

    public function loggedIn() {
        return isset($_SESSION['auth_user']);
    }

    public function login($usernameOrEmail, $password, $remember = false) {
        $this->completeLogin('test');
    }

}