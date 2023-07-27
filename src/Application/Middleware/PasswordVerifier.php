<?php

namespace App\Application\Middleware;

use Aura\Auth\Verifier\VerifierInterface;

/*
 * Replaces \Aura\Auth\Verifier\PasswordVerifier.
 * Workaround for https://github.com/auraphp/Aura.Auth/issues/102.
 * TODO: Remove when Aura issue is solved.
 */
class PasswordVerifier implements VerifierInterface
{
    /**
     *
     * The hashing algorithm to use.
     *
     * @var string|int
     *
     */
    protected $algo;

    /**
     *
     * Constructor.
     *
     * @param string|int $algo The hashing algorithm to use.
     *
     */
    public function __construct($algo)
    {
        $this->algo = $algo;
    }

    /**
     *
     * Verifies a password against a hash.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue The comparison hash.
     *
     * @param array $extra Optional array if used by verify
     *
     * @return bool
     *
     */
    public function verify($plaintext, $hashvalue, array $extra = [])
    {
        // FIXME: workaround for https://github.com/auraphp/Aura.Auth/issues/102 and https://github.com/rvolz/BicBucStriim/issues/348
        if (PHP_VERSION_ID < 70400) {
            if (is_string($this->algo)) {
                return hash($this->algo, $plaintext) === $hashvalue;
            } else {
                return password_verify($plaintext, $hashvalue);
            }
        } else {
            return password_verify($plaintext, $hashvalue);
        }
    }
}
