<?php
namespace Aura\Auth;

use Aura\Auth\Session\FakeSegment;
use Aura\Auth\Status;

class AuthTest extends \PHPUnit_Framework_TestCase
{
    protected $auth;

    protected $segment;

    protected function setUp()
    {
        $this->segment = new FakeSegment;
        $this->auth = new Auth($this->segment);
    }

    public function test()
    {
        $now = time();
        $this->auth->set(
            Status::VALID,
            $now,
            $now,
            'boshag',
            array('foo' => 'bar')
        );

        $this->assertSame(Status::VALID, $this->auth->getStatus());
        $this->assertSame($now, $this->auth->getFirstActive());
        $this->assertSame($now, $this->auth->getLastActive());
        $this->assertSame('boshag', $this->auth->getUserName());
        $this->assertSame(array('foo' => 'bar'), $this->auth->getUserData());
    }
}
