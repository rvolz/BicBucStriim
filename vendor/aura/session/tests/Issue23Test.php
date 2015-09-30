<?php
namespace Aura\Session;

/**
 * @runTestsInSeparateProcesses
 */
class Issue23Test extends \PHPUnit_Framework_TestCase
{
    protected $session;

    protected $segment;

    protected $name = __CLASS__;

    protected function setUp()
    {
        $this->session = $this->newSession();
        $this->segment = $this->session->getSegment($this->name);
    }

    protected function newSession(array $cookies = array())
    {
        // start session earlier
        session_start();
        return new Session(
            new SegmentFactory,
            new CsrfTokenFactory(new Randval(new Phpfunc)),
            new Phpfunc,
            $cookies
        );
    }

    public function testFlash()
    {
        // set a value
        $this->segment->setFlash('foo', 'bar');
        $expect = 'bar';
        $this->assertSame($expect, $this->segment->getFlashNext('foo'));
        $this->assertNull($this->segment->getFlash('foo'));

        // set a value and make it available now
        $this->segment->setFlashNow('baz', 'dib');
        $expect = 'dib';
        $this->assertSame($expect, $this->segment->getFlash('baz'));
        $this->assertSame($expect, $this->segment->getFlashNext('baz'));

        // clear the next values
        $this->segment->clearFlash();
        $this->assertNull($this->segment->getFlashNext('foo'));
        $this->assertNull($this->segment->getFlashNext('baz'));
        $expect = 'dib';
        $this->assertSame($expect, $this->segment->getFlash('baz'));

        // set some current values and make sure they get kept
        $now =& $_SESSION[Session::FLASH_NOW][$this->name];
        $now['foo'] = 'bar';
        $now['baz'] = 'dib';
        $this->segment->keepFlash();
        $this->assertSame('bar', $this->segment->getFlashNext('foo'));
        $this->assertSame('dib', $this->segment->getFlashNext('baz'));

        // clear the current and future values
        $this->segment->clearFlashNow();
        $this->assertNull($this->segment->getFlash('foo'));
        $this->assertNull($this->segment->getFlashNext('foo'));
        $this->assertNull($this->segment->getFlash('baz'));
        $this->assertNull($this->segment->getFlashNext('baz'));
    }
}
