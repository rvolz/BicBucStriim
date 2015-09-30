<?php
namespace Aura\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SegmentTest extends \PHPUnit_Framework_TestCase
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
        return new Session(
            new SegmentFactory,
            new CsrfTokenFactory(new Randval(new Phpfunc)),
            new Phpfunc,
            $cookies
        );
    }

    protected function getValue($key = null)
    {
        if ($key) {
            return $_SESSION[$this->name][$key];
        } else {
            return $_SESSION[$this->name];
        }
    }

    protected function setValue($key, $val)
    {
        $_SESSION[$this->name][$key] = $val;
    }

    public function testMagicMethods()
    {
        $this->assertNull($this->segment->get('foo'));

        $this->segment->set('foo', 'bar');
        $this->assertSame('bar', $this->segment->get('foo'));
        $this->assertSame('bar', $this->getValue('foo'));

        $this->setValue('foo', 'zim');
        $this->assertSame('zim', $this->segment->get('foo'));
    }

    public function testClear()
    {
        $this->segment->set('foo', 'bar');
        $this->segment->set('baz', 'dib');
        $this->assertSame('bar', $this->getValue('foo'));
        $this->assertSame('dib', $this->getValue('baz'));

        // now clear the data
        $this->segment->clear();
        $this->assertSame(array(), $this->getValue());
        $this->assertNull($this->segment->get('foo'));
        $this->assertNull($this->segment->get('baz'));
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

    public function testGetDoesNotStartSession()
    {
        $this->assertFalse($this->session->isStarted());
        $foo = $this->segment->get('foo');
        $this->assertNull($foo);
        $this->assertFalse($this->session->isStarted());
    }

    public function testGetResumesSession()
    {
        // fake a cookie
        $cookies = array(
            $this->session->getName() => 'fake-cookie-value',
        );
        $this->session = $this->newSession($cookies);

        // should be active now, even though not started
        $this->assertTrue($this->session->isResumable());

        // reset the segment to use the new session manager
        $this->segment = $this->session->getSegment($this->name);

        // this should restart the session
        $foo = $this->segment->get('foo');
        $this->assertTrue($this->session->isStarted());
    }

    public function testSetStartsSessionAndCanReadAfter()
    {
        // no session yet
        $this->assertFalse($this->session->isStarted());

        // set it
        $this->segment->set('foo', 'bar');

        // session should have started
        $this->assertTrue($this->session->isStarted());

        // get it from the session
        $foo = $this->segment->get('foo');
        $this->assertSame('bar', $foo);

        // make sure it's actually in $_SESSION
        $this->assertSame($foo, $_SESSION[$this->name]['foo']);
    }

    public function testClearDoesNotStartSession()
    {
        $this->assertFalse($this->session->isStarted());
        $this->segment->clear();
        $this->assertFalse($this->session->isStarted());
    }

    public function testSetFlashStartsSessionAndCanReadAfter()
    {
        // no session yet
        $this->assertFalse($this->session->isStarted());

        // set it
        $this->segment->setFlash('foo', 'bar');

        // session should have started
        $this->assertTrue($this->session->isStarted());

        // should see it in the session
        $actual = $_SESSION[Session::FLASH_NEXT][$this->name]['foo'];
        $this->assertSame('bar', $actual);

    }

    public function testGetFlashDoesNotStartSession()
    {
        $this->assertFalse($this->session->isStarted());
        $this->assertNull($this->segment->getFlash('foo'));
        $this->assertFalse($this->session->isStarted());
    }
}
