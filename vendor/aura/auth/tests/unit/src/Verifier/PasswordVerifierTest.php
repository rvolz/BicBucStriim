<?php
namespace Aura\Auth\Verifier;

class PasswordVerifierTest extends \PHPUnit_Framework_TestCase
{
    public function testBcrypt()
    {
        if (! defined('PASSWORD_BCRYPT')) {
            $this->markTestSkipped("password_hash functionality not available. Install ircmaxell/password-compat for 5.3+");
        }

        $verifier = new PasswordVerifier(PASSWORD_BCRYPT);
        $plaintext = 'password';
        $hashvalue = password_hash($plaintext, PASSWORD_BCRYPT);
        $this->assertTrue($verifier->verify($plaintext, $hashvalue));
        $this->assertFalse($verifier->verify('wrong', $hashvalue));
    }

    public function testHash()
    {
        $verifier = new PasswordVerifier('md5');
        $plaintext = 'password';
        $hashvalue = hash('md5', $plaintext);
        $this->assertTrue($verifier->verify($plaintext, $hashvalue));
        $this->assertFalse($verifier->verify('wrong', $hashvalue));
    }
}
