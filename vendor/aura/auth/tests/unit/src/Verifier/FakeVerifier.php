<?php
namespace Aura\Auth\Verifier;

class FakeVerifier implements VerifierInterface
{
    public function verify($plaintext, $hashvalue, array $extra = array())
    {
        throw new \Exception('should not be calling this');
    }
}
