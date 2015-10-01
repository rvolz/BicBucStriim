<?php
namespace Aura\Auth\Verifier;

class HtpasswdVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->verifier = new HtpasswdVerifier;
    }

    public function testDes()
    {
        $hashvalue = 'ngPfeOKlo3uIs';
        $this->assertTrue($this->verifier->verify('12345678', $hashvalue));
        $this->assertFalse($this->verifier->verify('wrong', $hashvalue));
        $this->assertFalse($this->verifier->verify('1234567890', $hashvalue));
    }

    public function testSha()
    {
        $hashvalue = '{SHA}MCdMR5A70brHYzu/CXQxSeurgF8=';
        $this->assertTrue($this->verifier->verify('passwd', $hashvalue));
        $this->assertFalse($this->verifier->verify('wrong', $hashvalue));
    }

    public function testApr()
    {
        $hashvalue = '$apr1$c4b0dz9t$FRDSRse3FWsZidoPAx9g0.';
        $this->assertTrue($this->verifier->verify('tkirah', $hashvalue));
        $this->assertFalse($this->verifier->verify('wrong', $hashvalue));
    }

    public function testBcrypt()
    {
        if (! function_exists('password_verify')) {
            $this->markTestSkipped("password_hash functionality not available. Install ircmaxell/password-compat for 5.3+");
        }
        $hashvalue = '$2y$05$VBdzN9btLNhVZi1tyl8nOeNiQcafX.A8pR/HJT57XHKK2lGmPpaDW';
        $this->assertTrue($this->verifier->verify('1234567890', $hashvalue));
        $this->assertFalse($this->verifier->verify('wrong', $hashvalue));
    }
}
