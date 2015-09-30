<?php
namespace Aura\Auth\Session;

/**
 * @runTestsInSeparateProcesses
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->setSession();
    }

    protected function setSession(array $cookie = array())
    {
        $this->session = new Session($cookie);
    }

    public function testStart()
    {
        // no session yet
        $this->assertTrue(session_id() === '');

        // start once
        $this->session->start();
        $id = session_id();
        $this->assertTrue(session_id() !== '');
    }

    public function testResume()
    {
        // fake a previous session cookie
        $this->setSession(array(session_name() => true));

        // no session yet
        $this->assertTrue(session_id() === '');

        // resume the pre-existing session
        $this->assertTrue($this->session->resume());

        // now we have a session
        $this->assertTrue(session_id() !== '');

        // try again after the session is already started
        $this->assertTrue($this->session->resume());
    }

    public function testResume_nonePrevious()
    {
        // no previous session cookie
        $cookie = array();
        $this->session = new Session($cookie);

        // no session yet
        $this->assertTrue(session_id() === '');

        // no pre-existing session to resume
        $this->assertFalse($this->session->resume());

        // still no session
        $this->assertTrue(session_id() === '');
    }

    public function testRegenerateId()
    {
        $cookie = array();
        $this->session = new Session($cookie);

        $this->session->start();
        $old_id = session_id();
        $this->assertTrue(session_id() !== '');

        $this->session->regenerateId();
        $new_id = session_id();
        $this->assertTrue($old_id !== $new_id);
    }
}
