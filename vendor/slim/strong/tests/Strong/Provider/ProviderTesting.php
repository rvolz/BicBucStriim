<?php

use Strong\Strong;

abstract class Strong_Provider_ProviderTesting extends PHPUnit_Framework_TestCase {

    abstract public function getObj();

    public function setUp() {
        $this->provider = $this->getObj();
        $_SESSION['auth_user'] = null;
    }

    public function tearDown() {
        $_SESSION['auth_user'] = null;
    }

    public function testCreateInstance() {
        $this->assertInstanceOf('\Strong\Provider', $this->provider->getProvider());
    }

    public function testCheckNotLogin() {
        $this->assertFalse($this->provider->loggedIn());
    }

    public function testLoginNonExistsUser() {
        $this->assertFalse($this->provider->login('adminTest', 'pass'));
    }

    public function testLoginInvalid() {
        $this->assertFalse($this->provider->login('admin', 'testInvalidPass'));
    }

    public function testLoginValid() {
        $this->assertTrue($this->provider->login('admin', 'pass'));
    }

    public function testLogout() {
        $this->provider->login('admin', 'pass');
        $this->provider->logout();
        $this->assertEquals(null, $this->provider->getUser());
    }


}