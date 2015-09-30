<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\Auth;
use Aura\Auth\Status;

class LoginServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $segment;

    protected $session;

    protected $adapter;

    protected $auth;

    protected $login_service;

    protected function setUp()
    {
        $this->segment = new FakeSegment;
        $this->session = new FakeSession;
        $this->adapter = new FakeAdapter;

        $this->auth = new Auth($this->segment);

        $this->login_service = new LoginService(
            $this->adapter,
            $this->session
        );
    }

    public function testLogin()
    {
        $this->assertTrue($this->auth->isAnon());

        $this->login_service->login(
            $this->auth,
            array('username' => 'boshag')
        );

        $this->assertTrue($this->auth->isValid());
        $this->assertSame('boshag', $this->auth->getUserName());
    }

    public function testForceLogin()
    {
        $this->assertTrue($this->auth->isAnon());

        $result = $this->login_service->forceLogin(
            $this->auth,
            'boshag',
            array('foo' => 'bar')
        );

        $this->assertSame(Status::VALID, $result);
        $this->assertSame(Status::VALID, $this->auth->getStatus());
        $this->assertSame('boshag', $this->auth->getUserName());
        $this->assertSame(array('foo' => 'bar'), $this->auth->getUserData());
    }

    public function testForceLogin_cannotResumeOrStart()
    {
        $this->session->allow_resume = false;
        $this->session->allow_start = false;

        $this->assertTrue($this->auth->isAnon());

        $result = $this->login_service->forceLogin(
            $this->auth,
            'boshag',
            array('foo' => 'bar')
        );

        $this->assertFalse($result);
        $this->assertTrue($this->auth->isAnon());
    }
}
