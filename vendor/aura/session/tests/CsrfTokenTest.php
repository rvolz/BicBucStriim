<?php
namespace Aura\Session;

/**
 * @runTestsInSeparateProcesses
 */
class CsrfTokenTest extends \PHPUnit_Framework_TestCase
{
    protected $session;

    protected $csrf_token;

    protected $name = __CLASS__;

    protected $phpfunc;

    protected function setUp()
    {
        $this->phpfunc = new FakePhpfunc;

        $this->session = new Session(
            new SegmentFactory,
            new CsrfTokenFactory(new Randval($this->phpfunc)),
            $this->phpfunc,
            $_COOKIE
        );
    }

    public function teardown()
    {
        session_unset();
        if (session_id() !== '') {
            session_destroy();
        }
    }

    public function testLaziness()
    {
        $this->assertFalse($this->session->isStarted());
        $token = $this->session->getCsrfToken();
        $this->assertTrue($this->session->isStarted());
    }

    public function testGetAndRegenerateValue()
    {
        $token = $this->session->getCsrfToken();

        $old = $token->getValue();
        $this->assertTrue($old != '');

        // with openssl
        $this->phpfunc->extensions = array('openssl');
        $token->regenerateValue();
        $openssl = $token->getValue();
        $this->assertTrue($old != $openssl);

        // with mcrypt
        $this->phpfunc->extensions = array('mcrypt');
        $token->regenerateValue();
        $mcrypt = $token->getValue();
        $this->assertTrue($old != $openssl && $old != $mcrypt);

        // with nothing
        $this->phpfunc->extensions = array();
        $this->setExpectedException('Aura\Session\Exception');
        $token->regenerateValue();
    }

    public function testIsValid()
    {
        $token = $this->session->getCsrfToken();
        $value = $token->getValue();

        $this->assertTrue($token->isValid($value));
        $token->regenerateValue();
        $this->assertFalse($token->isValid($value));
    }
}
