<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\Auth;
use Aura\Auth\Status;

class LogoutServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $session;

    protected $segment;

    protected $adapter;

    protected $auth;

    protected $login_service;

    protected $logout_service;

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

        $this->logout_service = new LogoutService(
            $this->adapter,
            $this->session
        );
    }

    public function testLogout()
    {
        $this->login_service->forceLogin($this->auth, 'boshag');
        $this->assertTrue($this->auth->isValid());

        $this->logout_service->logout($this->auth);
        $this->assertTrue($this->auth->isAnon());
    }

    public function testForceLogout()
    {
        $result = $this->login_service->forceLogin(
            $this->auth,
            'boshag',
            array('foo' => 'bar')
        );
        $this->assertSame(Status::VALID, $result);
        $this->assertTrue($this->auth->isValid());

        $result = $this->logout_service->forceLogout($this->auth);

        $this->assertSame(Status::ANON, $result);
        $this->assertSame(Status::ANON, $this->auth->getStatus());
        $this->assertNull($this->auth->getUserName());
    }
}
