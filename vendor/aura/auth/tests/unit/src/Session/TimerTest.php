<?php
namespace Aura\Auth\Session;

use Aura\Auth\Status;

class TimerTest extends \PHPUnit_Framework_TestCase
{
    protected $timer;

    protected function setUp()
    {
        $this->timer = new Timer(1440, 14400);
    }

    public function testHasExpired()
    {
        $this->assertFalse($this->timer->hasExpired(time()));
        $this->assertTrue($this->timer->hasExpired(time() - 14441));
    }

    public function testHasIdled()
    {
        $this->assertFalse($this->timer->hasIdled(time()));
        $this->assertTrue($this->timer->hasIdled(time() - 1441));
    }

    public function testGetTimeoutStatus()
    {
        $actual = $this->timer->getTimeoutStatus(
            time() - 14441,
            time()
        );
        $this->assertSame(Status::EXPIRED, $actual);

        $actual = $this->timer->getTimeoutStatus(
            time() - 1442,
            time() - 1441
        );

        $this->assertSame(Status::IDLE, $actual);

        $this->assertNull($this->timer->getTimeoutStatus(
            time(),
            time()
        ));
    }

    public function testSetIdleTtl_bad()
    {
        $this->setExpectedException('Aura\Auth\Exception');
        $this->timer->setIdleTtl(1441);
    }

    public function testSetExpireTtl_bad()
    {
        $this->setExpectedException('Aura\Auth\Exception');
        $this->timer->setExpireTtl(14441);
    }
}
