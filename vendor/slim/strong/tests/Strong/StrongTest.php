<?php

use Strong\Strong;

class Strong_StrongTest extends PHPUnit_Framework_TestCase {

    public function testCreateInstance() {
        $strong = new Strong(array('provider' => 'mock'));
        $this->assertInstanceOf('\Strong\Strong', $strong);
    }

    public function testCreateInstanceWithFactory() {
        $strong = Strong::factory(array('provider' => 'mock'));
        $this->assertInstanceOf('\Strong\Strong', $strong);
    }

    public function testCreateInstanceWithProviderObject() {
        $provider = new \Strong\Provider\Mock(array());
        $strong = new Strong(array('provider' => $provider));
        $this->assertInstanceOf('\Strong\Strong', $strong);
        $this->assertSame($provider, $strong->getProvider());
    }

    public function testGetInstance() {
        $strong = Strong::factory(array('provider' => 'mock'));
        $strong = Strong::getInstance();
        $this->assertInstanceOf('\Strong\Strong', $strong);
    }

    public function testCreateInstanceWithNotExistProvider() {
        try {
            $strong = new Strong(array('provider' => 'notExistProvider'));
            $this->fail();
        } catch(\Exception $e) {
            $this->assertEquals('Strong is missing provider \Strong\Provider\notExistProvider in Strong\Strong', $e->getMessage());
        }
    }

    public function testCreateInstanceWithInvalidProvider() {
        try {
            $strong = new Strong(array('provider' => 'invalid'));
            $this->fail();
        } catch(\Exception $e) {
            $this->assertEquals('The current Provider Strong\Provider\Invalid does not extend \Strong\Provider', $e->getMessage());
        }
    }

    public function testLoggedIn() {
        $strong = new Strong(array('provider' => 'mock'));
        \Strong\Provider\Mock::$logged = true;
        $this->assertTrue($strong->loggedIn());
    }

    public function testProtect() {
        $this->assertTrue(Strong::protect());
    }

    public function testLoginWithEmptyPassword() {
        $strong = Strong::factory(array('provider' => 'mock'));
        $this->assertFalse($strong->login('test', null));
    }

    public function testLoginWithPasswordIsNotString() {
        $strong = Strong::factory(array('provider' => 'mock'));
        $this->assertFalse($strong->login('test', array('test')));
    }

    public function testLoginWithValidData() {
        $strong = Strong::factory(array('provider' => 'mock'));
        $this->assertTrue($strong->login('test', 'test'));
        $this->assertTrue(Strong::protect());
    }

    public function testLogout() {
        \Strong\Provider\Mock::$logged = true;
        Strong::factory(array('provider' => 'mock'))->logout();
        $this->assertFalse(\Strong\Provider\Mock::$logged);
    }

    public function testGetUser() {
        $_SESSION['auth_user'] = null;
        $user = Strong::factory(array('provider' => 'mock'))->getUser();
        $this->assertEquals('', $user);
    }

    public function testGetSetName() {
        $strong = Strong::factory(array('provider' => 'mock'));
        $strong->setName('test');
        $this->assertEquals('test', $strong->getName());
    }

    public function testSetConfig() {
        $strong = Strong::factory(array('provider' => 'mock'));
        $strong->setConfig(array('provider' => 'test', 'opt' => 'test-2'));
        $strongRefl = new \ReflectionObject($strong);
        $config = $strongRefl->getProperty('config');
        $config->setAccessible(true);
        $this->assertEquals(array('name' => 'default', 'provider' => 'test', 'opt' => 'test-2'), $config->getValue($strong));
    }

    public function testGetProvider() {
        $strong = Strong::factory(array('provider' => 'mock'));
        $this->assertInstanceOf('\Strong\Provider\Mock', $strong->getProvider());
    }

}