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
 * Password Verifier
 *
 * @package Aura.Auth
 *
 */
interface VerifierInterface
{
    /**
     *
     * Verify that a plaintext password matches a hashed one.
     *
     * @param string $plaintext Plaintext password.
     *
     * @param string $hashvalue Hashed password.
     *
     * @param array $extra Optional array of data.
     *
     * @return bool
     *
     */
    public function verify($plaintext, $hashvalue, array $extra = array());
}
