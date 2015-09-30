<?php
namespace Aura\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    // the session object
    protected $session;

    protected function setUp()
    {
        $this->phpfunc = new FakePhpfunc;
        $handler = new FakeSessionHandler();
        session_set_save_handler(
            array($handler, 'open'),
            array($handler, 'close'),
            array($handler, 'read'),
            array($handler, 'write'),
            array($handler, 'destroy'),
            array($handler, 'gc')
        );
        $this->session = $this->newSession();
    }

    protected function newSession(array $cookies = array())
    {
        return new Session(
            new SegmentFactory,
            new CsrfTokenFactory(new Randval(new Phpfunc)),
            $this->phpfunc,
            $cookies
        );
    }

    public function teardown()
    {
        session_unset();
        if (session_id() !== '') {
            session_destroy();
        }
    }

    public function testStart()
    {
        $this->session->start();
        $this->assertTrue($this->session->isStarted());
    }

    public function testClear()
    {
        // get a test segment and set some data
        $segment = $this->session->getSegment('test');
        $segment->set('foo', 'bar');
        $segment->set('baz', 'dib');

        $expect = array(
            Session::FLASH_NEXT => array(
                'test' => array(),
            ),
            Session::FLASH_NOW => array(
                'test' => array(),
            ),
            'test' => array(
                'foo' => 'bar',
                'baz' => 'dib',
            ),
        );

        $this->assertSame($expect, $_SESSION);

        // now clear it
        $this->session->clear();
        $this->assertSame(array(), $_SESSION);
    }

    public function testDestroy()
    {
        // get a test segment and set some data
        $segment = $this->session->getSegment('test');
        $segment->set('foo', 'bar');
        $segment->set('baz', 'dib');

        $this->assertTrue($this->session->isStarted());

        $expect = array(
            Session::FLASH_NEXT => array(
                'test' => array(),
            ),
            Session::FLASH_NOW => array(
                'test' => array(),
            ),
            'test' => array(
                'foo' => 'bar',
                'baz' => 'dib',
            ),
        );

        $this->assertSame($expect, $_SESSION);

        // now destroy it
        $this->session->destroy();
        $this->assertFalse($this->session->isStarted());
    }

    public function testCommit()
    {
        $this->session->commit();
        $this->assertFalse($this->session->isStarted());
    }

    public function testCommitAndDestroy()
    {
        // get a test segment and set some data
        $segment = $this->session->getSegment('test');
        $segment->set('foo', 'bar');
        $segment->set('baz', 'dib');

        $this->assertTrue($this->session->isStarted());

        $expect = array(
            Session::FLASH_NEXT => array(
                'test' => array(),
            ),
            Session::FLASH_NOW => array(
                'test' => array(),
            ),
            'test' => array(
                'foo' => 'bar',
                'baz' => 'dib',
            ),
        );

        $this->assertSame($expect, $_SESSION);

        $this->session->commit();
        $this->session->destroy();
        $segment = $this->session->getSegment('test');
        $this->assertSame(array(), $_SESSION);
    }

    public function testGetSegment()
    {
        $segment = $this->session->getSegment('test');
        $this->assertInstanceof('Aura\Session\Segment', $segment);
    }

    public function testGetCsrfToken()
    {
        $actual = $this->session->getCsrfToken();
        $expect = 'Aura\Session\CsrfToken';
        $this->assertInstanceOf($expect, $actual);
    }

    public function testisResumable()
    {
        // should not look active
        $this->assertFalse($this->session->isResumable());

        // fake a cookie
        $cookies = array(
            $this->session->getName() => 'fake-cookie-value',
        );
        $this->session = $this->newSession($cookies);

        // now it should look active
        $this->assertTrue($this->session->isResumable());
    }

    public function testGetAndRegenerateId()
    {
        $this->session->start();
        $old_id = $this->session->getId();
        $this->session->regenerateId();
        $new_id = $this->session->getId();
        $this->assertTrue($old_id != $new_id);

        // check the csrf token as well
        $old_value = $this->session->getCsrfToken()->getValue();
        $this->session->regenerateId();
        $new_value = $this->session->getCsrfToken()->getValue();
        $this->assertTrue($old_value != $new_value);
    }

    public function testSetAndGetName()
    {
        $expect = 'new_name';
        $this->session->setName($expect);
        $actual = $this->session->getName();
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetSavePath()
    {
        $expect = '/new/save/path';
        $this->session->setSavePath($expect);
        $actual = $this->session->getSavePath();
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetCookieParams()
    {
        $expect = $this->session->getCookieParams();
        $expect['lifetime'] = '999';
        $this->session->setCookieParams($expect);
        $actual = $this->session->getCookieParams();
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetCacheExpire()
    {
        $expect = 123;
        $this->session->setCacheExpire($expect);
        $actual = $this->session->getCacheExpire();
        $this->assertSame($expect, $actual);
    }

    public function testSetAndGetCacheLimiter()
    {
        $expect = 'private_no_cache';
        $this->session->setCacheLimiter($expect);
        $actual = $this->session->getCacheLimiter();
        $this->assertSame($expect, $actual);
    }

    public function testResume()
    {
        // should not look active
        $this->assertFalse($this->session->isResumable());
        $this->assertFalse($this->session->resume());

        // fake a cookie so a session looks available
        $cookies = array(
            $this->session->getName() => 'fake-cookie-value',
        );
        $this->session = $this->newSession($cookies);
        $this->assertTrue($this->session->resume());

        // now it should already active
        $this->assertTrue($this->session->resume());
    }

    public function testIsStarted_php53()
    {
        $this->phpfunc->functions = array('session_status' => false);
        $this->session = $this->newSession();
        $this->assertFalse($this->session->isStarted());
        $this->session->start();
        $this->assertTrue($this->session->isStarted());
    }
}
