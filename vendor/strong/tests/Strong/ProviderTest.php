<?php

use Strong\Strong;
use Strong\AbstractMock;

class Strong_ProviderTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        $this->provider = new AbstractMock(array());
        $_SESSION['auth_user'] = null;
    }

    public function tearDown() {
        $_SESSION['auth_user'] = null;
    }

    public function testCreateInstance() {
        $this->assertInstanceOf('\Strong\Provider', $this->provider);
    }

    public function testCheckNotLogin() {
        $this->assertFalse($this->provider->loggedIn());
    }

    public function testGetUserEmpty() {
        $this->assertNull($this->provider->getUser());
    }

    public function testLogin() {
        $this->provider->login(true, true);
        $this->assertEquals('test', $_SESSION['auth_user']);
    }

    public function testGetUser() {
        $this->provider->login(true, true);
        $this->assertEquals('test', $this->provider->getUser());
    }

    public function testLogout() {
        $this->provider->login(true, true);
        $this->provider->logout();
        $this->assertEquals(null, $this->provider->getUser());
    }

}