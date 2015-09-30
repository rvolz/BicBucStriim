<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Verifier;

/**
 *
 * Htaccess password Verifier
 *
 * @package Aura.Auth
 *
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
    public function verify($plaintext, $hashvalue, array $extra = array())
    {
        if (is_string($this->algo)) {
            return hash($this->algo, $plaintext) === $hashvalue;
        } else {
            return password_verify($plaintext, $hashvalue);
        }
    }
}
