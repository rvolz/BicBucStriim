<?php
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\FakeAdapter;
use Aura\Auth\Session\FakeSession;
use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Session\Timer;
use Aura\Auth\Auth;
use Aura\Auth\Status;

class ResumeServiceTest extends \PHPUnit_Framework_TestCase
{
    protected $segment;

    protected $adapter;

    protected $session;

    protected $timer;

    protected $auth;

    protected $login_service;

    protected $logout_service;

    protected $resume_service;

    protected function setUp()
    {
        $this->segment = new FakeSegment;
        $this->session = new FakeSession;
        $this->adapter = new FakeAdapter;
        $this->timer = new Timer(1440, 14400);

        $this->auth = new Auth($this->segment);

        $this->login_service = new LoginService(
            $this->adapter,
            $this->session
        );

        $this->logout_service = new LogoutService(
            $this->adapter,
            $this->session
        );

        $this->resume_service = new ResumeService(
            $this->adapter,
            $this->session,
            $this->timer,
            $this->logout_service
        );
    }

    public function testResume()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->login_service->forceLogin($this->auth, 'boshag');
        $this->assertTrue($this->auth->isValid());

        $this->auth->setLastActive(time() - 100);
        $this->resume_service->resume($this->auth);
        $this->assertTrue($this->auth->isValid());
        $this->assertSame(time(), $this->auth->getLastActive());
    }

    public function testResume_cannotResume()
    {
        $this->session->allow_resume = false;
        $this->assertTrue($this->auth->isAnon());
        $this->resume_service->resume($this->auth);
        $this->assertTrue($this->auth->isAnon());
    }

    public function testResume_logoutIdle()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->login_service->forceLogin($this->auth, 'boshag');
        $this->assertTrue($this->auth->isValid());

        $this->auth->setLastActive(time() - 1441);

        $this->resume_service->resume($this->auth);
        $this->assertTrue($this->auth->isIdle());
        $this->assertNull($this->auth->getUserName());
    }

    public function testResume_logoutExpired()
    {
        $this->assertTrue($this->auth->isAnon());
        $this->login_service->forceLogin($this->auth, 'boshag');
        $this->assertTrue($this->auth->isValid());

        $this->auth->setFirstActive(time() - 14441);

        $this->resume_service->resume($this->auth);
        $this->assertTrue($this->auth->isExpired());
        $this->assertNull($this->auth->getUserName());
    }
}
